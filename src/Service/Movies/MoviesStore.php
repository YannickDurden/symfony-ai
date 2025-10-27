<?php

namespace App\Service\Movies;

use Symfony\Component\Uid\Uuid;
use Codewithkyrian\ChromaDB\Factory;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\TextDocument;
use Symfony\AI\Store\Bridge\ChromaDb\Store as ChromaStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class MoviesStore
{
    private ChromaStore $store;

    public function __construct(
        #[Autowire(env: 'CHROMADB_HOST')] private string $chromadbHost,
        #[Autowire(env: 'CHROMADB_PORT')] private string $chromadbPort,
    ) {
        $client = (new Factory())
            ->withHost($this->chromadbHost)
            ->withPort((int) $this->chromadbPort)
            ->connect();

        $this->store = new ChromaStore(
            client: $client,
            collectionName: 'movies',
        );
    }

    public function getStore(): ChromaStore
    {
        return $this->store;
    }

    public function prepareDocuments(array $data): array
    {
        $documents = [];

        foreach ($data as $item) {
            $documents[] = new TextDocument(
                id: Uuid::v4(),
                content: $this->formatContent($item), // text to be embedded and searched
                metadata: new Metadata($this->sanitizeMetadata($item))
            );
        }

        return $documents;
    }

    private function sanitizeMetadata(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = implode(', ', $value);
            } elseif (is_scalar($value) || is_null($value)) {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    public function formatContent(array $content): string
    {
        $formatted = '';

        foreach ($content as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $formatted .= ucfirst($key) . ': ' . $value . PHP_EOL;
        }

        return trim($formatted);
    }
}
