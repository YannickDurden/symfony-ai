<?php

namespace App\Command;

use App\Service\RagStore;
use App\Service\DocumentIndexer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'rag:index:movies',
    description: 'Index movies data into the vector store.',
)]
class RagIndexMoviesCommand extends Command
{
    public function __construct(
        private readonly RagStore $store,
        private readonly DocumentIndexer $documentIndexer,
        #[Autowire('%kernel.project_dir%')] private string $rootDir,
        #[Autowire(env: 'DATA_PATH')] private readonly string $dataPath,
    ){
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $filePath = $this->rootDir . $this->dataPath . '/movies.json';

        if (!file_exists($filePath)) {
            $io->error('Movies data file not found.');

            return Command::FAILURE;
        }

        $movies = json_decode(file_get_contents($filePath), true);

        $documents = $this->store->prepareDocuments($movies);
        $this->documentIndexer->indexDocuments($documents);

        $io->success('Movies indexed!');

        return Command::SUCCESS;
    }
}
