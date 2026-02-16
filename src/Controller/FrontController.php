<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\Ressource;
use App\Enum\TypeRessource;
use App\Repository\ProjetRepository;
use App\Repository\RessourceRepository;
use App\Repository\ParcoursRepository;
use App\Repository\FeedbackRepository;
use App\Repository\UtilisateurRepository;
use App\Entity\Utilisateur;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/', name: 'front_')]
final class FrontController extends AbstractController
{
    #[Route('', name: 'home')]
    public function home(): Response
    {
        return $this->render('front/home.html.twig');
    }

    
    #[Route('about', name: 'about')]
    public function about(): Response
    {
        return $this->render('front/about.html.twig');
    }

    #[Route('course-details', name: 'course_details')]
    public function courseDetails(): Response
    {
        return $this->render('front/course-details.html.twig');
    }

// Projet creation with validation

    #[Route('projets', name: 'projets')]
    public function projets(Request $request, EntityManagerInterface $em, RessourceRepository $ressourceRepo): Response
    {
        $projet = new Projet();
        $projet->setDateCreation(new \DateTime());
        $errors = [];
        $ressources = [];
        
        if ($request->isMethod('POST')) {
            // Récupération des données du projet
            $titre = trim($request->request->get('titre', ''));
            $type = trim($request->request->get('type', ''));
            $technologies = trim($request->request->get('technologies', ''));
            $description = trim($request->request->get('description', ''));
            $dateDebut = $request->request->get('date_debut', '');
            $dateFin = $request->request->get('date_fin', '');
            
            // Validation PHP du projet
            if (empty($titre)) {
                $errors['titre'] = 'Le titre du projet est requis.';
            } elseif (strlen($titre) < 3) {
                $errors['titre'] = 'Le titre doit contenir au moins 3 caractères.';
            } elseif (strlen($titre) > 255) {
                $errors['titre'] = 'Le titre ne doit pas dépasser 255 caractères.';
            }
            
            if (empty($type)) {
                $errors['type'] = 'Le type de projet est requis.';
            } elseif (strlen($type) > 100) {
                $errors['type'] = 'Le type ne doit pas dépasser 100 caractères.';
            }
            
            if (empty($technologies)) {
                $errors['technologies'] = 'Les technologies sont requises.';
            } elseif (strlen($technologies) > 500) {
                $errors['technologies'] = 'Les technologies ne doivent pas dépasser 500 caractères.';
            }
            
            // Validation des dates si présentes
            $dateDebutObj = null;
            $dateFinObj = null;
            
            if (!empty($dateDebut)) {
                try {
                    $dateDebutObj = new \DateTime($dateDebut);
                } catch (\Exception $e) {
                    $errors['date_debut'] = 'La date de début est invalide.';
                }
            }
            
            if (!empty($dateFin)) {
                try {
                    $dateFinObj = new \DateTime($dateFin);
                } catch (\Exception $e) {
                    $errors['date_fin'] = 'La date de fin est invalide.';
                }
            }
            
            // Vérifier que la date de fin est après la date de début
            if (!empty($dateDebut) && !empty($dateFin) && $dateDebutObj && $dateFinObj) {
                if ($dateFinObj < $dateDebutObj) {
                    $errors['date_fin'] = 'La date de fin doit être après la date de début.';
                }
            }
            
            // Si pas d'erreurs, sauvegarder le projet
            if (empty($errors)) {
                $projet->setTitre($titre);
                $projet->setType($type);
                $projet->setTechnologies($technologies);
;
                
                if ($dateDebutObj) {
                    $projet->setDateDebut($dateDebutObj);
                }
                if ($dateFinObj) {
                    $projet->setDateFin($dateFinObj);
                }
                
                // Ajouter l'utilisateur actuellement connecté
                if ($this->getUser()) {
                    $projet->setUtilisateur($this->getUser());
                }
                
                $em->persist($projet);
                $em->flush();
                
                // Traiter les ressources
                $ressourcesData = $request->request->all();
                
                if (isset($ressourcesData['ressources']) && is_array($ressourcesData['ressources'])) {
                    foreach ($ressourcesData['ressources'] as $timestamp => $ressourceData) {
                        // Vérifier que les champs requis sont présents
                        if (isset($ressourceData['nom'], $ressourceData['type'], $ressourceData['url']) 
                            && !empty($ressourceData['nom']) 
                            && !empty($ressourceData['type']) 
                            && !empty($ressourceData['url'])) {
                            
                            $ressource = new Ressource();
                            $ressource->setNom(trim($ressourceData['nom']));
                            $ressource->setUrlRessource(trim($ressourceData['url']));
                            $ressource->setDescription(trim($ressourceData['description'] ?? ''));
                            $ressource->setProjet($projet);
                            $ressource->setDateCreation(new \DateTime());
                            
                            // Mapper le type string vers l'enum TypeRessource
                            $typeStr = strtoupper(trim($ressourceData['type']));
                            try {
                                $typeEnum = TypeRessource::tryFrom($typeStr);
                                if ($typeEnum === null) {
                                    $typeEnum = TypeRessource::OTHER;
                                }
                                $ressource->setTypeRessource($typeEnum);
                            } catch (\Exception $e) {
                                $ressource->setTypeRessource(TypeRessource::OTHER);
                            }
                            
                            $em->persist($ressource);
                        }
                    }
                }
                
                $em->flush();
                
                $this->addFlash('success', 'Projet créé avec succès!');
                return $this->redirectToRoute('front_projets');
            } else {
                // Afficher les erreurs
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                // Repeupler le formulaire avec les données saisies
                $projet->setTitre($titre);
                $projet->setType($type);
                $projet->setTechnologies($technologies);
                $projet->setDescription($description);
            }
        }
        
        return $this->render('front/projets.html.twig', [
            'projet' => $projet,
            'errors' => $errors,
            'ressources' => $ressources,
        ]);
    }

    #[Route('instructor-profile', name: 'instructor_profile')]
    public function instructorProfile(): Response
    {
        return $this->render('front/instructor-profile.html.twig');
    }

    #[Route('events', name: 'events')]
    public function events(): Response
    {
        return $this->render('front/events.html.twig');
    }

    #[Route('pricing', name: 'pricing')]
    public function pricing(): Response
    {
        return $this->render('front/pricing.html.twig');
    }

    #[Route('privacy', name: 'privacy')]
    public function privacy(): Response
    {
        return $this->render('front/privacy.html.twig');
    }

    #[Route('terms', name: 'terms')]
    public function terms(): Response
    {
        return $this->render('front/terms.html.twig');
    }

    #[Route('blog', name: 'blog')]
    public function blog(): Response
    {
        return $this->render('front/blog.html.twig');
    }

    #[Route('blog-details', name: 'blog_details')]
    public function blogDetails(): Response
    {
        return $this->render('front/blog-details.html.twig');
    }

    #[Route('contact', name: 'contact')]
    public function contact(
        Request $request,
        FeedbackRepository $feedbackRepo,
        UtilisateurRepository $userRepo
    ): Response {
        // ✅ NOTIFICATION FACEBOOK-LIKE : Calculer le nombre de nouveaux feedbacks traités
        $newTreatedCount = 0;
        try {
            // Get mock user (temporary)
            $userId = 2;
            $user = $userRepo->find($userId);
            
            if ($user) {
                // Récupérer tous les feedbacks de l'utilisateur
                $feedbacks = $feedbackRepo->findBy(['utilisateur' => $user]);
                
                // Récupérer les IDs des feedbacks déjà vus depuis la session
                $session = $request->getSession();
                $seenFeedbackIds = $session->get('seen_treated_feedbacks', []);
                
                // Compter les feedbacks traités qui ne sont pas encore vus
                foreach ($feedbacks as $feedback) {
                    $etat = strtolower($feedback->getEtatfeedback() ?? '');
                    if (($etat === 'traite' || $etat === 'traité') && !in_array($feedback->getId(), $seenFeedbackIds)) {
                        $newTreatedCount++;
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail if user doesn't exist
        }
        
        return $this->render('front/contact.html.twig', [
            'newTreatedCount' => $newTreatedCount
        ]);
    }

    #[Route('enroll', name: 'enroll')]
    public function enroll(): Response
    {
        return $this->render('front/enroll.html.twig');
    }

    #[Route('starter', name: 'starter')]
    public function starter(): Response
    {
        return $this->render('front/starter-page.html.twig');
    }

    #[Route('404', name: '404')]
    public function error404(): Response
    {
        return $this->render('front/404.html.twig');
    }

    #[Route('api/resource/delete/{id}', name: 'api_resource_delete', methods: ['POST'])]
    public function deleteResource(int $id, EntityManagerInterface $em, RessourceRepository $ressourceRepo, ManagerRegistry $doctrine): Response
    {
        $ressource = $ressourceRepo->find($id);
        
        if (!$ressource) {
            return $this->json(['error' => 'Ressource not found'], 404);
        }

        $user = $this->getUser();
        if (!$user) {
            $user = $doctrine->getRepository(Utilisateur::class)->find(1);
        }

        // Vérifier que l'utilisateur est propriétaire du projet
        if ($ressource->getProjet()?->getUtilisateur() !== $user) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $em->remove($ressource);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('api/projet/delete/{id}', name: 'api_projet_delete', methods: ['POST'])]
    public function deleteProjet(int $id, EntityManagerInterface $em, ProjetRepository $projetRepository, ManagerRegistry $doctrine): Response
    {
        $projet = $projetRepository->find($id);
        
        if (!$projet) {
            return $this->json(['error' => 'Projet not found'], 404);
        }

        $user = $this->getUser();
        if (!$user) {
            $user = $doctrine->getRepository(Utilisateur::class)->find(1);
        }

        // Vérifier que l'utilisateur est propriétaire
        if ($projet->getUtilisateur() !== $user) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        // Supprimer toutes les ressources associées
        foreach ($projet->getRessources() as $ressource) {
            $em->remove($ressource);
        }

        $em->remove($projet);
        $em->flush();

        return $this->json(['success' => true]);
    }

        #[Route('mesprojets', name: 'mesprojets')]
        public function mesprojets(ProjetRepository $projetRepository, ManagerRegistry $doctrine): Response
        {
            $user = $this->getUser();

            if (!$user) {
                // Development fallback: load the user with id=1 to allow testing without login.
                $user = $doctrine->getRepository(Utilisateur::class)->find(1);
                if ($user) {
                    $this->addFlash('info', 'Fallback dev actif : affichage en tant qu\'utilisateur id=1');
                } else {
                    $this->addFlash('error', 'Utilisateur de test (id=1) introuvable. Merci de vous connecter.');
                    return $this->redirectToRoute('front_home');
                }
            }

            $projets = $projetRepository->findBy(['utilisateur' => $user]);

            return $this->render('front/mesprojets.html.twig', [
                'projets' => $projets,
            ]);
        }



    
        /*#[Route('mesprojets', name: 'mesprojets')]
        public function mesprojets(
            ProjetRepository $projetRepository
        ): Response
        {
            $user = $this->getUser();
            if (!$user) {
                $this->addFlash('error', 'Vous devez être connecté pour voir vos projets.');
                return $this->redirectToRoute('front_home');
            }

            $projets = $projetRepository->findBy(['utilisateur' => $user]);

            return $this->render('front/mesprojets.html.twig', [
                'projets' => $projets,
            ]);
        }*/



                    #[Route('mesparcours', name: 'mesparcours')]
    public function mesparcours(ProjetRepository $projetRepository, ManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();

        if (!$user) {
            // Development fallback: load the user with id=1 to allow testing without login.
            $user = $doctrine->getRepository(Utilisateur::class)->find(1);
            if ($user) {
                $this->addFlash('info', 'Fallback dev actif : affichage en tant qu\'utilisateur id=1');
            } else {
                $this->addFlash('error', 'Utilisateur de test (id=1) introuvable. Merci de vous connecter.');
                return $this->redirectToRoute('front_home');
            }
        }

        $projets = $projetRepository->findBy(['utilisateur' => $user]);

        // Extraire les parcours uniques à partir des projets de l'utilisateur
        $parcoursMap = [];
        foreach ($projets as $p) {
            $parc = $p->getParcours();
            if ($parc) {
                $parcoursMap[$parc->getId()] = $parc;
            }
        }

        $parcours = array_values($parcoursMap);

        return $this->render('front/mesparcours.html.twig', [
            'parcours' => $parcours,
        ]);
    }

    #[Route('api/projet/{id}', name: 'api_projet_get', methods: ['GET'])]
    public function getProjet(int $id, ProjetRepository $projetRepository, ManagerRegistry $doctrine): Response
    {
        $projet = $projetRepository->find($id);
        
        if (!$projet) {
            return $this->json(['error' => 'Projet not found'], 404);
        }

        $user = $this->getUser();
        if (!$user) {
            $user = $doctrine->getRepository(Utilisateur::class)->find(1);
        }

        // Vérifier que l'utilisateur est propriétaire
        if ($projet->getUtilisateur() !== $user) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        return $this->json([
            'id' => $projet->getId(),
            'titre' => $projet->getTitre(),
            'type' => $projet->getType(),
            'technologies' => $projet->getTechnologies(),
            'description' => $projet->getDescription(),
            'dateDebut' => $projet->getDateDebut()?->format('Y-m-d'),
            'dateFin' => $projet->getDateFin()?->format('Y-m-d'),
        ]);
    }

    #[Route('api/projet/{id}', name: 'api_projet_update', methods: ['PUT'])]
    public function updateProjet(int $id, Request $request, ProjetRepository $projetRepository, EntityManagerInterface $em, ManagerRegistry $doctrine): Response
    {
        $projet = $projetRepository->find($id);
        
        if (!$projet) {
            return $this->json(['error' => 'Projet not found'], 404);
        }

        $user = $this->getUser();
        if (!$user) {
            $user = $doctrine->getRepository(Utilisateur::class)->find(1);
        }

        // Vérifier que l'utilisateur est propriétaire
        if ($projet->getUtilisateur() !== $user) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $errors = [];

        // Validation du titre
        if (!empty($data['titre'])) {
            $titre = trim($data['titre']);
            if (strlen($titre) < 3) {
                $errors['titre'] = 'Le titre doit contenir au moins 3 caractères.';
            } elseif (strlen($titre) > 255) {
                $errors['titre'] = 'Le titre ne doit pas dépasser 255 caractères.';
            } else {
                $projet->setTitre($titre);
            }
        }

        // Validation du type
        if (!empty($data['type'])) {
            $type = trim($data['type']);
            if (strlen($type) > 100) {
                $errors['type'] = 'Le type ne doit pas dépasser 100 caractères.';
            } else {
                $projet->setType($type);
            }
        }

        // Validation des technologies
        if (!empty($data['technologies'])) {
            $technologies = trim($data['technologies']);
            if (strlen($technologies) > 500) {
                $errors['technologies'] = 'Les technologies ne doivent pas dépasser 500 caractères.';
            } else {
                $projet->setTechnologies($technologies);
            }
        }

        // Description
        if (isset($data['description'])) {
            $projet->setDescription(trim($data['description']));
        }

        // Validation des dates
        $dateDebut = $data['dateDebut'] ?? null;
        $dateFin = $data['dateFin'] ?? null;

        if (!empty($dateDebut)) {
            try {
                $projet->setDateDebut(new \DateTime($dateDebut));
            } catch (\Exception $e) {
                $errors['dateDebut'] = 'La date de début est invalide.';
            }
        } else {
            $projet->setDateDebut(null);
        }

        if (!empty($dateFin)) {
            try {
                $projet->setDateFin(new \DateTime($dateFin));
            } catch (\Exception $e) {
                $errors['dateFin'] = 'La date de fin est invalide.';
            }
        } else {
            $projet->setDateFin(null);
        }

        if (empty($errors)) {
            $em->flush();
            return $this->json(['success' => true, 'message' => 'Projet modifié avec succès.']);
        }

        return $this->json(['errors' => $errors], 400);
    }

    // Routes for editing resources
    #[Route('api/ressource/{id}', name: 'api_ressource_get', methods: ['GET'])]
    public function getRessource(int $id, RessourceRepository $ressourceRepository, ManagerRegistry $doctrine): Response
    {
        $ressource = $ressourceRepository->find($id);
        
        if (!$ressource) {
            return $this->json(['error' => 'Ressource not found'], 404);
        }

        $user = $this->getUser();
        if (!$user) {
            $user = $doctrine->getRepository(Utilisateur::class)->find(1);
        }

        // Vérifier que l'utilisateur est propriétaire du projet
        if ($ressource->getProjet()?->getUtilisateur() !== $user) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        return $this->json([
            'id' => $ressource->getId(),
            'nom' => $ressource->getNom(),
            'type' => $ressource->getTypeRessource()?->value,
            'url' => $ressource->getUrlRessource(),
            'description' => $ressource->getDescription(),
        ]);
    }

    #[Route('api/ressource/{id}', name: 'api_ressource_update', methods: ['PUT'])]
    public function updateRessource(int $id, Request $request, RessourceRepository $ressourceRepository, EntityManagerInterface $em, ManagerRegistry $doctrine): Response
    {
        $ressource = $ressourceRepository->find($id);
        
        if (!$ressource) {
            return $this->json(['error' => 'Ressource not found'], 404);
        }

        $user = $this->getUser();
        if (!$user) {
            $user = $doctrine->getRepository(Utilisateur::class)->find(1);
        }

        // Vérifier que l'utilisateur est propriétaire du projet
        if ($ressource->getProjet()?->getUtilisateur() !== $user) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $errors = [];

        // Validation du nom
        if (!empty($data['nom'])) {
            $nom = trim($data['nom']);
            if (strlen($nom) < 1) {
                $errors['nom'] = 'Le nom est requis.';
            } elseif (strlen($nom) > 255) {
                $errors['nom'] = 'Le nom ne doit pas dépasser 255 caractères.';
            } else {
                $ressource->setNom($nom);
            }
        }

        // Validation de l'URL
        if (!empty($data['url'])) {
            $url = trim($data['url']);
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $errors['url'] = 'L\'URL est invalide.';
            } else {
                $ressource->setUrlRessource($url);
            }
        }

        // Description
        if (isset($data['description'])) {
            $ressource->setDescription(trim($data['description']));
        }

        // Type
        if (!empty($data['type'])) {
            $typeStr = strtoupper(trim($data['type']));
            try {
                $typeEnum = TypeRessource::tryFrom($typeStr);
                if ($typeEnum === null) {
                    $typeEnum = TypeRessource::OTHER;
                }
                $ressource->setTypeRessource($typeEnum);
            } catch (\Exception $e) {
                $ressource->setTypeRessource(TypeRessource::OTHER);
            }
        }

        if (empty($errors)) {
            $em->flush();
            return $this->json(['success' => true, 'message' => 'Ressource modifiée avec succès.']);
        }

        return $this->json(['errors' => $errors], 400);
    }

        
       /* #[Route('mesparcours', name: 'mesparcours')]
        public function mesparcours(
            ParcoursRepository $parcoursRepository
        ): Response
        {
            $user = $this->getUser();
            if (!$user) {
                $this->addFlash('error', 'Vous devez être connecté pour voir vos parcours.');
                return $this->redirectToRoute('front_home');
            }

            $parcours = $parcoursRepository->findBy(['utilisateur' => $user]);

            return $this->render('front/mesparcours.html.twig', [
                'parcours' => $parcours,
            ]);
        }
*/




}

