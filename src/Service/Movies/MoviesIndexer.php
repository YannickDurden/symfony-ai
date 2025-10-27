<?php

namespace App\Service\Movies;

use Symfony\AI\Store\Indexer;
use App\Service\RagUtilsTrait;
use Symfony\AI\Store\Document\Loader\InMemoryLoader;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MoviesIndexer
{
    use RagUtilsTrait;

    public function __construct(
        private readonly MoviesStore $store,
        #[Autowire(env: 'OPENAI_API_KEY')]
        private readonly string $openaiApiKey,
    ) {}

    public function indexDocuments(array $documents): void
    {
        $indexer = $this->indexer($documents);
        $indexer->index($documents);
    }

    public function indexer(array $documents): Indexer
    {
        return new Indexer(
            loader: new InMemoryLoader($documents),
            vectorizer: $this->vectorizer(),
            store: $this->store->getStore()
        );
    }
}
