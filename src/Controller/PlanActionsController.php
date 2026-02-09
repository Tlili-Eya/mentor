<?php

namespace App\Controller;

use App\Entity\PlanActions;
use App\Form\PlanActionsType;
use App\Repository\PlanActionsRepository;
use App\Enum\Statut;
use App\Service\PdfExportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/back/plan-actions')]
class PlanActionsController extends AbstractController
{
    #[Route('/', name: 'app_plan_actions_index', methods: ['GET'])]
    public function index(Request $request, PlanActionsRepository $repository): Response
    {
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'date'); // 'date' par défaut
        $order = $request->query->get('order', 'DESC');
        $statut = $request->query->get('statut', '');
        $categorie = $request->query->get('categorie', '');
        $dateDebut = $request->query->get('date_debut', '');
        $dateFin = $request->query->get('date_fin', '');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;

        $qb = $repository->createQueryBuilder('p');

        // Recherche
        if (!empty($search)) {
            $qb->andWhere('p.decision LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Filtre par statut
        if (!empty($statut)) {
            try {
                $statutEnum = Statut::from($statut);
                $qb->andWhere('p.statut = :statut')
                   ->setParameter('statut', $statutEnum);
            } catch (\ValueError $e) {
                // Statut invalide, on ignore le filtre
            }
        }

        // Filtre par catégorie
        if (!empty($categorie)) {
            $qb->andWhere('p.categorie = :categorie')
               ->setParameter('categorie', $categorie);
        }

        // Filtre par date début
        if (!empty($dateDebut)) {
            $qb->andWhere('p.date >= :date_debut')
               ->setParameter('date_debut', new \DateTime($dateDebut));
        }

        // Filtre par date fin
        if (!empty($dateFin)) {
            $qb->andWhere('p.date <= :date_fin')
               ->setParameter('date_fin', new \DateTime($dateFin . ' 23:59:59'));
        }

        // Tri
        $validSorts = ['id', 'decision', 'date', 'statut'];
        if (in_array($sortBy, $validSorts)) {
            $qb->orderBy('p.' . $sortBy, strtoupper($order) === 'ASC' ? 'ASC' : 'DESC');
        } else {
            $qb->orderBy('p.date', 'DESC'); // Tri par défaut
        }

        // Pagination
        $total = count($qb->getQuery()->getResult());
        $plans = $qb->setFirstResult(($page - 1) * $limit)
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();

        $totalPages = ceil($total / $limit);

        return $this->render('back/plan_actions/index.html.twig', [
            'plans' => $plans,
            'search' => $search,
            'sortBy' => $sortBy,
            'order' => $order,
            'statut' => $statut,
            'categorie' => $categorie,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    #[Route('/new', name: 'app_plan_actions_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $planAction = new PlanActions();
        $form = $this->createForm(PlanActionsType::class, $planAction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $planAction->setUpdatedAt(new \DateTime());
            
            $entityManager->persist($planAction);
            $entityManager->flush();

            $this->addFlash('success', 'Le plan d\'action a été créé avec succès!');
            return $this->redirectToRoute('app_plan_actions_index');
        }

        return $this->render('back/plan_actions/new.html.twig', [
            'plan_actions' => $planAction,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_plan_actions_show', methods: ['GET'])]
    public function show(PlanActions $planAction): Response
    {
        return $this->render('back/plan_actions/show.html.twig', [
            'plan_actions' => $planAction,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_plan_actions_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PlanActions $planAction, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PlanActionsType::class, $planAction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $planAction->setUpdatedAt(new \DateTime());
            
            $entityManager->flush();

            $this->addFlash('success', 'Le plan d\'action a été modifié avec succès!');
            return $this->redirectToRoute('app_plan_actions_index');
        }

        return $this->render('back/plan_actions/edit.html.twig', [
            'plan_actions' => $planAction,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_plan_actions_delete', methods: ['POST'])]
    public function delete(Request $request, PlanActions $planAction, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $planAction->getId(), $request->request->get('_token'))) {
            $entityManager->remove($planAction);
            $entityManager->flush();

            $this->addFlash('success', 'Le plan d\'action a été supprimé avec succès!');
        }

        return $this->redirectToRoute('app_plan_actions_index');
    }

    #[Route('/export/pdf', name: 'app_plan_actions_export_pdf', methods: ['GET'])]
    public function exportPdf(
        Request $request,
        PlanActionsRepository $repository,
        PdfExportService $pdfService
    ): Response {
        // Récupérer les plans avec les mêmes filtres que l'index
        $search = $request->query->get('search', '');
        $statut = $request->query->get('statut', '');
        $categorie = $request->query->get('categorie', '');
        $dateDebut = $request->query->get('date_debut', '');
        $dateFin = $request->query->get('date_fin', '');

        $qb = $repository->createQueryBuilder('p');

        // Appliquer les mêmes filtres que l'index
        if (!empty($search)) {
            $qb->andWhere('p.decision LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if (!empty($statut)) {
            try {
                $statutEnum = Statut::from($statut);
                $qb->andWhere('p.statut = :statut')
                   ->setParameter('statut', $statutEnum);
            } catch (\ValueError $e) {
                // Ignorer
            }
        }

        if (!empty($categorie)) {
            $qb->andWhere('p.categorie = :categorie')
               ->setParameter('categorie', $categorie);
        }

        if (!empty($dateDebut)) {
            $qb->andWhere('p.date >= :date_debut')
               ->setParameter('date_debut', new \DateTime($dateDebut));
        }

        if (!empty($dateFin)) {
            $qb->andWhere('p.date <= :date_fin')
               ->setParameter('date_fin', new \DateTime($dateFin . ' 23:59:59'));
        }

        $qb->orderBy('p.date', 'DESC');
        
        $plans = $qb->getQuery()->getResult();

        // Préparer les données pour le PDF
        $data = [
            'plans' => $plans,
            'title' => 'Liste des Plans d\'Actions',
            'date' => new \DateTime(),
            'filters' => [
                'search' => $search,
                'statut' => $statut,
                'categorie' => $categorie,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
            ]
        ];

        return $pdfService->generateTablePdf($data, 'plans_actions_' . date('Y-m-d'));
    }
}