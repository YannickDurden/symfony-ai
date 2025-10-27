<?php

namespace App\Service\Image;

use Symfony\AI\Store\Indexer;
use App\Service\RagUtilsTrait;
use Symfony\AI\Store\Document\Loader\InMemoryLoader;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ImageIndexer
{
    use RagUtilsTrait;

    public function __construct(
        private readonly ImageStore $store,
        #[Autowire(env: 'OPENAI_API_KEY')]
        private readonly string $openaiApiKey,
        private readonly ImageAnalyzer $imageAnalyzer,
    ) {
    }

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
