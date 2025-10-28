<?php

namespace App\Service\Documentation;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class DocumentationParser
{
    public function __construct(
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'DOCUMENTATION_CHUNK_SIZE')] private readonly int $chunkSize,
        #[Autowire(env: 'DOCUMENTATION_CHUNK_OVERLAP')] private readonly int $chunkOverlap,
    ) {
    }

    /**
     * Parse un fichier Markdown et retourne un tableau de chunks
     *
     * @param string $filePath Chemin relatif du fichier (ex: "installation.md")
     * @param string $content Contenu du fichier Markdown
     * @param string $bundleName Nom du bundle
     * @param string $bundleVersion Version du bundle
     * @param string $repositoryUrl URL du repository
     * @return array Tableau de chunks prêts pour l'entité Documentation
     */
    public function parseMarkdownFile(
        string $filePath,
        string $content,
        string $bundleName,
        string $bundleVersion,
        string $repositoryUrl
    ): array {
        $this->logger->info("Parsing file: {$filePath}");

        // Extraire les sections du fichier
        $sections = $this->extractSections($content);

        if (empty($sections)) {
            // Si pas de sections, traiter le fichier entier comme une section
            $sections = [
                [
                    'title' => basename($filePath, '.md'),
                    'content' => $content,
                    'level' => 1,
                ]
            ];
        }

        // Créer les chunks à partir des sections
        $chunks = $this->createChunks($sections);

        // Préparer les données pour l'entité Documentation
        $documentationData = [];
        foreach ($chunks as $chunk) {
            $documentationData[] = [
                'bundle_name' => $bundleName,
                'bundle_version' => $bundleVersion,
                'file_path' => $filePath,
                'section_title' => $chunk['title'],
                'repository_url' => $repositoryUrl,
                'content' => $chunk['content'],
            ];
        }

        $this->logger->info(sprintf("Created %d chunks from file: {$filePath}", count($documentationData)));

        return $documentationData;
    }

    /**
     * Extrait les sections d'un contenu Markdown basé sur les titres H2 et H3
     *
     * @param string $content Contenu Markdown
     * @return array Tableau de sections avec titre, contenu et niveau
     */
    public function extractSections(string $content): array
    {
        $sections = [];
        $lines = explode("\n", $content);
        $currentSection = null;
        $currentContent = [];

        foreach ($lines as $line) {
            // Détecter les titres H2 (## Titre) et H3 (### Titre)
            if (preg_match('/^(#{2,3})\s+(.+)$/', $line, $matches)) {
                // Sauvegarder la section précédente si elle existe
                if ($currentSection !== null && !empty($currentContent)) {
                    $sections[] = [
                        'title' => $currentSection['title'],
                        'content' => trim(implode("\n", $currentContent)),
                        'level' => $currentSection['level'],
                    ];
                }

                // Commencer une nouvelle section
                $level = strlen($matches[1]); // 2 pour ##, 3 pour ###
                $title = trim($matches[2]);

                $currentSection = [
                    'title' => $title,
                    'level' => $level,
                ];
                $currentContent = [$line]; // Inclure le titre dans le contenu
            } else {
                // Ajouter la ligne au contenu de la section courante
                if ($currentSection !== null) {
                    $currentContent[] = $line;
                } else {
                    // Contenu avant le premier titre (introduction)
                    if (!isset($sections[0])) {
                        $sections[0] = [
                            'title' => 'Introduction',
                            'content' => '',
                            'level' => 1,
                        ];
                    }
                    $sections[0]['content'] .= $line . "\n";
                }
            }
        }

        // Ajouter la dernière section
        if ($currentSection !== null && !empty($currentContent)) {
            $sections[] = [
                'title' => $currentSection['title'],
                'content' => trim(implode("\n", $currentContent)),
                'level' => $currentSection['level'],
            ];
        }

        return $sections;
    }

    /**
     * Crée des chunks à partir des sections en respectant la taille maximale
     *
     * @param array $sections Tableau de sections
     * @param int|null $maxSize Taille maximale d'un chunk (null = utilise la config)
     * @return array Tableau de chunks
     */
    public function createChunks(array $sections, ?int $maxSize = null): array
    {
        $maxSize = $maxSize ?? $this->chunkSize;
        $chunks = [];

        foreach ($sections as $section) {
            $sectionTitle = $section['title'];
            $sectionContent = $section['content'];
            $contentLength = strlen($sectionContent);

            // Si la section est plus petite que la taille max, créer un seul chunk
            if ($contentLength <= $maxSize) {
                $chunks[] = [
                    'title' => $sectionTitle,
                    'content' => $sectionContent,
                ];
                continue;
            }

            // Sinon, découper la section en plusieurs chunks avec overlap
            $subChunks = $this->splitLargeSection($sectionTitle, $sectionContent, $maxSize);
            $chunks = array_merge($chunks, $subChunks);
        }

        return $chunks;
    }

    /**
     * Découpe une grande section en plusieurs chunks avec overlap
     *
     * @param string $title Titre de la section
     * @param string $content Contenu de la section
     * @param int $maxSize Taille maximale d'un chunk
     * @return array Tableau de chunks
     */
    private function splitLargeSection(string $title, string $content, int $maxSize): array
    {
        $chunks = [];
        $paragraphs = $this->splitIntoParagraphs($content);
        $currentChunk = '';
        $chunkIndex = 1;

        foreach ($paragraphs as $paragraph) {
            $paragraphLength = strlen($paragraph);

            // Si le paragraphe seul dépasse la taille max, le forcer dans un chunk
            if ($paragraphLength > $maxSize) {
                // Sauvegarder le chunk courant s'il existe
                if (!empty($currentChunk)) {
                    $chunks[] = [
                        'title' => $title . " (partie {$chunkIndex})",
                        'content' => trim($currentChunk),
                    ];
                    $chunkIndex++;
                }

                // Découper le paragraphe en morceaux
                $pieces = $this->splitLongParagraph($paragraph, $maxSize);
                foreach ($pieces as $piece) {
                    $chunks[] = [
                        'title' => $title . " (partie {$chunkIndex})",
                        'content' => trim($piece),
                    ];
                    $chunkIndex++;
                }

                $currentChunk = '';
                continue;
            }

            // Si ajouter ce paragraphe dépasse la taille max
            if (strlen($currentChunk) + $paragraphLength > $maxSize) {
                // Sauvegarder le chunk courant
                $chunks[] = [
                    'title' => $title . " (partie {$chunkIndex})",
                    'content' => trim($currentChunk),
                ];
                $chunkIndex++;

                // Commencer un nouveau chunk avec overlap
                $currentChunk = $this->getOverlapText($currentChunk) . "\n\n" . $paragraph;
            } else {
                // Ajouter le paragraphe au chunk courant
                $currentChunk .= ($currentChunk ? "\n\n" : '') . $paragraph;
            }
        }

        // Ajouter le dernier chunk
        if (!empty($currentChunk)) {
            $chunks[] = [
                'title' => $title . ($chunkIndex > 1 ? " (partie {$chunkIndex})" : ''),
                'content' => trim($currentChunk),
            ];
        }

        return $chunks;
    }

    /**
     * Découpe le contenu en paragraphes
     *
     * @param string $content Contenu à découper
     * @return array Tableau de paragraphes
     */
    private function splitIntoParagraphs(string $content): array
    {
        // Découper par double saut de ligne (paragraphes)
        $paragraphs = preg_split('/\n\s*\n/', $content);

        return array_filter(array_map('trim', $paragraphs));
    }

    /**
     * Découpe un long paragraphe en morceaux
     *
     * @param string $paragraph Paragraphe à découper
     * @param int $maxSize Taille maximale
     * @return array Tableau de morceaux
     */
    private function splitLongParagraph(string $paragraph, int $maxSize): array
    {
        $pieces = [];
        $sentences = preg_split('/(?<=[.!?])\s+/', $paragraph);

        $currentPiece = '';
        foreach ($sentences as $sentence) {
            if (strlen($currentPiece) + strlen($sentence) > $maxSize) {
                if (!empty($currentPiece)) {
                    $pieces[] = $currentPiece;
                }
                $currentPiece = $sentence;
            } else {
                $currentPiece .= ($currentPiece ? ' ' : '') . $sentence;
            }
        }

        if (!empty($currentPiece)) {
            $pieces[] = $currentPiece;
        }

        return $pieces;
    }

    /**
     * Récupère le texte d'overlap (fin du chunk précédent)
     *
     * @param string $text Texte complet
     * @return string Texte d'overlap
     */
    private function getOverlapText(string $text): string
    {
        if (strlen($text) <= $this->chunkOverlap) {
            return $text;
        }

        // Prendre les derniers caractères en essayant de couper à un espace
        $overlap = substr($text, -$this->chunkOverlap);
        $spacePos = strpos($overlap, ' ');

        if ($spacePos !== false) {
            return substr($overlap, $spacePos + 1);
        }

        return $overlap;
    }
}

