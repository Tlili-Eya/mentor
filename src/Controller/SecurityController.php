<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté → redirection selon son rôle
        if ($this->getUser()) {
            return $this->redirectToAppropriateDashboard();
        }

        // Récupérer l'erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Dernier nom d'utilisateur saisi
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * Redirige l'utilisateur vers le bon dashboard selon son rôle
     */
    private function redirectToAppropriateDashboard(): Response
    {
        // Récupère l'utilisateur connecté
        $user = $this->getUser();

        // Sécurité supplémentaire (normalement impossible ici, mais au cas où)
        if (!$user) {
            return $this->redirectToRoute('front_home');
        }

        // Ordre important : tester les rôles du plus privilégié au moins privilégié
        if ($this->isGranted('ROLE_ADMIN')) {
            // → À créer si tu as un dashboard admin global
            return $this->redirectToRoute('back_home'); 
            // Ou : return $this->redirectToRoute('back_administrateur');
        }

        if ($this->isGranted('ROLE_ADMINM')) {
            return $this->redirectToRoute('front_adminm');
        }

        if ($this->isGranted('ROLE_ENSEIGNANT')) {
            return $this->redirectToRoute('app_enseignant_dashboard');
        }

        // Par défaut : étudiant ou tout autre rôle
        return $this->redirectToRoute('front_home');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode peut rester vide - elle sera interceptée par la clé logout du firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}