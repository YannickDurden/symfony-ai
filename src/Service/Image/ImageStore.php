<?php

namespace App\Service\Image;

use App\Entity\Image;
use Codewithkyrian\ChromaDB\Factory;
use Symfony\AI\Store\Bridge\ChromaDb\Store as ChromaStore;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\TextDocument;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

readonly class ImageStore
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
            collectionName: 'images',
        );
    }

    public function getStore(): ChromaStore
    {
        return $this->store;
    }

    /**
     * @param array<Image> $images
     *
     * @return array<TextDocument>
     */
    public function prepareDocuments(array $images): array
    {
        return array_map(
            static fn (Image $image) => new TextDocument(
                id: Uuid::fromString($image->getUuid()),
                content: $image->getDescription(),
                metadata: new Metadata([
                    'filename' => $image->getFilename(),
                    'path' => $image->getPath(),
                    'postgres_id' => $image->getId(),
                ])
            ),
            $images
        );
    }
}
