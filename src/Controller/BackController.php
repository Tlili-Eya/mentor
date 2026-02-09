<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\Ressource;
use App\Repository\ProjetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Repository\FeedbackRepository;
use App\Repository\TraitementRepository;
use App\Repository\UtilisateurRepository;
use App\Entity\Feedback;
use App\Entity\Traitement;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Parcours;
use App\Repository\ParcoursRepository;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/admin', name: 'back_')]
final class BackController extends AbstractController
{
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



    #[Route('/course-details', name: 'course_details')]
    public function courseDetails(): Response
    {
        return $this->render('back/course-details.html.twig');
    }

#[Route('/projets', name: 'projets')]
public function projets(ProjetRepository $projetRepository, Request $request): Response
{
    // Récupérer les paramètres de recherche et tri
    $search = $request->query->get('search', '');
    $sort = $request->query->get('sort', '');
    
    // Utiliser la nouvelle méthode du repository
    $projets = $projetRepository->findBySearchAndSort($search, $sort);
    
    return $this->render('back/projets.html.twig', [
        'projets' => $projets,
    ]);
}

    #[Route('/projets/export-pdf', name: 'projets_export_pdf', methods: ['GET'])]
    public function exportProjetsPdf(ProjetRepository $projetRepository, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', '');

        $projets = $projetRepository->findBySearchAndSort($search, $sort);

        // Render HTML for PDF
        $html = $this->renderView('back/projets_pdf.html.twig', [
            'projets' => $projets,
        ]);

        if (!class_exists(Dompdf::class)) {
            $this->addFlash('error', 'Dompdf non installé. Exécutez : composer require dompdf/dompdf');
            return $this->redirectToRoute('back_projets');
        }

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $output = $dompdf->output();

        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="projets.pdf"'
        ]);
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
     * Analyse des messages répétitifs (ALERTE)
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
     * AVEC TRI, RECHERCHE UTILISATEUR, et ANALYSE DES MESSAGES
     */
    #[Route('/contact', name: 'contact')]
    public function contact(
        Request $request,
        FeedbackRepository $feedbackRepo,
        UtilisateurRepository $userRepo
    ): Response {
        // RECHERCHE par utilisateur (nom/prénom/email)
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
        
        // FILTRAGE par utilisateur si recherche
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
        
        // ANALYSE des messages répétitifs (ALERTE)
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
     * PAGE 2 : Formulaire de traitement d'un feedback
     * ✅ UTILISE LA VALIDATION PHP DES ENTITÉS
     */
    #[Route('/traitement/{id}', name: 'traitement', methods: ['GET', 'POST'])]
    public function traitement(
        Request $request,
        Feedback $feedback,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        // Vérifier si le feedback a déjà un traitement
        $traitement = $feedback->getTraitement();

        // Si c'est une requête POST, enregistrer le traitement
        if ($request->isMethod('POST')) {
            $typeTraitement = trim($request->request->get('type_traitement'));
            $decision = trim($request->request->get('decision'));

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

            // ✅ VALIDATION PHP via les contraintes de l'entité
            $errors = $validator->validate($traitement);
            
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->redirectToRoute('back_traitement', ['id' => $feedback->getId()]);
            }

            // Mettre à jour l'état du feedback
            $feedback->setEtatfeedback('traite');

            // Sauvegarder
            $em->persist($traitement);
            $em->flush();

            $this->addFlash('success', 'Traitement enregistré avec succès !');
            return $this->redirectToRoute('back_contact');
        }

        // Afficher le formulaire
        return $this->render('back/traitement.html.twig', [
            'feedback' => $feedback,
            'traitement' => $traitement,
        ]);
    }

    /**
     * MODIFIER un traitement existant
     * ✅ UTILISE LA VALIDATION PHP DES ENTITÉS
     */
    #[Route('/traitement/{id}/edit', name: 'traitement_edit', methods: ['GET', 'POST'])]
    public function editTraitement(
        Request $request,
        Traitement $traitement,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        $feedback = $traitement->getFeedback();

        // Si c'est une requête POST, mettre à jour
        if ($request->isMethod('POST')) {
            $typeTraitement = trim($request->request->get('type_traitement'));
            $decision = trim($request->request->get('decision'));

            $traitement->setTypetraitement($typeTraitement);
            $traitement->setDescription($decision);
            $traitement->setDecision($typeTraitement);
            $traitement->setDatetraitement(new \DateTime());

            // ✅ VALIDATION PHP via les contraintes de l'entité
            $errors = $validator->validate($traitement);
            
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->redirectToRoute('back_traitement_edit', ['id' => $traitement->getId()]);
            }

            $em->flush();

            $this->addFlash('success', 'Traitement modifié avec succès !');
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

    /**
     * EXPORT PDF (routes factices si PdfExportService n'existe pas)
     */
    #[Route('/feedback/{id}/export-pdf', name: 'feedback_export_pdf')]
    public function exportFeedbackPdf(Feedback $feedback): Response
    {
        $this->addFlash('info', 'Fonctionnalité d\'export PDF en cours de développement.');
        return $this->redirectToRoute('back_contact');
    }

    #[Route('/feedbacks/export-all-pdf', name: 'feedbacks_export_all_pdf')]
    public function exportAllFeedbacksPdf(FeedbackRepository $feedbackRepo): Response
    {
        $this->addFlash('info', 'Fonctionnalité d\'export PDF en cours de développement.');
        return $this->redirectToRoute('back_contact');
    }

    #[Route('/update-projet/{id}', name: 'update_projet', methods: ['POST'])]
    public function updateProjet(Request $request, Projet $projet, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $projet->setTitre($data['titre'] ?? $projet->getTitre());
        $projet->setType($data['type'] ?? $projet->getType());
        $projet->setDescription($data['description'] ?? $projet->getDescription());
        $projet->setTechnologies($data['technologies'] ?? $projet->getTechnologies());
        $entityManager->flush();

        return new JsonResponse(['status' => 'Projet mis à jour avec succès']);
    }

    #[Route('/delete-projet/{id}', name: 'delete_projet', methods: ['DELETE'])]
    public function deleteProjet(Projet $projet, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($projet);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Projet supprimé avec succès']);
    }

    #[Route('/update-ressource/{id}', name: 'update_ressource', methods: ['POST'])]
    public function updateRessource(Request $request, Ressource $ressource, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ressource->setNom($data['nom'] ?? $ressource->getNom());
        $ressource->setDescription($data['description'] ?? $ressource->getDescription());
        $ressource->setUrlRessource($data['urlRessource'] ?? $ressource->getUrlRessource());
        $entityManager->flush();

        return new JsonResponse(['status' => 'Ressource mise à jour avec succès']);
    }

    #[Route('/delete-ressource/{id}', name: 'delete_ressource', methods: ['DELETE'])]
    public function deleteRessource(Ressource $ressource, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($ressource);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Ressource supprimée avec succès']);
    }


    // BackController.php
#[Route('/parcours', name: 'parcours')]
public function parcours(ParcoursRepository $parcoursRepository, Request $request): Response
{
    // Récupérer les paramètres de recherche et tri
    $search = $request->query->get('search', '');
    $sort = $request->query->get('sort', '');
    
    // Utiliser une nouvelle méthode du repository
    $parcours = $parcoursRepository->findBySearchAndSort($search, $sort);
    
    return $this->render('back/parcours.html.twig', [
        'parcours' => $parcours,
    ]);
}

    #[Route('/parcours/export-pdf', name: 'parcours_export_pdf', methods: ['GET'])]
    public function exportParcoursPdf(ParcoursRepository $parcoursRepository, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', '');

        $parcours = $parcoursRepository->findBySearchAndSort($search, $sort);

        $html = $this->renderView('back/parcours_pdf.html.twig', [
            'parcours' => $parcours,
        ]);

        if (!class_exists(Dompdf::class)) {
            $this->addFlash('error', 'Dompdf non installé. Exécutez : composer require dompdf/dompdf');
            return $this->redirectToRoute('back_parcours');
        }

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $output = $dompdf->output();

        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="parcours.pdf"'
        ]);
    }

#[Route('/update-parcours/{id}', name: 'update_parcours', methods: ['POST'])]
public function updateParcours(Request $request, Parcours $parcours, EntityManagerInterface $entityManager): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    
    // Mettre à jour les champs de base
    $parcours->setTitre($data['titre'] ?? $parcours->getTitre());
    $parcours->setTypeParcours($data['type_parcours'] ?? $parcours->getTypeParcours());
    $parcours->setDescription($data['description'] ?? $parcours->getDescription());
    
    // Mettre à jour les champs spécifiques
    if (isset($data['etablissement'])) {
        $parcours->setEtablissement($data['etablissement']);
    }
    if (isset($data['diplome'])) {
        $parcours->setDiplome($data['diplome']);
    }
    if (isset($data['specialite'])) {
        $parcours->setSpecialite($data['specialite']);
    }
    if (isset($data['entreprise'])) {
        $parcours->setEntreprise($data['entreprise']);
    }
    if (isset($data['poste'])) {
        $parcours->setPoste($data['poste']);
    }
    if (isset($data['type_contrat'])) {
        $parcours->setTypeContrat($data['type_contrat']);
    }
    
    // Mettre à jour les dates
    if (isset($data['date_debut']) && !empty($data['date_debut'])) {
        try {
            $parcours->setDateDebut(new \DateTime($data['date_debut']));
        } catch (\Exception $e) {
            // Gérer l'erreur si nécessaire
        }
    }
    
    if (isset($data['date_fin']) && !empty($data['date_fin'])) {
        try {
            $parcours->setDateFin(new \DateTime($data['date_fin']));
        } catch (\Exception $e) {
            // Gérer l'erreur si nécessaire
        }
    }
    
    $entityManager->flush();

    return new JsonResponse(['status' => 'Parcours mis à jour avec succès']);
}

#[Route('/delete-parcours/{id}', name: 'delete_parcours', methods: ['DELETE'])]
public function deleteParcours(Parcours $parcours, EntityManagerInterface $entityManager): JsonResponse
{
    $entityManager->remove($parcours);
    $entityManager->flush();

    return new JsonResponse(['status' => 'Parcours supprimé avec succès']);
}
}
