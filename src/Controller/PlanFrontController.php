<?php
// src/Controller/PlanFrontController.php

namespace App\Controller;

use App\Entity\PlanActions;
use App\Repository\PlanActionsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlanFrontController extends AbstractController
{
    #[Route('/plans', name: 'front_plans')]
    public function index(Request $request, PlanActionsRepository $repository): Response
    {
        // Pour les enseignants, seulement les plans pédagogiques/administratifs
        $type = $request->query->get('type', '');
        $search = $request->query->get('search', '');
        
        $qb = $repository->createQueryBuilder('p')
            ->andWhere('p.statut != :rejete')
            ->setParameter('rejete', \App\Enum\Statut::Rejete);

        if ($this->isGranted('ROLE_ENSEIGNANT')) {
            $qb->andWhere('p.typeDecision IN (:types)')
               ->setParameter('types', ['PEDAGOGIQUE', 'ADMINISTRATIVE']);
        }

        if (!empty($search)) {
            $qb->andWhere('p.decision LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if (!empty($type)) {
            $qb->andWhere('p.typeDecision = :type')
               ->setParameter('type', $type);
        }

        $plans = $qb->orderBy('p.createdAt', 'DESC')
                   ->getQuery()
                   ->getResult();

        return $this->render('front/plans/index.html.twig', [
            'plans' => $plans,
            'search' => $search,
            'type' => $type,
        ]);
    }

    #[Route('/plan/{id}', name: 'front_plan_show')]
    public function show(PlanActions $plan): Response
    {
        // Vérifier les permissions
        if ($this->isGranted('ROLE_ENSEIGNANT') && 
            !in_array($plan->getTypeDecision()->value, ['PEDAGOGIQUE', 'ADMINISTRATIVE'])) {
            throw $this->createAccessDeniedException('Accès non autorisé à ce plan.');
        }

        return $this->render('front/plans/show.html.twig', [
            'plan' => $plan,
        ]);
    }
}