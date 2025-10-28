<?php

namespace App\Command\Documentation;

use App\Repository\DocumentationRepository;
use App\Service\Documentation\DocumentationIndexer;
use App\Service\Documentation\DocumentationStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rag:index:documentation',
    description: 'Index documentation into the vector store.',
)]
class IndexDocumentationCommand extends Command
{
    public function __construct(
        private readonly DocumentationRepository $documentationRepository,
        private readonly DocumentationStore $documentationStore,
        private readonly DocumentationIndexer $documentationIndexer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Index Documentation');

        $documents = $this->documentationRepository->findAll();
        $documents = $this->documentationStore->prepareDocuments($documents);
        $this->documentationIndexer->indexDocuments($documents);

        $io->success('Documentation indexed!');

        return Command::SUCCESS;
    }
}
