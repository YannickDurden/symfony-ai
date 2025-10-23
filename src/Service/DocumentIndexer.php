<?php

namespace App\Service;

use Symfony\AI\Store\Indexer;
use Symfony\AI\Store\Document\Loader\InMemoryLoader;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class DocumentIndexer
{
    use RagUtilsTrait;

    public function __construct(
        private readonly RagStore $store,
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
