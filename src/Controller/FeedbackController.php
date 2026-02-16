<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Repository\FeedbackRepository;
use App\Repository\UtilisateurRepository;
use App\Service\EmailNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/', name: 'front_')]
final class FeedbackController extends AbstractController
{
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

    /**
     * ✅ HELPER : Calculer le nombre de nouveaux feedbacks traités (non vus)
     * 
     * Cette méthode compare les feedbacks traités avec ceux déjà vus en session
     * pour déterminer combien de nouveaux feedbacks traités existent.
     * 
     * @param Request $request Pour accéder à la session
     * @param FeedbackRepository $repo Pour récupérer les feedbacks
     * @param mixed $user L'utilisateur connecté
     * @return int Le nombre de nouveaux feedbacks traités
     */
    private function getNewTreatedFeedbackCount(
        Request $request,
        FeedbackRepository $repo,
        $user
    ): int {
        // Récupérer tous les feedbacks de l'utilisateur
        $feedbacks = $repo->findBy(['utilisateur' => $user]);
        
        // Récupérer les IDs des feedbacks déjà vus depuis la session
        $session = $request->getSession();
        $seenFeedbackIds = $session->get('seen_treated_feedbacks', []);
        
        // Compter les feedbacks traités qui ne sont pas encore vus
        $newTreatedCount = 0;
        foreach ($feedbacks as $feedback) {
            $etat = strtolower($feedback->getEtatfeedback() ?? '');
            if (($etat === 'traite' || $etat === 'traité') && !in_array($feedback->getId(), $seenFeedbackIds)) {
                $newTreatedCount++;
            }
        }
        
        return $newTreatedCount;
    }

    // ============================================================
    // CRUD FEEDBACK (avec utilisateur mocké + EMAIL)
    // ============================================================

    /**
     * AJOUT FEEDBACK
     * ✅ UTILISE LA VALIDATION PHP DES ENTITÉS
     * ✅ ENVOIE UN EMAIL DE CONFIRMATION
     * 
     * TEMPORAIRE : Utilise getMockUser()
     * APRÈS INTÉGRATION : Remplace par $this->getUser()
     */
    #[Route('feedback/add', name: 'feedback_add', methods: ['POST'])]
    public function addFeedback(
        Request $request,
        EntityManagerInterface $em,
        UtilisateurRepository $userRepo,  // ← TEMPORAIRE, à retirer après
        ValidatorInterface $validator,
        FeedbackRepository $feedbackRepo,
        EmailNotificationService $emailService  // ✅ SERVICE EMAIL
    ): Response {
        // Créer une nouvelle instance de Feedback
        $feedback = new Feedback();

        // Récupérer les données du formulaire
        $typeFeedback = $request->request->get('type_feedback');
        $contenu = $request->request->get('contenu');
        $rating = $request->request->get('rating');

        $feedback->setTypefeedback($typeFeedback);
        $feedback->setContenu($contenu);
        $feedback->setNote((int)$rating);
        $feedback->setEtatfeedback('en_attente');
        $feedback->setDatefeedback(new \DateTime());
        
        // 🚨 TEMPORAIRE : Utilise un utilisateur mocké
        // APRÈS : Remplace par $feedback->setUtilisateur($this->getUser());
        $user = $this->getMockUser($userRepo);
        $feedback->setUtilisateur($user);

        // ✅ VALIDATION PHP via les contraintes de l'entité
        $errors = $validator->validate($feedback);
        
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('front_contact');
        }

        // Persister et sauvegarder
        $em->persist($feedback);
        $em->flush();

        // ✅ ENVOYER L'EMAIL DE CONFIRMATION
        try {
            $emailService->sendFeedbackReceivedNotification($feedback);
            $this->addFlash('success', 'Votre feedback a été envoyé avec succès ! Vous allez recevoir un email de confirmation.');
        } catch (\Exception $e) {
            // Si l'email échoue, le feedback est quand même enregistré
            $this->addFlash('success', 'Votre feedback a été envoyé avec succès !');
            $this->addFlash('warning', 'Note : L\'email de confirmation n\'a pas pu être envoyé.');
            
            // Log l'erreur pour debug (optionnel)
            // error_log('Email error: ' . $e->getMessage());
        }

        // Rediriger vers la liste
        return $this->redirectToRoute('front_feedback_list');
    }

    /**
     * LISTE FEEDBACK
     * 
     * ✅ NOUVELLE FONCTIONNALITÉ : Notification Facebook-like
     * - Compte les nouveaux feedbacks traités
     * - Marque comme "vus" après visite de la liste
     * - Utilise la session (pas de DB)
     * 
     * ✅ NOUVELLE FONCTIONNALITÉ : Search & Sort
     * - Recherche par contenu du message (case-insensitive, partial match)
     * - Tri par date (DESC par défaut, ASC optionnel)
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

        // ✅ Get search and sort parameters from request
        $searchTerm = $request->query->get('search', '');
        $sortOrder = $request->query->get('sort', 'DESC'); // DESC = newest first, ASC = oldest first

        // Validate sort order
        if (!in_array($sortOrder, ['DESC', 'ASC'])) {
            $sortOrder = 'DESC';
        }

        // ✅ Use repository method with search and sort
        $feedbacks = $repo->searchByUser($user, $searchTerm, $sortOrder);

        // ✅ NOTIFICATION FACEBOOK-LIKE : Marquer les feedbacks traités comme "vus"
        // Récupérer la session
        $session = $request->getSession();
        
        // Récupérer les IDs des feedbacks déjà vus
        $seenFeedbackIds = $session->get('seen_treated_feedbacks', []);
        
        // Parcourir les feedbacks traités et les marquer comme vus
        foreach ($feedbacks as $feedback) {
            $etat = strtolower($feedback->getEtatfeedback() ?? '');
            if (($etat === 'traite' || $etat === 'traité') && !in_array($feedback->getId(), $seenFeedbackIds)) {
                // Ajouter à la liste des feedbacks vus
                $seenFeedbackIds[] = $feedback->getId();
            }
        }
        
        // Sauvegarder dans la session
        $session->set('seen_treated_feedbacks', $seenFeedbackIds);

        return $this->render('front/feedback_list.html.twig', [
            'feedbacks' => $feedbacks,
            'newTreatedCount' => 0,  // Toujours 0 ici car on vient de tout marquer comme vu
            'searchTerm' => $searchTerm,
            'sortOrder' => $sortOrder
        ]);
    }

    /**
     * MODIFIER FEEDBACK
     * ✅ UTILISE LA VALIDATION PHP DES ENTITÉS
     * ✅ NOUVELLE LOGIQUE : Modifiable SEULEMENT si "en_attente"
     * 
     * TEMPORAIRE : Utilise getMockUser()
     * APRÈS INTÉGRATION : Remplace par $this->getUser()
     */
    #[Route('feedback/{id}/edit', name: 'feedback_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Feedback $feedback,
        EntityManagerInterface $em,
        UtilisateurRepository $userRepo,  // ← TEMPORAIRE, à retirer après
        ValidatorInterface $validator
    ): Response {
        // 🚨 TEMPORAIRE : Récupère un utilisateur mocké
        // APRÈS : Remplace par $user = $this->getUser();
        $user = $this->getMockUser($userRepo);

        // Vérifier que le feedback appartient à l'utilisateur
        if ($feedback->getUtilisateur() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier ce feedback.');
            return $this->redirectToRoute('front_feedback_list');
        }

        // ✅ NOUVELLE LOGIQUE : Vérifier si le feedback est modifiable
        // Modifiable SEULEMENT si "en_attente"
        $etat = strtolower($feedback->getEtatfeedback() ?? '');
        
        if ($etat === 'traite' || $etat === 'traité') {
            $this->addFlash('error', 'Ce feedback a déjà été traité et ne peut plus être modifié.');
            return $this->redirectToRoute('front_feedback_list');
        }

        // Si c'est une requête POST, enregistrer les modifications
        if ($request->isMethod('POST')) {
            $typeFeedback = $request->request->get('type_feedback');
            $contenu = $request->request->get('contenu');
            $rating = $request->request->get('rating');

            // Mettre à jour
            $feedback->setTypefeedback($typeFeedback);
            $feedback->setContenu($contenu);
            $feedback->setNote((int)$rating);

            // ✅ VALIDATION PHP via les contraintes de l'entité
            $errors = $validator->validate($feedback);
            
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->redirectToRoute('front_feedback_edit', ['id' => $feedback->getId()]);
            }

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
     * ✅ NOUVELLE LOGIQUE : Supprimable SEULEMENT si "en_attente"
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

        // ✅ NOUVELLE LOGIQUE : Vérifier si le feedback est supprimable
        // Supprimable SEULEMENT si "en_attente"
        $etat = strtolower($feedback->getEtatfeedback() ?? '');
        
        if ($etat === 'traite' || $etat === 'traité') {
            $this->addFlash('error', 'Ce feedback a déjà été traité et ne peut plus être supprimé.');
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
