<?php

namespace App\Service\Documentation;

use App\Entity\Documentation;
use Codewithkyrian\ChromaDB\Factory;
use Symfony\Component\Uid\Uuid;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\TextDocument;
use Symfony\AI\Store\Bridge\ChromaDb\Store as ChromaStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class DocumentationStore
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
            collectionName: 'documentation',
        );
    }

    public function getStore(): ChromaStore
    {
        return $this->store;
    }

    /**
     * @param array<Documentation> $data
     *
     * @return array<TextDocument>
     */
    public function prepareDocuments(array $documentations): array
    {
        return array_map(
            static fn (Documentation $documentation) => new TextDocument(
                id: Uuid::v4(),
                content: self::enrichContent($documentation),
                metadata: new Metadata($documentation->getMetadata())
            ),
            $documentations
        );
    }

    private static function enrichContent(Documentation $doc): string
    {
       // Ajouter le contexte au début du contenu
       $context = sprintf(
           "[Bundle: %s v%s | Section: %s | File: %s]\n\n",
           $doc->getBundleName(),
           $doc->getBundleVersion(),
           $doc->getSectionTitle(),
           $doc->getFilePath()
       );

       return $context . $doc->getContent();
    }
}