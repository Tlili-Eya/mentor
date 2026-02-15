<?php

namespace App\Controller;

use App\Entity\Tache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/tache', name: 'back_tache_')]
class BackTacheController extends AbstractController
{
    /**
     * Affiche le détail d'une tâche (lecture seule)
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Tache $tache): Response
    {
        return $this->render('back/tache_show.html.twig', [
            'tache' => $tache,
        ]);
    }
}
