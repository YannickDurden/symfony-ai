<?php

namespace App\Service;

use Symfony\AI\Store\Indexer;
use Symfony\AI\Store\Document\Vectorizer;
use Symfony\AI\Store\Document\Loader\InMemoryLoader;
use Symfony\AI\Platform\Bridge\OpenAi\PlatformFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class DocumentIndexer
{
    public function __construct(
        private RagStore $store,
        #[Autowire(env: 'OPENAI_API_KEY')]
        private string $openaiApiKey,
    ) {}

    public function indexDocuments(array $documents): void
    {
        $indexer = $this->indexer($documents);
        $indexer->index($documents);
    }

    public function vectorizer(): Vectorizer
    {
        $platform = PlatformFactory::create(apiKey: $this->openaiApiKey);

        return new Vectorizer(
            platform: $platform,
            model: 'text-embedding-3-small'
        );
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
