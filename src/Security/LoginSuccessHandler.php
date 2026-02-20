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
        
        // ROLE_ADMINM or ROLE_ADMINISTRATEUR -> back_contact (feedback management back office)
        if (in_array('ROLE_ADMINM', $roles, true) || in_array('ROLE_ADMINISTRATEUR', $roles, true)) {
            return new RedirectResponse($this->router->generate('back_contact'));
        }

        // ROLE_ENSEIGNANT -> app_enseignant_dashboard (teacher dashboard)
        if (in_array('ROLE_ENSEIGNANT', $roles, true)) {
            return new RedirectResponse($this->router->generate('app_enseignant_dashboard'));
        }

        // ROLE_ADMIN -> back_contact (feedback management)
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->router->generate('back_contact'));
        }

        // Default behavior for ROLE_ETUDIANT and others
        return new RedirectResponse($this->router->generate('front_home'));
    }
}
