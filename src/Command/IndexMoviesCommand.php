<?php

namespace App\Command;

use App\Service\Movies\MoviesIndexer;
use App\Service\Movies\MoviesStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'rag:index:movies',
    description: 'Index movies data into the vector store.',
)]
class IndexMoviesCommand extends Command
{
    public function __construct(
        private readonly MoviesStore $store,
        private readonly MoviesIndexer $moviesIndexer,
        #[Autowire('%kernel.project_dir%')] private readonly string $rootDir,
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

        $movies = json_decode(file_get_contents($filePath), true, 512, JSON_THROW_ON_ERROR);

        $documents = $this->store->prepareDocuments($movies);
        $this->moviesIndexer->indexDocuments($documents);

        $io->success('Movies indexed!');

        return Command::SUCCESS;
    }
}
