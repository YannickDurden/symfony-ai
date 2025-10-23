<?php

namespace App\Service;

use Symfony\AI\Platform\Bridge\OpenAi\PlatformFactory;
use Symfony\AI\Platform\Platform;
use Symfony\AI\Store\Document\Vectorizer;

trait RagUtilsTrait
{
    public function openAiPlatform(): Platform
    {
        if (!isset($this->openaiApiKey)) {
            throw new \RuntimeException('OpenAI API key not set.');
        }
        
        return PlatformFactory::create(apiKey: $this->openaiApiKey);
    }

    public function vectorizer(): Vectorizer
    {
        $platform = $this->openAiPlatform();

        return new Vectorizer(
            platform: $platform,
            model: 'text-embedding-3-small'
        );
    }
}
