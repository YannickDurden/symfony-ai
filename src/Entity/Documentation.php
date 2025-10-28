<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\DocumentationRepository;

#[ORM\Entity(repositoryClass: DocumentationRepository::class)]
class Documentation
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid')]
    private ?Uuid $uuid = null;

    #[ORM\Column(length: 255)]
    private ?string $bundleName = null;

    #[ORM\Column(length: 255)]
    private ?string $bundleVersion = null;

    #[ORM\Column(length: 255)]
    private ?string $filePath = null;

    #[ORM\Column(length: 255)]
    private ?string $sectionTitle = null;

    #[ORM\Column(length: 255)]
    private ?string $repositoryUrl = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getBundleName(): ?string
    {
        return $this->bundleName;
    }

    public function setBundleName(string $bundleName): static
    {
        $this->bundleName = $bundleName;

        return $this;
    }

    public function getBundleVersion(): ?string
    {
        return $this->bundleVersion;
    }

    public function setBundleVersion(string $bundleVersion): static
    {
        $this->bundleVersion = $bundleVersion;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getSectionTitle(): ?string
    {
        return $this->sectionTitle;
    }

    public function setSectionTitle(string $sectionTitle): static
    {
        $this->sectionTitle = $sectionTitle;

        return $this;
    }

    public function getRepositoryUrl(): ?string
    {
        return $this->repositoryUrl;
    }

    public function setRepositoryUrl(string $repositoryUrl): static
    {
        $this->repositoryUrl = $repositoryUrl;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getMetadata(): array
    {
        return [
            'bundle_name'    => $this->getBundleName(),
            'bundle_version' => $this->getBundleVersion(),
            'file_path'      => $this->getFilePath(),
            'section_title'  => $this->getSectionTitle(),
            'repository_url' => $this->getRepositoryUrl(),
            'postgres_id'    => $this->getId(),
        ];
    }

    /**
     * @param array{
     *     bundle_name: string,
     *     bundle_version: string,
     *     file_path: string,
     *     section_title: string,
     *     repository_url: string,
     *     content: string,
     * } $chunk
     */
    public static function createFromChunk(array $chunk): self
    {
        $documentation = new self();
        $documentation
            ->setBundleName($chunk['bundle_name'])
            ->setBundleVersion($chunk['bundle_version'])
            ->setFilePath($chunk['file_path'])
            ->setSectionTitle($chunk['section_title'])
            ->setRepositoryUrl($chunk['repository_url'])
            ->setContent($chunk['content'])
            ->setUuid(Uuid::v4());

        return $documentation;
    }
}
