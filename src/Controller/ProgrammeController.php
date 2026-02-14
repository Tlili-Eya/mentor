<?php

namespace App\Controller;

use App\Entity\Programme;
use App\Entity\Tache;
use App\Enum\Etat;
use App\Enum\Statutobj;
use App\Form\TacheType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ObjectifStatusService;

#[Route('/programme', name: 'front_programme_')]
class ProgrammeController extends AbstractController
{
    /**
     * Calcule et met à jour le score (pourcentage réalisé) et la meilleure médaille du programme
     */
    private ObjectifStatusService $objectifStatusService;

// Injecte dans le constructeur
public function __construct(ObjectifStatusService $objectifStatusService)
{
    $this->objectifStatusService = $objectifStatusService;
}
    private function updateProgrammeStats(Programme $programme, EntityManagerInterface $entityManager): void
    {
        $taches = $programme->getTache();
        $total = $taches->count();

        if ($total === 0) {
            $programme->setScorePourcentage(0);
            $programme->setMeilleureMedaille(null);
            if ($programme->getObjectif()) {
            $programme->getObjectif()->setStatut(Statutobj::Abandonner);
        }
            $entityManager->flush();
            return;
        }

        $realisees = 0;
        $meilleureMedaille = null;

        foreach ($taches as $tache) {
            if (in_array($tache->getEtat()->value, [Etat::realisee->value])) {
                $realisees++;

                // Meilleure médaille
                $medaille = $programme->getMeilleureMedaille();
                if ($medaille !== null && ($meilleureMedaille === null || $medaille->value > $meilleureMedaille->value)) {
                    $meilleureMedaille = $medaille;
                }
            }
        }

        // Score = pourcentage de tâches réalisées (arrondi à l'entier)
        $score = (int) round(($realisees / $total) * 100);

        $programme->setScorePourcentage($score);
        $programme->setMeilleureMedaille($meilleureMedaille);
        $entityManager->flush();
        // Synchronisation automatique du statut de l’objectif
   

        $entityManager->flush();
        if ($programme->getObjectif()) {
        $this->objectifStatusService->updateStatusFromProgrammeScore($programme->getObjectif());
    }
    }

    #[Route('/{id}', name: 'show', methods: ['GET', 'POST'])]
    public function show(
        Request $request,
        Programme $programme,
        EntityManagerInterface $entityManager
    ): Response {
        $tache = new Tache();
        $tache->setProgramme($programme);

        $formTache = $this->createForm(TacheType::class, $tache);
        $formTache->handleRequest($request);

        if ($formTache->isSubmitted() && $formTache->isValid()) {
            $entityManager->persist($tache);
            $entityManager->flush();

            // Mise à jour score et médaille après ajout
            $this->updateProgrammeStats($programme, $entityManager);

            $this->addFlash('success', 'Tâche ajoutée avec succès !');
            return $this->redirectToRoute('front_programme_show', ['id' => $programme->getId()]);
        }

        return $this->render('front/programme_show.html.twig', [
            'programme' => $programme,
            'formTache' => $formTache->createView(),
        ]);
    }
}