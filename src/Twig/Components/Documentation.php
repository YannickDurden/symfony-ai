<?php

namespace App\Twig\Components;

use App\Entity\Documentation as DocumentationEntity;
use App\Service\Documentation\DocumentationSearch;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(template: 'components/Documentation.html.twig')]
final class Documentation
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp(writable: true)]
    #[Assert\NotBlank(message: 'La recherche ne peut pas être vide.')]
    public string $query = '';

    #[LiveProp]
    public string $synthesis = '';

    /** @var array<DocumentationEntity> */
    #[LiveProp]
    public array $results = [];

    #[LiveProp]
    public bool $isLoading = false;

    #[LiveProp]
    public bool $hasSearched = false;

    #[LiveAction]
    public function search(DocumentationSearch $documentationSearch): void
    {
        $this->validate();

        $this->isLoading = true;
        $this->hasSearched = false;

        try {
            $this->results = $documentationSearch->query($this->query);
            $this->synthesis = $documentationSearch->queryWithAgent($this->query);
            $this->hasSearched = true;
        } catch (\Throwable $e) {
            $this->results = [];
            $this->hasSearched = true;
        } finally {
            $this->isLoading = false;
        }
    }

    #[LiveAction]
    public function reset(): void
    {
        $this->query = '';
        $this->synthesis = '';
        $this->results = [];
        $this->isLoading = false;
        $this->hasSearched = false;
        $this->clearValidation();
    }
}
