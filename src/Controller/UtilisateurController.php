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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($utilisateur);
            $entityManager->flush();

            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/new.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

    #[Route('/show', name: 'app_utilisateur_show', methods: ['GET'])]
    public function show(?Utilisateur $utilisateur): Response
    {
        if (!$utilisateur) {
           //this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('app_utilisateur_index');
        } 

        return $this->render('utilisateur/show.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }

    






    #[Route('/{id}/edit', name: 'app_utilisateur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ?Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        if (!$utilisateur) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('app_utilisateur_index');
        }

        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_utilisateur_delete', methods: ['POST'])]
    public function delete(Request $request, ?Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        if (!$utilisateur) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('app_utilisateur_index');
        }

        if ($this->isCsrfTokenValid('delete'.$utilisateur->getId(), $request->request->get('_token'))) {
            $entityManager->remove($utilisateur);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Inscription publique depuis la page authentifier.html.twig
     * Méthode GET : affiche la page (mais on utilise la vue authentifier)
     * Méthode POST : traite l'inscription
     */
    #[Route('/inscription', name: 'app_inscription', methods: ['GET', 'POST'])]
    public function inscription(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        UtilisateurRepository $utilisateurRepository
    ): Response {
        $utilisateur = new Utilisateur();
        // On met la date d'inscription immédiatement
        $utilisateur->setDateInscription(new \DateTime());

        $form = $this->createForm(UtilisateurType::class, $utilisateur, [
            'validation_groups' => ['Default', 'Registration'], // si tu veux des groupes de validation différents
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hashage du mot de passe
            $plainPassword = $form->get('mdp')->getData();
            if ($plainPassword) {
                $utilisateur->setMdp(
                    $passwordHasher->hashPassword($utilisateur, $plainPassword)
                );
            }

            // Gestion de l'upload de photo (champ non mappé dans l'entité)
            $pdpFile = $form->get('pdp_url')->getData();
            if ($pdpFile instanceof UploadedFile) {
                $newFilename = uniqid() . '.' . $pdpFile->guessExtension();
                try {
                    $pdpFile->move(
                        $this->getParameter('pdp_directory'),
                        $newFilename
                    );
                    $utilisateur->setPdpUrl($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de la photo.');
                    $this->addFlash('signup_active', true);
                    return $this->redirectToRoute('app_authentifier');
                }
            }

            // Vérification unicité email (avant persist)
            if ($utilisateurRepository->findOneBy(['email' => $utilisateur->getEmail()])) {
                $this->addFlash('email_error', 'Cet email est déjà utilisé.');
                $this->addFlash('signup_active', true);
                return $this->redirectToRoute('app_authentifier');
            }

            $entityManager->persist($utilisateur);
            $entityManager->flush();

            $this->addFlash('success', 'Compte créé avec succès ! Connectez-vous maintenant.');

            return $this->redirectToRoute('app_authentifier');
        }

        // En cas d'erreur ou GET : on renvoie vers la page authentifier
        // avec le formulaire pour afficher les erreurs
        return $this->render('authentifier.html.twig', [
            'inscriptionForm' => $form->createView(),
            // Tu peux passer d'autres variables si besoin
        ]);
    }
    // Dans UtilisateurController

/**
 * Page de connexion/inscription (affichage + traitement inscription)
 */
#[Route('/authentifier', name: 'app_authentifier', methods: ['GET', 'POST'])]
public function authentifier(
    Request $request,
    UserPasswordHasherInterface $passwordHasher,
    EntityManagerInterface $entityManager,
    UtilisateurRepository $utilisateurRepository
): Response
{
    $utilisateur = new Utilisateur();
    $utilisateur->setDateInscription(new \DateTime());

    $form = $this->createForm(UtilisateurType::class, $utilisateur);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Hash mot de passe
        $plainPassword = $form->get('mdp')->getData();
        if ($plainPassword) {
            $utilisateur->setMdp(
                $passwordHasher->hashPassword($utilisateur, $plainPassword)
            );
        }

        // Upload photo
        $pdpFile = $form->get('pdp_url')->getData();
        if ($pdpFile instanceof UploadedFile) {
            $newFilename = uniqid() . '.' . $pdpFile->guessExtension();
            $pdpFile->move($this->getParameter('pdp_directory'), $newFilename);
            $utilisateur->setPdpUrl($newFilename);
        }

        // Unicité email (facultatif mais très recommandé)
        if ($utilisateurRepository->findOneBy(['email' => $utilisateur->getEmail()])) {
            $form->get('email')->addError(new FormError('Cet email est déjà utilisé'));
        }

        if ($form->isValid()) {   // re-vérification après ajout erreur
            $entityManager->persist($utilisateur);
            $entityManager->flush();

            $this->addFlash('success', 'Compte créé avec succès ! Connectez-vous maintenant.');
            return $this->redirectToRoute('app_authentifier');
        }
    }

    return $this->render('authentifier.html.twig', [
        'inscriptionForm' => $form->createView(),
        // Tu peux ajouter d'autres variables si besoin (ex: loginForm si tu en as un)
    ]);
}
}