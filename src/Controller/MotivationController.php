<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MotivationController extends AbstractController
{
    #[Route('/motivation', name: 'app_motivation')]
    public function index(): Response
    {
        return $this->render('motivation/index.html.twig', [
            'controller_name' => 'MotivationController',
        ]);
    }
}
