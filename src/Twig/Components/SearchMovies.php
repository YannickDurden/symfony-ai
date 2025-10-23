<?php

namespace App\Twig\Components;

use App\Service\RagSearch;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\Component\Validator\Constraints as Assert;

#[AsLiveComponent(template: 'components/SearchMovies.html.twig')]
final class SearchMovies
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp(writable: true)]
    #[Assert\NotBlank(message: 'La question ne peut pas être vide.')]
    public string $question = '';

    #[LiveProp]
    public string $answer = '';

    #[LiveProp]
    public bool $isLoading = false;

    #[LiveAction]
    public function search(RagSearch $ragSearch): void
    {
        $this->validate();

        $this->isLoading = true;

        try {
            // $this->answer = $ragSearch->query($this->question);
            $this->answer = 'test';
        } catch (\Exception $e) {
            $this->answer = 'Une erreur est survenue lors de la recherche. Veuillez réessayer.';
        } finally {
            $this->isLoading = false;
        }
    }

    #[LiveAction]
    public function reset(): void
    {
        $this->question = '';
        $this->answer = '';
        $this->isLoading = false;
        $this->clearValidation();
    }
}
