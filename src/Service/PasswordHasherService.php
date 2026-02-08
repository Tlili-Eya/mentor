<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordHasherService
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function hashPassword(Utilisateur $utilisateur, string $plainPassword): string
    {
        return $this->passwordHasher->hashPassword($utilisateur, $plainPassword);
    }

    public function isPasswordValid(Utilisateur $utilisateur, string $plainPassword): bool
    {
        return $this->passwordHasher->isPasswordValid($utilisateur, $plainPassword);
    }
}
