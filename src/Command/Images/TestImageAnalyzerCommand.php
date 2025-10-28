<?php

namespace App\Command\Images;

use App\Service\Image\ImageAnalyzer;
use Symfony\AI\Platform\Exception\ExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'test:image:analyzer',
    description: 'Test l\'analyse d\'une image avec GPT-4o-mini.',
)]
class TestImageAnalyzerCommand extends Command
{
    public function __construct(
        private readonly ImageAnalyzer $imageAnalyzer,
    ){
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('image-path', InputArgument::REQUIRED, 'Chemin vers l\'image à analyser');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $imagePath = $input->getArgument('image-path');

        if (!file_exists($imagePath)) {
            $io->error("L'image n'existe pas : {$imagePath}");
            return Command::FAILURE;
        }

        $io->title('Test de l\'analyseur d\'images avec GPT-4 Vision');
        $io->info("Analyse de l'image : {$imagePath}");
        $io->newLine();

        try {
            $io->section('Envoi de l\'image à GPT-4...');

            $description = $this->imageAnalyzer->analyze($imagePath);

            $io->success('Image analysée avec succès !');
            $io->newLine();

            $io->section('Description générée :');
            $io->writeln($description);
            $io->newLine();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'analyse : ' . $e->getMessage());
            return Command::FAILURE;
        } catch (ExceptionInterface $e) {
            $io->error('Erreur lors de l\'analyse : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

