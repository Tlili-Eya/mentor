<?php

namespace App\Controller;

use App\Entity\Parcours;
use App\Form\ParcoursType;
use App\Repository\ParcoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ParcoursController extends AbstractController
{
    #[Route('/admin/parcours', name: 'back_parcours')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminParcours(Request $request, ParcoursRepository $parcoursRepository, EntityManagerInterface $entityManager): Response
    {
        $limit = 3;
        $page = max(1, $request->query->getInt('page', 1));
        $search = $request->query->get('q');
        $sortBy = $request->query->get('sort');

        $qb = $parcoursRepository->createQueryBuilder('p');

        if ($search) {
            $qb->andWhere('p.titre LIKE :search OR p.type_parcours LIKE :search OR p.date_debut LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        switch ($sortBy) {
            case 'titre':
                $qb->orderBy('p.titre', 'ASC');
                break;
            case 'date_debut':
                $qb->orderBy('p.date_debut', 'ASC');
                break;
            case 'duree':
                $qb->addSelect('(p.date_fin - p.date_debut) as HIDDEN duration')
                   ->orderBy('duration', 'DESC');
                break;
            default:
                $qb->orderBy('p.id', 'ASC');
                break;
        }

        $query = $qb->getQuery();
        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        $paginator->getQuery()
            ->setFirstResult(($limit * ($page - 1)))
            ->setMaxResults($limit);

        $totalParcours = count($paginator);
        $totalPages = (int) ceil($totalParcours / $limit);

        // --- STATS DATA ---
        // 1. Distribution by Type
        $statsTypes = $entityManager->createQuery('
            SELECT p.type_parcours as type, COUNT(p.id) as count 
            FROM App\Entity\Parcours p 
            GROUP BY p.type_parcours
        ')->getResult();

        // 2. Projects per Parcours
        $projectsPerParcours = $entityManager->createQuery('
            SELECT p.titre, COUNT(pr.id) as totalProjects 
            FROM App\Entity\Parcours p 
            LEFT JOIN p.projets pr 
            GROUP BY p.id 
            ORDER BY totalProjects DESC
        ')->setMaxResults(5)->getResult();

        // 3. Global Counts
        $globalStats = [
            'total_parcours' => $parcoursRepository->count([]),
            'total_projects' => $entityManager->createQuery('SELECT COUNT(p.id) FROM App\Entity\Projet p')->getSingleScalarResult(),
            'total_institutions' => $entityManager->createQuery('SELECT COUNT(DISTINCT p.etablissement) FROM App\Entity\Parcours p')->getSingleScalarResult(),
        ];

        return $this->render('back/parcours.html.twig', [
            'parcours' => $paginator,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'sortBy' => $sortBy,
            'statsTypes' => $statsTypes,
            'projectsPerParcours' => $projectsPerParcours,
            'globalStats' => $globalStats
        ]);
    }

    #[Route('/admin/parcours/new', name: 'back_new_parcours')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminNewParcours(Request $request, EntityManagerInterface $entityManager): Response
    {
        $parcours = new Parcours();
        $form = $this->createForm(ParcoursType::class, $parcours);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $parcours->setDateCreation(new \DateTime());
            $entityManager->persist($parcours);
            $entityManager->flush();

            $this->addFlash('success', 'Nouveau parcours créé avec succès.');
            return $this->redirectToRoute('back_parcours');
        }

        return $this->render('back/new_parcours.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/parcours/show/{id}', name: 'back_show_parcours')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminShowParcours(Parcours $parcours): Response
    {
        return $this->render('back/show_parcours.html.twig', [
            'parcours' => $parcours,
        ]);
    }

    #[Route('/admin/parcours/edit/{id}', name: 'back_edit_parcours')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminEditParcours(Request $request, Parcours $parcours, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ParcoursType::class, $parcours);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $parcours->setDateModification(new \DateTime());
            $entityManager->flush();
            $this->addFlash('success', 'Parcours modifié avec succès.');
            return $this->redirectToRoute('back_parcours');
        }

        return $this->render('back/edit_parcours.html.twig', [
            'parcours' => $parcours,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/parcours/delete/{id}', name: 'back_delete_parcours')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDeleteParcours(Parcours $parcours, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($parcours);
        $entityManager->flush();
        $this->addFlash('success', 'Parcours supprimé avec succès.');
        return $this->redirectToRoute('back_parcours');
    }
}
