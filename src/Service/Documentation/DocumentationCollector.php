<?php

namespace App\Service\Documentation;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class DocumentationCollector
{
   public function __construct(
       private readonly LoggerInterface $logger,
       #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
       #[Autowire(env: 'DOCUMENTATION_TEMP_DIR')] private readonly string $tempDir,
   ) {
   }

   public function collectFromRepository(string $repoUrl, string $docsPath, string $branch): array
   {
       // Créer un nom unique pour le répertoire temporaire
       $repoName = basename($repoUrl, '.git');
       $tempRepoPath = $this->tempDir . '/' . $repoName . '_' . time();
       // Créer le répertoire temporaire s'il n'existe pas
       if (!is_dir($this->tempDir)) {
           mkdir($this->tempDir, 0755, true);
       }

       try {
           // Cloner le repository
           $this->logger->info("Cloning repository: {$repoUrl}");
           $command = sprintf(
               'git clone --depth 1 --branch %s %s %s 2>&1',
               escapeshellarg($branch),
               escapeshellarg($repoUrl),
               escapeshellarg($tempRepoPath)
           );

           exec($command, $output, $returnCode);

           if ($returnCode !== 0) {
               throw new \RuntimeException(
                   "Failed to clone repository: " . implode("\n", $output)
               );
           }

           // Construire le chemin complet vers la documentation
           $fullDocsPath = $tempRepoPath . '/' . trim($docsPath, '/');

           if (!is_dir($fullDocsPath)) {
               throw new \RuntimeException(
                   "Documentation path not found: {$fullDocsPath}"
               );
           }

           // Récupérer les fichiers Markdown
           $files = $this->getMarkdownFiles($fullDocsPath);

           $this->logger->info(sprintf("Found %d markdown files", count($files)));

           return [
               'repository_url' => $repoUrl,
               'temp_path' => $tempRepoPath,
               'docs_path' => $fullDocsPath,
               'files' => $files,
           ];

       } catch (\Throwable $e) {
           // Nettoyer en cas d'erreur
           if (is_dir($tempRepoPath)) {
               $this->cleanupDirectory($tempRepoPath);
           }
           throw $e;
       }
   }

   public function getMarkdownFiles(string $path): array
   {
       $files = [];

       if (!is_dir($path)) {
           return $files;
       }

       // Utiliser RecursiveDirectoryIterator pour parcourir récursivement
       $iterator = new \RecursiveIteratorIterator(
           new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
           \RecursiveIteratorIterator::SELF_FIRST
       );

       foreach ($iterator as $file) {
           /** @var \SplFileInfo $file */
           if ($file->isFile() && strtolower($file->getExtension()) === 'md') {
               $files[] = [
                   'absolute_path' => $file->getRealPath(),
                   'relative_path' => str_replace($path . '/', '', $file->getRealPath()),
                   'filename' => $file->getFilename(),
                   'size' => $file->getSize(),
               ];
           }
       }

       return $files;
   }

   public function cleanupTempDirectory(?string $path = null): void
   {
       $pathToClean = $path ?? $this->tempDir;

       if (!is_dir($pathToClean)) {
           return;
       }

       $this->logger->info("Cleaning up directory: {$pathToClean}");

       try {
           $this->cleanupDirectory($pathToClean);
       } catch (\Throwable $e) {
           $this->logger->error("Failed to cleanup directory: " . $e->getMessage());
       }
   }

   private function cleanupDirectory(string $dir): void
   {
       if (!is_dir($dir)) {
           return;
       }

       $items = new \RecursiveIteratorIterator(
           new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
           \RecursiveIteratorIterator::CHILD_FIRST
       );

       foreach ($items as $item) {
           if ($item->isDir()) {
               rmdir($item->getRealPath());
           } else {
               unlink($item->getRealPath());
           }
       }

       rmdir($dir);
   }
}
