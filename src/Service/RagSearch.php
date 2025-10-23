<?php

namespace App\Service;

use Symfony\AI\Agent\Agent;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Agent\Toolbox\AgentProcessor;
use Symfony\AI\Agent\Toolbox\Tool\SimilaritySearch;
use Symfony\AI\Platform\Bridge\OpenAi\PlatformFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class RagSearch
{
    public function __construct(
        private readonly RagStore $store,
        private readonly DocumentIndexer $documentIndexer,
        #[Autowire(env: 'OPENAI_API_KEY')]
        private string $openaiApiKey,
    ) {}

    public function initSimilaritySearch(): AgentProcessor
    {
        $similaritySearch = new SimilaritySearch(
            vectorizer: $this->documentIndexer->vectorizer(),
            store: $this->store->getStore()
        );

        $toolbox   = new Toolbox([$similaritySearch]);
        $processor = new AgentProcessor($toolbox);

        return $processor;
    }

    public function createAgent(): Agent
    {
        $processor = $this->initSimilaritySearch();
        $platform  = PlatformFactory::create(apiKey: $this->openaiApiKey);

        return new Agent(
            platform: $platform,
            model: 'gpt-4o-mini',
            inputProcessors: [$processor],
            outputProcessors: [$processor]
        );
    }

    public function query(string $question): string
    {
        $messages = new MessageBag(
            Message::forSystem('Please answer all user questions only using SimilaritySearch function.'),
            Message::ofUser($question)
        );

        $result = $this->createAgent()->call($messages);

        return $result->getContent();
    }
}
