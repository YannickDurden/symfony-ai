<?php

namespace App\Controller;

use App\Service\RagSearch;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route(path: '/', name: 'app_home', methods: ['GET', 'POST'])]
    public function __invoke(RagSearch $ragSearch): Response
    {
        /*try {
            $response = $ragSearch->query(
                question: "Dans quels films est-ce qu'il y a des machines ?"
            );
        } catch (\Throwable $th) {
            dd($th->getMessage());
        }*/

        $response = 'Décommentez pour tester!';

        return $this->json(
            data: [
                'response' => $response,
            ]
        );
    }
}
