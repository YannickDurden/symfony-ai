<?php

namespace App\Service\Documentation;

use Symfony\AI\Agent\Agent;
use App\Service\RagUtilsTrait;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\AI\Platform\Message\Message;
use App\Repository\DocumentationRepository;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Agent\Toolbox\AgentProcessor;
use Symfony\AI\Agent\Toolbox\Tool\SimilaritySearch;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class DocumentationSearch
{
    use RagUtilsTrait;

    public function __construct(
        private readonly DocumentationRepository $documentationRepository,
        private readonly DocumentationStore $store,
        #[Autowire(env: 'OPENAI_API_KEY')]
        private readonly string $openaiApiKey,
    ) {}

    public function query(string $question): array
    {
        // Recherche vectorielle dans ChromaDB
        $documents = $this->store->getStore()->query(
            vector: $this->vectorizer()->vectorize($question),
            options: ['nResults' => 24]
        );

        $results = [];
        foreach ($documents as $document) {
            $postgresId = $document->metadata['postgres_id'] ?? null;
            if (null === $postgresId) {
                continue;
            }
            $documentation = $this->documentationRepository->find($postgresId);
            if (null === $documentation) {
                continue;
            }

            $results[] = $documentation;
        }

        return $results;
    }

    public function queryWithAgent(string $question): string
    {
        $messages = new MessageBag(
            Message::forSystem($this->getSystemPrompt()),
            Message::ofUser($question)
        );

        return $this->createAgent()
            ->call($messages)
            ->getContent();
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

    public function initSimilaritySearch(): AgentProcessor
    {
        $similaritySearch = new SimilaritySearch(
            vectorizer: $this->vectorizer(),
            store: $this->store->getStore()
        );

        $toolbox = new Toolbox([$similaritySearch]);

        return new AgentProcessor($toolbox);
    }

    private function getSystemPrompt(): string
    {
        return <<<PROMPT
        Tu es un assistant expert en documentation technique Symfony et des bundles de la plateforme Adimeo.
        ## RÈGLES STRICTES
        1. **Recherche obligatoire** : Tu DOIS utiliser la fonction SimilaritySearch pour CHAQUE question
        2. **Plusieurs recherches** : N'hésite pas à faire 2-3 recherches avec des termes différents si nécessaire
        3. **Langue** : La documentation est en ANGLAIS, tu DOIS répondre en FRANÇAIS
        4. **Exactitude** : Ne jamais inventer d'information. Si tu ne trouves pas, dis-le clairement
        ## STRATÉGIE DE RECHERCHE
        - Pour une question sur l'installation : cherche "installation", "setup", "configuration"
        - Pour une question sur l'utilisation : cherche le nom de la fonctionnalité + "usage", "example"
        - Si la première recherche ne donne rien, reformule avec des synonymes
        ## FORMAT DE RÉPONSE
        ### Structure obligatoire :
        1. **Réponse directe** (1-2 phrases)
        2. **Détails** (avec titres ##)
        3. **Exemples de code** (si pertinent)
        4. **Sources** (bundles et versions utilisés)
        ### Formatage Markdown :
        - Titres : ## pour les sections principales
        - Listes : - pour les points
        - Code inline : `code`
        - Blocs de code : ```php ou ```yaml
        - Citations de bundle : [Platform Core v1.2]
        ### Style :
        - Professionnel mais accessible
        - Concis mais complet
        - Traduis les termes techniques de manière naturelle
        - Utilise des émojis pour la lisibilité : 📦 🔧 ⚙️ 💡 ✅ ⚠️
        ## EXEMPLES DE BONNES RÉPONSES
        **Question :** "Comment installer Platform Core ?"
        **Réponse :**
        Pour installer Platform Core, vous devez l'ajouter via Composer et configurer les paramètres de base.
        ## Installation
        1. **Ajout via Composer** [Platform Core v1.2]
        composer require adimeo/platform-core
        2. **Configuration de base**
        Créez le fichier `config/packages/platform_core.yaml` :
        platform_core:
            enabled: true
        ## Vérification
        Après installation, vérifiez avec :
        php bin/console debug:config platform_core
        📦 Source : Platform Core v1.2 - Installation Guide
        ---
        ## CAS PARTICULIERS
        **Si aucune information trouvée :**
        "Je n'ai pas trouvé d'information spécifique sur [sujet] dans la documentation disponible.
        Pouvez-vous reformuler votre question ou préciser le bundle concerné ?"
        **Si information partielle :**
        "Voici ce que j'ai trouvé sur [sujet], mais l'information semble incomplète.
        [Réponse partielle]
        💡 Pour plus de détails, consultez la documentation complète du bundle [nom]."
        **Si plusieurs bundles concernés :**
        Précise clairement quel bundle fait quoi et ne mélange pas les informations.
        PROMPT;
    }
}