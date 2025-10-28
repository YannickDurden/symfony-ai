<?php

namespace App\Command\Images;

use App\Entity\Image;
use App\Repository\ImageRepository;
use App\Service\Image\ImageAnalyzer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'rag:generate:image:description',
    description: 'Generate description for images with GPT-4o-mini',
)]
class GenerateImagesDescriptionCommand extends Command
{
    public function __construct(
        private readonly ImageAnalyzer $imageAnalyzer,
        private readonly ImageRepository $imageRepository,
        #[Autowire(env: 'DATA_PATH')] private readonly string $dataPath,
        #[Autowire('%kernel.project_dir%')] private readonly string $rootDir,
    ){
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dirPath = $this->rootDir . $this->dataPath . '/images';

        if (!is_dir($dirPath)) {
            $io->error('Images directory not found.');

            return Command::FAILURE;
        }

        $files = glob($dirPath . '/*.*');

        $io->info("Found " . count($files) . " images.");

        $countImagesTreated = 0;

        foreach ($files as $file) {
            [
                'basename' => $basename,
            ] = pathinfo($file);

            $io->note("Generate description for image: {$basename}");

            if ($this->imageRepository->findOneByFilename($basename) instanceof Image) {
                $io->note("Image already described, skipping...");
                continue;
            }

            $imagePath = $dirPath . '/' . $basename;
            // generate description
            $description = $this->imageAnalyzer->analyze($imagePath);

            if (null === $description) {
                $io->error("Image analysis failed, skipping...");
                continue;
            }

            // save into PostgresSQL DB
            $image = (new Image())
                ->setDescription($description)
                ->setPath($imagePath)
                ->setFilename($basename)
                ->setUuid(UUid::v4());

            $this->imageRepository->save($image);

            $countImagesTreated++;
        }

        $io->success($countImagesTreated . ' images treated.');

        return Command::SUCCESS;
    }
}
