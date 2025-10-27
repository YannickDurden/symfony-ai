<?php

namespace App\Service\Movies;

use App\Service\RagUtilsTrait;
use Symfony\AI\Agent\Agent;
use Symfony\AI\Agent\Toolbox\AgentProcessor;
use Symfony\AI\Agent\Toolbox\Tool\SimilaritySearch;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\AI\Platform\Exception\ExceptionInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MoviesSearch
{
    use RagUtilsTrait;

    public function __construct(
        private readonly MoviesStore $store,
        private readonly MoviesIndexer $documentIndexer,
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
