<?php

namespace App\Service\Image;

use App\Entity\Image;
use App\Service\RagUtilsTrait;
use App\Repository\ImageRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ImageSearch
{
    use RagUtilsTrait;

    public function __construct(
        private readonly ImageStore $store,
        #[Autowire(env: 'OPENAI_API_KEY')]
        private readonly string $openaiApiKey,
        private readonly ImageIndexer $imageIndexer,
        private readonly ImageRepository $imageRepository,
    ) {
    }

    /**
     * Recherche des images par description sémantique
     *
     * @return Image[]
     */
    public function query(string $question): array
    {
        // Recherche vectorielle dans ChromaDB
        $documents = $this->store->getStore()->query(
            vector: $this->vectorizer()->vectorize($question),
            options: ['nResults' => 12]
        );

        $images = [];
        foreach ($documents as $document) {
            $postgresId = $document->metadata['postgres_id'] ?? null;
            if (null === $postgresId) {
                continue;
            }
            $image = $this->imageRepository->find($postgresId);
            if (null === $image) {
                continue;
            }

            $images[] = $image;
        }

        return $images;
    }
}
