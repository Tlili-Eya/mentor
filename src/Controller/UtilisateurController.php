<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/utilisateur')]
final class UtilisateurController extends AbstractController
{
    #[Route(name: 'app_utilisateur_index', methods: ['GET'])]
    public function index(UtilisateurRepository $utilisateurRepository): Response
    {
        return $this->render('utilisateur/index.html.twig', [
            'utilisateurs' => $utilisateurRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_utilisateur_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('mdp')->getData();
            
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($utilisateur, $plainPassword);
                $utilisateur->setMdp($hashedPassword);
            } else {
                $this->addFlash('error', 'Le mot de passe est obligatoire pour créer un compte.');
                
                // ✅ MODIFIÉ: Redirection vers administrateur même en cas d'erreur
                return $this->redirectToRoute('back_administrateur', [], Response::HTTP_SEE_OTHER);
            }

            if (!$utilisateur->getDateInscription()) {
                $utilisateur->setDateInscription(new \DateTime());
            }

            if (!$utilisateur->getRole()) {
                $utilisateur->setRole('etudiant');
            }

            $entityManager->persist($utilisateur);
            $entityManager->flush();

            // ✅ MODIFIÉ: Message de succès et redirection vers administrateur
            $this->addFlash('success', 'Instructor created successfully!');

            return $this->redirectToRoute('back_administrateur', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/new.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

    // ✅ CRITIQUE: /profil DOIT ÊTRE AVANT /{id} pour éviter les conflits de routes
    #[Route('/profil', name: 'app_profil', methods: ['GET', 'POST'])]
    public function profil(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Créer le formulaire de profil (différent de UtilisateurType pour la sécurité)
        $form = $this->createForm(\App\Form\ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hacher le mot de passe seulement s'il est modifié
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setMdp($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès !');

            return $this->redirectToRoute('app_profil', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/profil.html.twig', [
            'utilisateur' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_utilisateur_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Request $request, 
        Utilisateur $utilisateur, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('mdp')->getData();
            
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($utilisateur, $plainPassword);
                $utilisateur->setMdp($hashedPassword);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Instructor updated successfully!');

            // ✅ Si c'est une requête AJAX (depuis le modal), on renvoie du JSON
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['status' => 'success']);
            }

            // ✅ Sinon redirection classique
            return $this->redirectToRoute('back_administrateur', [], Response::HTTP_SEE_OTHER);
        }

        // Si le formulaire est soumis mais invalide en AJAX
        if ($request->isXmlHttpRequest() && $form->isSubmitted() && !$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }

            return new JsonResponse([
                'status' => 'error',
                'message' => implode(' ', $errors) ?: 'Validation error. Please check your data.'
            ], 422);
        }

        return $this->render('utilisateur/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

    // ✅ ROUTE DELETE SÉPARÉE avec validation CSRF
    #[Route('/{id}/delete', name: 'app_utilisateur_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        // ✅ Vérification du token CSRF
        $token = $request->request->get('_token');
        
        if ($this->isCsrfTokenValid('delete'.$utilisateur->getId(), $token)) {
            // ✅ MODIFIÉ: Changement de statut textuel
            $utilisateur->setStatus('desactiver');
            $entityManager->flush();

            // Si l'utilisateur se supprime lui-même, on le déconnecte
            if ($this->getUser() === $utilisateur) {
                $request->getSession()->invalidate();
                $this->container->get('security.token_storage')->setToken(null);
                return $this->redirectToRoute('app_login');
            }

            $this->addFlash('success', 'Compte désactivé avec succès.');
        } else {
            $this->addFlash('error', 'Security error. Please try again.');
        }

        return $this->redirectToRoute('back_administrateur', [], Response::HTTP_SEE_OTHER);
    }

    // ✅ ROUTE GÉNÉRIQUE /{id} DOIT ÊTRE EN DERNIER (sinon elle capture tout)
    #[Route('/{id}', name: 'app_utilisateur_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Utilisateur $utilisateur): Response
    {
        return $this->render('utilisateur/show.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }
}
