<?php

namespace App\Controller;

use App\Entity\Programme;
use App\Entity\Tache;
use App\Enum\Etat;
use App\Form\TacheType;
use App\Service\ObjectifStatusService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tache', name: 'front_tache_')]
class TacheController extends AbstractController
{
    private ObjectifStatusService $objectifStatusService;

    public function __construct(ObjectifStatusService $objectifStatusService)
    {
        $this->objectifStatusService = $objectifStatusService;
    }

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

            // Mise à jour du score, médaille et statut objectif
            if ($programme = $tache->getProgramme()) {
                $this->updateProgrammeStats($programme, $entityManager);
            }

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

            // Mise à jour du score, médaille et statut objectif après suppression
            if ($programme = $tache->getProgramme()) {
                $this->updateProgrammeStats($programme, $entityManager);
            }

            $this->addFlash('success', 'Tâche supprimée avec succès !');
            return $this->redirectToRoute('front_programme_show', ['id' => $programmeId]);
        }

        $this->addFlash('danger', 'Échec de la suppression.');
        return $this->redirectToRoute('front_programme_show', ['id' => $tache->getProgramme()->getId()]);
    }

    /**
     * Copie temporaire de updateProgrammeStats (à garder jusqu'à ce que tu crées un service partagé)
     */
    private function updateProgrammeStats(Programme $programme, EntityManagerInterface $entityManager): void
    {
        $taches = $programme->getTache();
        $total = $taches->count();

        if ($total === 0) {
            $programme->setScorePourcentage(0);
            $programme->setMeilleureMedaille(null);
            $entityManager->flush();
            return;
        }

        $realisees = 0;
        $meilleureMedaille = null;

        foreach ($taches as $tache) {
            if (in_array($tache->getEtat()->value, [Etat::realisee->value])) {
                $realisees++;

                $medaille = $programme->getMeilleureMedaille();
                if ($medaille !== null && ($meilleureMedaille === null || $medaille->value > $meilleureMedaille->value)) {
                    $meilleureMedaille = $medaille;
                }
            }
        }

        $score = (int) round(($realisees / $total) * 100);
        $programme->setScorePourcentage($score);
        $programme->setMeilleureMedaille($meilleureMedaille);

        $entityManager->flush();

        // Mise à jour statut objectif
        if ($programme->getObjectif()) {
            $this->objectifStatusService->updateStatusFromProgrammeScore($programme->getObjectif());
        }
    }
}