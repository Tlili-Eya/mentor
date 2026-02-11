<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        // Get user roles
        $roles = $token->getUser()->getRoles();

        // Check roles and redirect accordingly
        
        // ROLE_ADMIN -> front_home (as requested)
        if (in_array('ROLE_ADMIN', $roles, true)) {
             return new RedirectResponse($this->router->generate('front_home'));
        }

        // ROLE_ADMINM or ROLE_ADMINISTRATEUR -> front_admin (new blank page)
        if (in_array('ROLE_ADMINM', $roles, true) || in_array('ROLE_ADMINISTRATEUR', $roles, true)) {
            return new RedirectResponse($this->router->generate('front_admin'));
        }

        // ROLE_ENSEIGNANT -> front_enseignant
        if (in_array('ROLE_ENSEIGNANT', $roles, true)) {
            // New blank enseignant page
            return new RedirectResponse($this->router->generate('front_enseignant'));
        }

        // Default behavior for ROLE_ETUDIANT and others
        return new RedirectResponse($this->router->generate('front_home'));
    }
}
