<?php

namespace App\Controller;

use App\Entity\PlanActions;
use App\Form\PlanActionFeedbackType;
use App\Repository\PlanActionsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/enseignant/plan')]
class EnseignantPlanFeedbackController extends AbstractController
{
    #[Route('/{id}/feedback', name: 'app_enseignant_plan_feedback', methods: ['GET', 'POST'])]
    public function feedback(
        Request $request,
        int $id,
        PlanActionsRepository $repository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $plan = $repository->find($id);
        
        if (!$plan) {
            throw $this->createNotFoundException('Plan d\'action non trouvé');
        }

        // Vérifier si l'utilisateur est un enseignant
        if (!$this->isGranted('ROLE_ENSEIGNANT')) {
            throw $this->createAccessDeniedException('Accès réservé aux enseignants');
        }

        $form = $this->createForm(PlanActionFeedbackType::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistrer l'auteur et la date du feedback
            $user = $this->getUser();
            if ($user) {
                $plan->setFeedbackAuteur($user);
            }
            $plan->setFeedbackDate(new \DateTime());
            
            $entityManager->flush();

            $this->addFlash('success', 'Votre feedback a été enregistré avec succès!');
            return $this->redirectToRoute('app_enseignant_plan_detail', ['id' => $id]);
        }

        return $this->render('front/enseignant/plan_feedback.html.twig', [
            'plan' => $plan,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer-feedback', name: 'app_enseignant_plan_supprimer_feedback', methods: ['POST'])]
    public function supprimerFeedback(
        Request $request,
        int $id,
        PlanActionsRepository $repository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $plan = $repository->find($id);
        
        if (!$plan) {
            throw $this->createNotFoundException('Plan d\'action non trouvé');
        }

        // Vérifier que l'utilisateur est l'auteur du feedback ou un admin
        $user = $this->getUser();
        if (!$user || ($plan->getFeedbackAuteur() && $plan->getFeedbackAuteur()->getId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN'))) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce feedback');
        }

        if ($this->isCsrfTokenValid('delete-feedback' . $plan->getId(), $request->request->get('_token'))) {
            $plan->clearFeedback();
            $entityManager->flush();

            $this->addFlash('success', 'Le feedback a été supprimé avec succès!');
        }

        return $this->redirectToRoute('app_enseignant_plan_detail', ['id' => $id]);
    }
}