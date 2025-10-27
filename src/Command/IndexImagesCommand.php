<?php

namespace App\Command;

use App\Repository\ImageRepository;
use App\Service\Image\ImageIndexer;
use App\Service\Image\ImageStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rag:index:images',
    description: 'Index images data into the vector store.',
)]
class IndexImagesCommand extends Command
{
    public function __construct(
        private readonly ImageStore $store,
        private readonly ImageIndexer $imageIndexer,
        private readonly ImageRepository $imageRepository,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Indexing images...');

        $images = $this->imageRepository->findAll();
        $documents = $this->store->prepareDocuments($images);
        $this->imageIndexer->indexDocuments($documents);

        $io->success('Images indexed!');

        return Command::SUCCESS;
    }
}
