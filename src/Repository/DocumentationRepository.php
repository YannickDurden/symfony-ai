<?php

namespace App\Repository;

use App\Entity\Documentation;
use Symfony\Component\Uid\Uuid;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Documentation>
 */
class DocumentationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Documentation::class);
    }

    /**
     * Sauvegarde un tableau de chunks dans la base de données
     *
     * @param array<array> $chunks Tableau de chunks à sauvegarder
     */
    public function saveChunks(array $chunks): void
    {
        foreach ($chunks as $chunk) {
            $documentation = Documentation::createFromChunk($chunk);
            $this->getEntityManager()->persist($documentation);
        }

        $this->getEntityManager()->flush();
    }
}
