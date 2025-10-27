<?php

namespace App\Twig\Components;

use App\Entity\Image;
use App\Service\Image\ImageSearch;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(template: 'components/SearchImages.html.twig')]
final class SearchImages
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp(writable: true)]
    #[Assert\NotBlank(message: 'La recherche ne peut pas être vide.')]
    public string $query = '';

    /** @var array<Image> */
    #[LiveProp]
    public array $images = [];

    #[LiveProp]
    public bool $isLoading = false;

    #[LiveProp]
    public bool $hasSearched = false;

    #[LiveAction]
    public function search(ImageSearch $imageSearch): void
    {
        $this->validate();

        $this->isLoading = true;
        $this->hasSearched = false;

        try {
            $this->images = $imageSearch->query($this->query);
            $this->hasSearched = true;
        } catch (\Throwable $e) {
            $this->images = [];
            $this->hasSearched = true;
        } finally {
            $this->isLoading = false;
        }
    }

    #[LiveAction]
    public function reset(): void
    {
        $this->query = '';
        $this->images = [];
        $this->isLoading = false;
        $this->hasSearched = false;
        $this->clearValidation();
    }
}

