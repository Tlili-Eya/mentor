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
                return $this->render('utilisateur/new.html.twig', [
                    'utilisateur' => $utilisateur,
                    'form' => $form,
                ]);
            }

            if (!$utilisateur->getDateInscription()) {
                $utilisateur->setDateInscription(new \DateTime());
            }

            if (!$utilisateur->getRole()) {
                $utilisateur->setRole('etudiant');
            }

            $entityManager->persist($utilisateur);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');

            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/new.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

    // ✅ CRITIQUE: /profil DOIT ÊTRE AVANT /{id} pour éviter les conflits de routes
    #[Route('/profil', name: 'app_profil', methods: ['GET'])]
    public function profil(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('utilisateur/show.html.twig', [
            'utilisateur' => $user,
        ]);
    }

    // ✅ ROUTE SPÉCIFIQUE /{id}/edit AVANT la route générique /{id}
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

            $this->addFlash('success', 'Profil mis à jour avec succès !');

            return $this->redirectToRoute('back_administrateur', [], Response::HTTP_SEE_OTHER);
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
            $entityManager->remove($utilisateur);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Erreur de sécurité. Veuillez réessayer.');
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
