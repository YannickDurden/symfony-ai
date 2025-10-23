<?php

namespace App\Service;

use Symfony\AI\Agent\Agent;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\AI\Platform\Exception\ExceptionInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Agent\Toolbox\AgentProcessor;
use Symfony\AI\Agent\Toolbox\Tool\SimilaritySearch;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class RagSearch
{
    use RagUtilsTrait;

    public function __construct(
        private readonly RagStore $store,
        private readonly DocumentIndexer $documentIndexer,
        #[Autowire(env: 'OPENAI_API_KEY')]
        private readonly string $openaiApiKey,
    ) {}

    public function initSimilaritySearch(): AgentProcessor
    {
        $similaritySearch = new SimilaritySearch(
            vectorizer: $this->vectorizer(),
            store: $this->store->getStore()
        );

        $toolbox = new Toolbox([$similaritySearch]);

        return new AgentProcessor($toolbox);
    }

    public function createAgent(): Agent
    {
        $processor = $this->initSimilaritySearch();
        $platform  = $this->openAiPlatform();

        return new Agent(
            platform: $platform,
            model: 'gpt-4o-mini',
            inputProcessors: [$processor],
            outputProcessors: [$processor]
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function query(string $question): string
    {
        $messages = new MessageBag(
            Message::forSystem('Please answer all user questions only using SimilaritySearch function.'),
            Message::ofUser($question)
        );

        return $this->createAgent()
            ->call($messages)
            ->getContent();
    }
}
