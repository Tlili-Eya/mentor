<?php

namespace App\Controller;

use App\Repository\ObjectifRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events', name: 'app_events_')]
final class ObjectifController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ObjectifRepository $objectifRepository): Response
    {
        $objectifs = $objectifRepository->findAll();

        return $this->render('front/events.html.twig', [
            'objectifs' => $objectifs,
        ]);
    }
}