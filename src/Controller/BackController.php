<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\FeedbackRepository;
use App\Repository\TraitementRepository;
use App\Repository\UtilisateurRepository;
use App\Entity\Feedback;
use App\Entity\Traitement;
use App\Service\PdfExportService;
use App\Service\EmailNotificationService;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/admin', name: 'back_')]
final class BackController extends AbstractController
{
    public function __construct(
        private PdfExportService $pdfExportService,
        private EmailNotificationService $emailNotificationService
    ) {}
    
    #[Route('', name: 'home')]
    public function home(): Response
    {
        return $this->render('back/home.html.twig');
    }

    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('back/about.html.twig');
    }

    #[Route('/courses', name: 'courses')]
    public function courses(): Response
    {
        return $this->render('back/courses.html.twig');
    }

    #[Route('/course-details', name: 'course_details')]
    public function courseDetails(): Response
    {
        return $this->render('back/course-details.html.twig');
    }

    #[Route('/instructors', name: 'instructors')]
    public function instructors(): Response
    {
        return $this->render('back/instructors.html.twig');
    }

    #[Route('/instructor-profile', name: 'instructor_profile')]
    public function instructorProfile(): Response
    {
        return $this->render('back/instructor-profile.html.twig');
    }

    #[Route('/events', name: 'events')]
    public function events(): Response
    {
        return $this->render('back/events.html.twig');
    }

    #[Route('/pricing', name: 'pricing')]
    public function pricing(): Response
    {
        return $this->render('back/pricing.html.twig');
    }

    #[Route('/blog', name: 'blog')]
    public function blog(): Response
    {
        return $this->render('back/blog.html.twig');
    }

    #[Route('/blog-details', name: 'blog_details')]
    public function blogDetails(): Response
    {
        return $this->render('back/blog-details.html.twig');
    }

    // ============================================================
    // GESTION DES FEEDBACKS
    // ============================================================

    /**
     * ✨ FONCTION : Analyse des messages répétitifs (ALERTE)
     */
    private function analyzeRepetitiveMessages(array $feedbacks): array
    {
        $mots = [];
        
        foreach ($feedbacks as $feedback) {
            $contenu = strtolower($feedback->getContenu());
            
            // Découper en mots et compter les occurrences
            $motsContenu = preg_split('/\s+/', $contenu);
            foreach ($motsContenu as $mot) {
                // Nettoyer les ponctuations
                $mot = preg_replace('/[^a-zàâäéèêëïîôùûüç0-9]/u', '', $mot);
                
                // Ignorer les mots trop courts
                if (strlen($mot) < 4) continue;
                
                if (!isset($mots[$mot])) {
                    $mots[$mot] = 0;
                }
                $mots[$mot]++;
            }
        }
        
        // Trier par occurrence décroissante
        arsort($mots);
        
        // Retourner les 10 mots les plus répétés
        return array_slice($mots, 0, 10, true);
    }

    /**
     * PAGE 1 : Liste de tous les feedbacks (en_attente et traités)
     * ✨ AVEC TRI, RECHERCHE UTILISATEUR, et ANALYSE DES MESSAGES
     */
    #[Route('/contact', name: 'contact')]
    public function contact(
        Request $request,
        FeedbackRepository $feedbackRepo,
        UtilisateurRepository $userRepo
    ): Response {
        // ✨ RECHERCHE par utilisateur (nom/prénom/email)
        $searchUser = trim($request->query->get('search_user', ''));
        $showOnlyUntreated = $request->query->get('only_untreated', false);
        
        // Récupérer tous les feedbacks en attente
        $feedbacksEnAttente = $feedbackRepo->findBy(
            ['etatfeedback' => 'en_attente'],
            ['datefeedback' => 'DESC']
        );

        // Récupérer tous les feedbacks traités
        $feedbacksTraites = $feedbackRepo->findBy(
            ['etatfeedback' => 'traite'],
            ['datefeedback' => 'DESC']
        );
        
        // ✨ FILTRAGE par utilisateur si recherche
        if (!empty($searchUser)) {
            $feedbacksEnAttente = array_filter($feedbacksEnAttente, function($feedback) use ($searchUser) {
                $user = $feedback->getUtilisateur();
                if (!$user) return false;
                
                return stripos($user->getNom(), $searchUser) !== false
                    || stripos($user->getPrenom(), $searchUser) !== false
                    || stripos($user->getEmail(), $searchUser) !== false;
            });
            
            if (!$showOnlyUntreated) {
                $feedbacksTraites = array_filter($feedbacksTraites, function($feedback) use ($searchUser) {
                    $user = $feedback->getUtilisateur();
                    if (!$user) return false;
                    
                    return stripos($user->getNom(), $searchUser) !== false
                        || stripos($user->getPrenom(), $searchUser) !== false
                        || stripos($user->getEmail(), $searchUser) !== false;
                });
            } else {
                // Si "only_untreated" est activé, on vide les feedbacks traités
                $feedbacksTraites = [];
            }
        }
        
        // ✨ ANALYSE des messages répétitifs (ALERTE)
        $allFeedbacks = array_merge($feedbacksEnAttente, $feedbacksTraites);
        $motsCles = $this->analyzeRepetitiveMessages($allFeedbacks);

        return $this->render('back/contact.html.twig', [
            'feedbacksEnAttente' => $feedbacksEnAttente,
            'feedbacksTraites' => $feedbacksTraites,
            'searchUser' => $searchUser,
            'motsCles' => $motsCles,
            'showOnlyUntreated' => $showOnlyUntreated,
        ]);
    }

    /**
     * ✨ VALIDATION PHP pour le traitement
     */
    private function validateTraitementData(string $typeTraitement, string $decision): array
    {
        $errors = [];
        
        // Type de traitement
        $typesValides = ['remboursement', 'prolongation_abonnement', 'geste_commercial', 'aucun_traitement'];
        if (empty($typeTraitement)) {
            $errors[] = "Le type de traitement est obligatoire.";
        } elseif (!in_array($typeTraitement, $typesValides)) {
            $errors[] = "Type de traitement invalide.";
        }
        
        // Décision/Description
        if (empty($decision)) {
            $errors[] = "La décision/description est obligatoire.";
        } elseif (strlen($decision) < 10) {
            $errors[] = "La description doit contenir au moins 10 caractères.";
        } elseif (strlen($decision) > 1000) {
            $errors[] = "La description ne peut pas dépasser 1000 caractères.";
        }
        
        return $errors;
    }

    /**
     * PAGE 2 : Formulaire de traitement d'un feedback (avec validation PHP)
     */
    #[Route('/traitement/{id}', name: 'traitement', methods: ['GET', 'POST'])]
    public function traitement(
        Request $request,
        Feedback $feedback,
        EntityManagerInterface $em
    ): Response {
        // Vérifier si le feedback a déjà un traitement
        $traitement = $feedback->getTraitement();

        // Si c'est une requête POST, enregistrer le traitement
        if ($request->isMethod('POST')) {
            $typeTraitement = trim($request->request->get('type_traitement'));
            $decision = trim($request->request->get('decision'));

            // ✨ VALIDATION PHP
            $errors = $this->validateTraitementData($typeTraitement, $decision);
            
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('back_traitement', ['id' => $feedback->getId()]);
            }

            // Si pas de traitement existant, en créer un nouveau
            if (!$traitement) {
                $traitement = new Traitement();
                $traitement->setFeedback($feedback);
            }

            // Remplir les données
            $traitement->setTypetraitement($typeTraitement);
            $traitement->setDescription($decision);
            $traitement->setDecision($typeTraitement);
            $traitement->setDatetraitement(new \DateTime());

            // Mettre à jour l'état du feedback
            $feedback->setEtatfeedback('traite');

            // Sauvegarder
            $em->persist($traitement);
            $em->flush();

            // ✨ ENVOYER LA NOTIFICATION EMAIL
            try {
                $this->emailNotificationService->sendFeedbackTreatedNotification($feedback);
                $this->addFlash('success', 'Traitement enregistré avec succès ! Email de notification envoyé à l\'utilisateur.');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Traitement enregistré, mais l\'email n\'a pas pu être envoyé : ' . $e->getMessage());
            }

            return $this->redirectToRoute('back_contact');
        }

        // Afficher le formulaire
        return $this->render('back/traitement.html.twig', [
            'feedback' => $feedback,
            'traitement' => $traitement,
        ]);
    }

    /**
     * MODIFIER un traitement existant (avec validation PHP)
     */
    #[Route('/traitement/{id}/edit', name: 'traitement_edit', methods: ['GET', 'POST'])]
    public function editTraitement(
        Request $request,
        Traitement $traitement,
        EntityManagerInterface $em
    ): Response {
        $feedback = $traitement->getFeedback();

        // Si c'est une requête POST, mettre à jour
        if ($request->isMethod('POST')) {
            $typeTraitement = trim($request->request->get('type_traitement'));
            $decision = trim($request->request->get('decision'));

            // ✨ VALIDATION PHP
            $errors = $this->validateTraitementData($typeTraitement, $decision);
            
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('back_traitement_edit', ['id' => $traitement->getId()]);
            }

            $traitement->setTypetraitement($typeTraitement);
            $traitement->setDescription($decision);
            $traitement->setDecision($typeTraitement);
            $traitement->setDatetraitement(new \DateTime());

            $em->flush();

            // ✨ ENVOYER UNE NOTIFICATION DE MISE À JOUR
            try {
                $this->emailNotificationService->sendFeedbackTreatedNotification($feedback);
                $this->addFlash('success', 'Traitement modifié avec succès ! Email de notification mis à jour.');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Traitement modifié, mais l\'email n\'a pas pu être envoyé : ' . $e->getMessage());
            }

            return $this->redirectToRoute('back_contact');
        }

        return $this->render('back/traitement_edit.html.twig', [
            'feedback' => $feedback,
            'traitement' => $traitement,
        ]);
    }

    /**
     * SUPPRIMER un traitement
     */
    #[Route('/traitement/{id}/delete', name: 'traitement_delete', methods: ['POST'])]
    public function deleteTraitement(
        Request $request,
        Traitement $traitement,
        EntityManagerInterface $em
    ): Response {
        // Récupérer le feedback associé
        $feedback = $traitement->getFeedback();

        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $traitement->getId(), $token)) {
            // Remettre le feedback en "en_attente"
            $feedback->setEtatfeedback('en_attente');

            // Supprimer le traitement
            $em->remove($traitement);
            $em->flush();

            $this->addFlash('success', 'Traitement supprimé avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('back_contact');
    }

    // ============================================================
    // ✨ NOUVELLES ROUTES POUR EXPORT PDF
    // ============================================================

    /**
     * Exporter un feedback spécifique en PDF
     */
    #[Route('/feedback/{id}/export-pdf', name: 'feedback_export_pdf')]
    public function exportFeedbackPdf(Feedback $feedback): Response
    {
        // Vérifier que le feedback est traité
        if ($feedback->getEtatfeedback() !== 'traite') {
            $this->addFlash('error', 'Seuls les feedbacks traités peuvent être exportés en PDF.');
            return $this->redirectToRoute('back_contact');
        }

        // Générer le PDF
        $pdfContent = $this->pdfExportService->generateFeedbackPdf($feedback);

        // Retourner le PDF en réponse
        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="feedback_' . $feedback->getId() . '.pdf"'
            ]
        );
    }

    /**
     * Exporter TOUS les feedbacks traités en PDF
     */
    #[Route('/feedbacks/export-all-pdf', name: 'feedbacks_export_all_pdf')]
    public function exportAllFeedbacksPdf(FeedbackRepository $feedbackRepo): Response
    {
        // Générer le PDF
        $pdfContent = $this->pdfExportService->generateAllFeedbacksPdf($feedbackRepo);

        // Retourner le PDF
        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="feedbacks_traites_' . date('Y-m-d') . '.pdf"'
            ]
        );
    }
}
