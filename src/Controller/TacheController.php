<?php

namespace App\Controller;

use App\Entity\Tache;
use App\Form\TacheType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tache', name: 'front_tache_')]
class TacheController extends AbstractController
{
    // 1. Afficher une tâche (Voir)
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Tache $tache): Response
    {
        return $this->render('front/tache_show.html.twig', [
            'tache' => $tache,
        ]);
    }

    // 2. Modifier une tâche
#[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Tache $tache,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Tâche modifiée avec succès !');
            return $this->redirectToRoute('front_programme_show', [
                'id' => $tache->getProgramme()->getId()
            ]);
        }

        return $this->render('front/tache_edit.html.twig', [
            'tache' => $tache,
            'form'  => $form->createView(),
        ]);
    }

    // 3. Supprimer une tâche
    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Tache $tache, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tache->getId(), $request->request->get('_token'))) {
            $programmeId = $tache->getProgramme()->getId();
            $entityManager->remove($tache);
            $entityManager->flush();

            $this->addFlash('success', 'Tâche supprimée avec succès !');
            return $this->redirectToRoute('front_programme_show', ['id' => $programmeId]);
        }

        $this->addFlash('danger', 'Échec de la suppression.');
        return $this->redirectToRoute('front_programme_show', ['id' => $tache->getProgramme()->getId()]);
    }
}