<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\Ressource;
use App\Form\ProjetType;
use App\Form\RessourceType;
use App\Repository\ProjetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/projets', name: 'front_')]
class ProjetController extends AbstractController
{
    #[Route('/', name: 'projets')]
    public function index(Request $request, EntityManagerInterface $entityManager, ProjetRepository $projetRepository, \App\Repository\RessourceRepository $ressourceRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $projects = $projetRepository->findBy(['utilisateur' => $user]);
        $projectId = $request->query->get('id');
        $activeProject = null;

        // Si l'ID est fourni et > 0, on cherche le projet
        if ($projectId && $projectId > 0) {
            $activeProject = $projetRepository->findOneBy(['id' => $projectId, 'utilisateur' => $user]);
        } 
        // Si aucun ID n'est fourni (null), on affiche le premier projet par défaut
        elseif ($projectId === null && !empty($projects)) {
            $activeProject = $projects[0];
        }
        // Si l'ID est '0', on ne fait rien : activeProject reste null => Formulaire de création

        $projectForm = $this->createForm(ProjetType::class, $activeProject ?? new Projet());
        $projectForm->handleRequest($request);

        if ($projectForm->isSubmitted() && $projectForm->isValid()) {
            $projet = $projectForm->getData();
            $projet->setUtilisateur($user);
            
            if (!$projet->getId()) {
                 $projet->setDateCreation(new \DateTime());
            }
             $projet->setDateModification(new \DateTime());

            $entityManager->persist($projet);
            $entityManager->flush();

            return $this->redirectToRoute('front_projets', ['id' => $projet->getId()]);
        }

        $resourceForm = null;
        if ($activeProject && $activeProject->getId()) {
            // Gestion de l'édition d'une ressource
            $resourceId = $request->query->get('resource_id');
            $resource = null;
            
            if ($resourceId) {
                $resource = $ressourceRepository->findOneBy(['id' => $resourceId]);
                // Vérifier que la ressource appartient bien au projet actif (sécurité)
                if ($resource && $resource->getProjet()->getId() !== $activeProject->getId()) {
                    $resource = null; // Rejet silencieux ou throw AccessDenied
                }
            }
            
            // Si pas de ressource trouvée ou pas d'ID, on crée une nouvelle
            if (!$resource) {
                $resource = new Ressource();
            }

            $resourceForm = $this->createForm(RessourceType::class, $resource);
            $resourceForm->handleRequest($request);

            if ($resourceForm->isSubmitted() && $resourceForm->isValid()) {
                $resource->setProjet($activeProject);
                if (!$resource->getId()) {
                     $resource->setDateCreation(new \DateTime());
                }
                $resource->setDateModification(new \DateTime());
                
                $entityManager->persist($resource);
                $entityManager->flush();

                // Redirection sans le paramètre resource_id pour sortir du mode édition
                return $this->redirectToRoute('front_projets', ['id' => $activeProject->getId()]);
            }
        }

        return $this->render('front/projets.html.twig', [
            'projects' => $projects,
            'activeProject' => $activeProject,
            'projectForm' => $projectForm->createView(),
            'resourceForm' => $resourceForm ? $resourceForm->createView() : null,
        ]);
    }

    #[Route('/delete/{id}', name: 'delete_project')]
    public function deleteProject(Projet $projet, EntityManagerInterface $entityManager): Response
    {
        if ($projet->getUtilisateur() !== $this->getUser()) {
             throw $this->createAccessDeniedException();
        }
        
        $entityManager->remove($projet);
        $entityManager->flush();
        
        return $this->redirectToRoute('front_projets');
    }

    #[Route('/ressource/delete/{id}', name: 'delete_ressource')]
    public function deleteRessource(Ressource $ressource, EntityManagerInterface $entityManager): Response
    {
         if ($ressource->getProjet()->getUtilisateur() !== $this->getUser()) {
             throw $this->createAccessDeniedException();
         }

         $projectId = $ressource->getProjet()->getId();
         $entityManager->remove($ressource);
         $entityManager->flush();

         return $this->redirectToRoute('front_projets', ['id' => $projectId]);
    }
}
