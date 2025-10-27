<?php

namespace App\Service\Image;

use App\Service\RagUtilsTrait;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\Content\Image;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ImageAnalyzer
{
    use RagUtilsTrait;

    public function __construct(
        #[Autowire(env: 'OPENAI_API_KEY')]
        private readonly string $openaiApiKey,
    ) {}

    /**
     * Analyse une image avec GPT-4o-mini et retourne une description détaillée
     */
    public function analyze(string|File $image): ?string
    {
        $imagePath = $image instanceof File ? $image->getPathname() : $image;

        if (!file_exists($imagePath)) {
            throw new \RuntimeException("Image file not found: {$imagePath}");
        }

        $platform = $this->openAiPlatform();
        $messages = new MessageBag(
            Message::forSystem($this->getForSystemPrompt()),
            Message::ofUser(
                $this->getAnalysisPrompt(),
                Image::fromFile($imagePath)
            )
        );

        try {
            return $platform
                ->invoke('gpt-4o-mini', $messages)
                ->asText();
        } catch (\Throwable $throwable) {
            return null;
        }
    }

    private function getForSystemPrompt(): string
    {
        return <<<PROMPT
        Tu es un expert en analyse d'images qui aide à identifier le contenu d\'une image.
        PROMPT;
    }

    /**
     * Retourne le prompt optimisé pour l'analyse d'images
     */
    private function getAnalysisPrompt(): string
    {
        return <<<PROMPT
        Analyse cette image en détail pour permettre une recherche sémantique efficace.
        
        Fournis une description structurée incluant :
        
        1. **Personnes** : Nombre, apparence approximative (âge, genre si visible), vêtements, actions qu'elles effectuent
        2. **Objets** : Liste tous les objets visibles et leur utilisation (ex: verre, bouteille, téléphone, etc.)
        3. **Contexte et lieu** : Type d'endroit (intérieur/extérieur, restaurant, bureau, parc, etc.)
        4. **Actions** : Que se passe-t-il dans l'image ? Que font les personnes ?
        5. **Ambiance** : Atmosphère générale (festive, professionnelle, calme, etc.)
        6. **Couleurs dominantes** : Principales couleurs présentes
        
        Sois précis et factuel. Utilise un langage descriptif qui facilitera la recherche par mots-clés.
        Ne fais pas d'interprétation subjective, reste descriptif.
        
        Format ta réponse de manière fluide et naturelle, pas en liste à puces.
        PROMPT;
    }
}
