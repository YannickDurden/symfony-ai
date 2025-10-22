<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mercure\Update;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        HubInterface $hub
    ): Response {
        $composer = json_decode(
            json: file_get_contents($projectDir . '/composer.json'),
            associative: true
        );
        $update = new Update(
            $this->generateUrl('mercure_test'),
            json_encode(['message' => 'Mercure is alive!'])
        );

        $hub->publish($update);

        return $this->render('home/index.html.twig', [
            'composer' => $composer
        ]);
    }

    #[Route(path: '/mercure', name: 'mercure_test', methods: ['POST'])]
    public function test(Request $request): Response
    {
        return $this->json('Received!');
    }
}
