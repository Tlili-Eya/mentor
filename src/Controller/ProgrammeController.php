<?php

namespace App\Controller;

use App\Entity\Programme;
use App\Entity\Tache;
use App\Form\TacheType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/programme', name: 'front_programme_')]
class ProgrammeController extends AbstractController
{
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

            $this->addFlash('success', 'Tâche ajoutée avec succès !');
            return $this->redirectToRoute('front_programme_show', ['id' => $programme->getId()]);
        }

        return $this->render('front/programme_show.html.twig', [
            'programme' => $programme,
            'formTache' => $formTache->createView(),
        ]);
    }
}