<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\FeedbackRepository;
use App\Repository\UtilisateurRepository;
use App\Entity\Feedback;
use App\Service\EmailNotificationService;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/', name: 'front_')]
final class FrontController extends AbstractController
{
    public function __construct(
        private EmailNotificationService $emailNotificationService
    ) {}

    // ============================================================
    // 🚨 TEMPORAIRE - UTILISATEUR MOCKÉ POUR TESTS
    // ============================================================
    // À REMPLACER PAR $this->getUser() quand le vrai login sera intégré
    // ============================================================
    
    /**
     * Récupère un utilisateur temporaire pour les tests
     * 
     * IMPORTANT : Cette fonction est TEMPORAIRE !
     * Quand le vrai système de login sera intégré par Hejer :
     * 1. Supprime cette fonction
     * 2. Remplace tous les getMockUser() par $this->getUser()
     * 3. C'est tout !
     */
    private function getMockUser(UtilisateurRepository $userRepo)
    {
        // CHANGE L'ID ICI pour tester avec un autre utilisateur
        $userId = 2; // ← Change cet ID selon l'utilisateur que tu veux simuler
        
        $user = $userRepo->find($userId);
        
        if (!$user) {
            throw new \Exception("Utilisateur #$userId n'existe pas ! Crée-le dans la base ou change l'ID dans getMockUser()");
        }
        
        return $user;
    }
    
    // ============================================================
    // ROUTES NORMALES
    // ============================================================

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

    #[Route('courses', name: 'courses')]
    public function courses(): Response
    {
        return $this->render('front/courses.html.twig');
    }

    #[Route('course-details', name: 'course_details')]
    public function courseDetails(): Response
    {
        return $this->render('front/course-details.html.twig');
    }

    #[Route('instructors', name: 'instructors')]
    public function instructors(): Response
    {
        return $this->render('front/instructors.html.twig');
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
    public function contact(): Response
    {
        return $this->render('front/contact.html.twig');
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

    // ============================================================
    // CRUD FEEDBACK (avec utilisateur mocké)
    // ============================================================

    /**
     * ✨ VALIDATION PHP pour le feedback
     */
    private function validateFeedbackData(string $typeFeedback, string $contenu, $rating): array
    {
        $errors = [];
        
        // Type de feedback
        $typesValides = ['suggestion', 'probleme', 'satisfaction'];
        if (empty($typeFeedback)) {
            $errors[] = "Le type de feedback est obligatoire.";
        } elseif (!in_array($typeFeedback, $typesValides)) {
            $errors[] = "Type de feedback invalide. Choisissez parmi : suggestion, problème, satisfaction.";
        }
        
        // Contenu
        if (empty($contenu)) {
            $errors[] = "Le message est obligatoire.";
        } elseif (strlen($contenu) < 10) {
            $errors[] = "Le message doit contenir au moins 10 caractères.";
        } elseif (strlen($contenu) > 2000) {
            $errors[] = "Le message ne peut pas dépasser 2000 caractères.";
        }
        
        // Note
        if (empty($rating)) {
            $errors[] = "La note est obligatoire.";
        } elseif (!is_numeric($rating)) {
            $errors[] = "La note doit être un nombre.";
        } elseif ($rating < 1 || $rating > 5) {
            $errors[] = "La note doit être entre 1 et 5.";
        }
        
        return $errors;
    }

    /**
     * AJOUT FEEDBACK (avec validation PHP + NOTIFICATION EMAIL)
     * 
     * TEMPORAIRE : Utilise getMockUser()
     * APRÈS INTÉGRATION : Remplace par $this->getUser()
     */
    #[Route('feedback/add', name: 'feedback_add', methods: ['POST'])]
    public function addFeedback(
        Request $request,
        EntityManagerInterface $em,
        UtilisateurRepository $userRepo  // ← TEMPORAIRE, à retirer après
    ): Response {
        // Récupérer les données du formulaire
        $typeFeedback = trim($request->request->get('type_feedback'));
        $contenu = trim($request->request->get('contenu'));
        $rating = $request->request->get('rating');

        // ✨ VALIDATION PHP
        $errors = $this->validateFeedbackData($typeFeedback, $contenu, $rating);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('front_contact');
        }

        // Créer le feedback
        $feedback = new Feedback();
        $feedback->setTypefeedback($typeFeedback);
        $feedback->setContenu($contenu);
        $feedback->setNote((int)$rating);
        $feedback->setEtatfeedback('en_attente');
        $feedback->setDatefeedback(new \DateTime());
        
        // 🚨 TEMPORAIRE : Utilise un utilisateur mocké
        // APRÈS : Remplace par $feedback->setUtilisateur($this->getUser());
        $feedback->setUtilisateur($this->getMockUser($userRepo));

        // Sauvegarder
        $em->persist($feedback);
        $em->flush();

        // ✨ ENVOYER L'EMAIL DE CONFIRMATION
        try {
            $this->emailNotificationService->sendFeedbackReceivedNotification($feedback);
            $this->addFlash('success', 'Votre feedback a été envoyé avec succès ! Vous recevrez un email de confirmation.');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Feedback envoyé, mais l\'email de confirmation n\'a pas pu être envoyé.');
        }

        return $this->redirectToRoute('front_feedback_list');
    }

    /**
     * LISTE FEEDBACK (avec TRI et RECHERCHE)
     * 
     * TEMPORAIRE : Utilise getMockUser()
     * APRÈS INTÉGRATION : Remplace par $this->getUser()
     */
    #[Route('feedback/list', name: 'feedback_list')]
    public function feedbackList(
        Request $request,
        FeedbackRepository $repo,
        UtilisateurRepository $userRepo  // ← TEMPORAIRE, à retirer après
    ): Response {
        // 🚨 TEMPORAIRE : Récupère un utilisateur mocké
        // APRÈS : Remplace par $user = $this->getUser();
        $user = $this->getMockUser($userRepo);

        // Récupération des paramètres de tri et recherche
        $sortBy = $request->query->get('sort', 'date_desc'); // Par défaut : date décroissante
        $search = trim($request->query->get('search', ''));

        // Récupérer tous les feedbacks de l'utilisateur
        $feedbacks = $repo->findBy(
            ['utilisateur' => $user]
        );

        // ✨ RECHERCHE par mot-clé dans le contenu
        if (!empty($search)) {
            $feedbacks = array_filter($feedbacks, function($feedback) use ($search) {
                return stripos($feedback->getContenu(), $search) !== false 
                    || stripos($feedback->getTypefeedback(), $search) !== false;
            });
        }

        // ✨ TRI
        usort($feedbacks, function($a, $b) use ($sortBy) {
            switch ($sortBy) {
                case 'date_asc':
                    return $a->getDatefeedback() <=> $b->getDatefeedback();
                case 'date_desc':
                    return $b->getDatefeedback() <=> $a->getDatefeedback();
                case 'note_asc':
                    return $a->getNote() <=> $b->getNote();
                case 'note_desc':
                    return $b->getNote() <=> $a->getNote();
                default:
                    return $b->getDatefeedback() <=> $a->getDatefeedback();
            }
        });

        return $this->render('front/feedback_list.html.twig', [
            'feedbacks' => $feedbacks,
            'currentSort' => $sortBy,
            'currentSearch' => $search,
        ]);
    }

    /**
     * MODIFIER FEEDBACK (avec validation PHP)
     * 
     * TEMPORAIRE : Utilise getMockUser()
     * APRÈS INTÉGRATION : Remplace par $this->getUser()
     */
    #[Route('feedback/{id}/edit', name: 'feedback_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Feedback $feedback,
        EntityManagerInterface $em,
        UtilisateurRepository $userRepo  // ← TEMPORAIRE, à retirer après
    ): Response {
        // 🚨 TEMPORAIRE : Récupère un utilisateur mocké
        // APRÈS : Remplace par $user = $this->getUser();
        $user = $this->getMockUser($userRepo);

        // Vérifier que le feedback appartient à l'utilisateur
        if ($feedback->getUtilisateur() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier ce feedback.');
            return $this->redirectToRoute('front_feedback_list');
        }

        // Vérifier si le feedback est modifiable (seulement si "traité")
        $etat = strtolower($feedback->getEtatfeedback() ?? '');
        if ($etat !== 'traite' && $etat !== 'traité') {
            $this->addFlash('error', 'Ce feedback ne peut pas être modifié. Statut actuel : ' . $feedback->getEtatfeedback());
            return $this->redirectToRoute('front_feedback_list');
        }

        // Si c'est une requête POST, enregistrer les modifications
        if ($request->isMethod('POST')) {
            $typeFeedback = trim($request->request->get('type_feedback'));
            $contenu = trim($request->request->get('contenu'));
            $rating = $request->request->get('rating');

            // ✨ VALIDATION PHP
            $errors = $this->validateFeedbackData($typeFeedback, $contenu, $rating);
            
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('front_feedback_edit', ['id' => $feedback->getId()]);
            }

            // Mettre à jour
            $feedback->setTypefeedback($typeFeedback);
            $feedback->setContenu($contenu);
            $feedback->setNote((int)$rating);

            $em->flush();

            $this->addFlash('success', 'Feedback modifié avec succès !');
            return $this->redirectToRoute('front_feedback_list');
        }

        // Afficher le formulaire de modification
        return $this->render('front/edit.html.twig', [
            'feedback' => $feedback,
        ]);
    }

    /**
     * SUPPRIMER FEEDBACK
     * 
     * TEMPORAIRE : Utilise getMockUser()
     * APRÈS INTÉGRATION : Remplace par $this->getUser()
     */
    #[Route('feedback/{id}/delete', name: 'feedback_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Feedback $feedback,
        EntityManagerInterface $em,
        UtilisateurRepository $userRepo  // ← TEMPORAIRE, à retirer après
    ): Response {
        // 🚨 TEMPORAIRE : Récupère un utilisateur mocké
        // APRÈS : Remplace par $user = $this->getUser();
        $user = $this->getMockUser($userRepo);

        // Vérifier que le feedback appartient à l'utilisateur
        if ($feedback->getUtilisateur() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce feedback.');
            return $this->redirectToRoute('front_feedback_list');
        }

        // Vérifier si le feedback est supprimable (seulement si "traité")
        $etat = strtolower($feedback->getEtatfeedback() ?? '');
        if ($etat !== 'traite' && $etat !== 'traité') {
            $this->addFlash('error', 'Ce feedback ne peut pas être supprimé. Statut actuel : ' . $feedback->getEtatfeedback());
            return $this->redirectToRoute('front_feedback_list');
        }

        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $feedback->getId(), $token)) {
            $em->remove($feedback);
            $em->flush();
            $this->addFlash('success', 'Feedback supprimé avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('front_feedback_list');
    }
}
