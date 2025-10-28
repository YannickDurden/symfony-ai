<?php

namespace App\Command\Documentation;

use App\Service\Documentation\DocumentationCollector;
use App\Service\Documentation\DocumentationParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'rag:test:documentation-parsing',
    description: 'Test DocumentationCollector and DocumentationParser with configured sources',
)]
class TestDocumentationParsingCommand extends Command
{
    public function __construct(
        private readonly DocumentationCollector $collector,
        private readonly DocumentationParser $parser,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('source', 's', InputOption::VALUE_OPTIONAL, 'Specific source name to test (leave empty to test all)')
            ->addOption('limit-files', 'l', InputOption::VALUE_OPTIONAL, 'Limit number of files to process per source', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $specificSource = $input->getOption('source');
        $limitFiles = $input->getOption('limit-files') ? (int) $input->getOption('limit-files') : null;
        $io->title('Test Documentation Parsing');
        // Lire le fichier de configuration
        $configPath = $this->projectDir . '/config/documentation_sources.yaml';

        if (!file_exists($configPath)) {
            $io->error("Configuration file not found: {$configPath}");
            return Command::FAILURE;
        }
        $config = Yaml::parseFile($configPath);
        $sources = $config['documentation_sources'] ?? [];

        if (empty($sources)) {
            $io->error('No documentation sources configured in documentation_sources.yaml');
            return Command::FAILURE;
        }
        // Filtrer par source spécifique si demandé
        if ($specificSource) {
            $sources = array_filter($sources, fn($source) => $source['name'] === $specificSource);

            if (empty($sources)) {
                $io->error("Source '{$specificSource}' not found in configuration");
                return Command::FAILURE;
            }
        }
        $io->section('Configuration loaded');
        $io->writeln(sprintf('Found <info>%d</info> source(s) to process', count($sources)));

        // Afficher les sources
        $io->table(
            ['Name', 'Repository', 'Docs Path', 'Branch', 'Version'],
            array_map(fn($s) => [
                $s['name'],
                $s['repository'],
                $s['docs_path'],
                $s['branch'],
                $s['version'],
            ], $sources)
        );
        $globalStats = [
            'total_sources' => count($sources),
            'total_files' => 0,
            'total_chunks' => 0,
            'errors' => 0,
        ];

        // Traiter chaque source
        foreach ($sources as $source) {
            $io->section("Processing: {$source['name']}");

            try {
                // Étape 1 : Collecter les fichiers
                $io->writeln('📥 Collecting files from repository...');
                $collectionResult = $this->collector->collectFromRepository(
                    $source['repository'],
                    $source['docs_path'],
                    $source['branch']
                );

                $files = $collectionResult['files'];

                // Limiter le nombre de fichiers si demandé
                if ($limitFiles !== null && count($files) > $limitFiles) {
                    $io->note(sprintf('Limiting to %d files (out of %d)', $limitFiles, count($files)));
                    $files = array_slice($files, 0, $limitFiles);
                }

                $globalStats['total_files'] += count($files);

                $io->success(sprintf('Found %d markdown files', count($files)));

                // Afficher quelques fichiers
                if (!empty($files)) {
                    $displayFiles = array_slice($files, 0, 5);
                    $io->table(
                        ['Filename', 'Size'],
                        array_map(fn($file) => [
                            $file['filename'],
                            $this->formatBytes($file['size']),
                        ], $displayFiles)
                    );

                    if (count($files) > 5) {
                        $io->writeln(sprintf('  ... and %d more files', count($files) - 5));
                    }
                }
                // Étape 2 : Parser les fichiers
                $io->writeln('📝 Parsing markdown files...');

                $sourceChunks = 0;
                $progressBar = $io->createProgressBar(count($files));
                $progressBar->start();

                foreach ($files as $file) {
                    $content = file_get_contents($file['absolute_path']);

                    $chunks = $this->parser->parseMarkdownFile(
                        filePath: $file['relative_path'],
                        content: $content,
                        bundleName: $source['name'],
                        bundleVersion: $source['version'],
                        repositoryUrl: $source['repository']
                    );

                    $sourceChunks += count($chunks);
                    $progressBar->advance();
                }

                $progressBar->finish();
                $io->newLine(2);

                $globalStats['total_chunks'] += $sourceChunks;

                $io->success(sprintf(
                    '✅ %s: %d files → %d chunks',
                    $source['name'],
                    count($files),
                    $sourceChunks
                ));
                // Nettoyage
                $this->collector->cleanupTempDirectory($collectionResult['temp_path']);
            } catch (\Throwable $e) {
                $globalStats['errors']++;
                $io->error(sprintf(
                    'Failed to process %s: %s',
                    $source['name'],
                    $e->getMessage()
                ));

                if ($output->isVerbose()) {
                    $io->writeln('<error>' . $e->getTraceAsString() . '</error>');
                }

                // Essayer de nettoyer
                try {
                    $this->collector->cleanupTempDirectory();
                } catch (\Throwable $cleanupError) {
                    // Ignorer les erreurs de nettoyage
                }
            }
        }
        // Résumé global
        $io->newLine();
        $io->section('📊 Global Summary');
        $io->table(
            ['Metric', 'Value'],
            [
                ['Sources processed', $globalStats['total_sources']],
                ['Total files', $globalStats['total_files']],
                ['Total chunks created', $globalStats['total_chunks']],
                ['Errors', $globalStats['errors']],
                [
                    'Average chunks per file',
                    $globalStats['total_files'] > 0
                        ? round($globalStats['total_chunks'] / $globalStats['total_files'], 2)
                        : 0
                ],
            ]
        );
        if ($globalStats['errors'] > 0) {
            $io->warning(sprintf('%d source(s) failed to process', $globalStats['errors']));
            return Command::FAILURE;
        }
        $io->success('✅ All sources processed successfully!');
        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / 1048576, 2) . ' MB';
        }
    }
}
