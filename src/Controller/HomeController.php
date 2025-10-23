<?php

namespace App\Controller;

use App\Service\RagSearch;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly RagSearch $ragSearch,
        #[Autowire('%kernel.project_dir%')] private string $rootDir,
        #[Autowire(env: 'DATA_PATH')] private readonly string $dataPath,
    ) {}

    #[Route(path: '/', name: 'app_home', methods: ['GET', 'POST'])]
    public function __invoke(): Response
    {
        /* $response = $this->ragSearch->query(
            question: "Dans quels films est-ce qu'il y a des machines ?"
        ); */

        $response = 'Decommentez pour tester!';

        return $this->json(
            data: [
                'response' => $response,
            ]
        );
    }
}
