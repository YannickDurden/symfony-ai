<?php

namespace App\Controller;

use App\Repository\ImageRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HomeController extends AbstractController
{
    #[Route(path: '/', name: 'app_home', methods: ['GET'])]
    public function searchMovies(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route(path: '/images', name: 'app_images', methods: ['GET'])]
    public function searchImages(): Response
    {
        return $this->render('home/images.html.twig');
    }

    #[Route(path: '/image/{id}', name: 'app_image_show', methods: ['GET'])]
    public function showImage(int $id, ImageRepository $imageRepository): Response
    {
        $image = $imageRepository->find($id);

        if (!$image) {
            throw new NotFoundHttpException('Image not found');
        }

        $imagePath = $image->getPath();

        if (!file_exists($imagePath)) {
            throw new NotFoundHttpException('Image file not found');
        }

        return new BinaryFileResponse($imagePath);
    }
}
