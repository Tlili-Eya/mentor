<?php

namespace App\Command;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:hash-passwords',
    description: 'Hash tous les mots de passe en clair dans la base de données',
)]
class HashPasswordsCommand extends Command
{
    public function __construct(
        private UtilisateurRepository $utilisateurRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $utilisateurs = $this->utilisateurRepository->findAll();
        $count = 0;

        foreach ($utilisateurs as $utilisateur) {
            $currentPassword = $utilisateur->getMdp();
            
            // Vérifier si le mot de passe est déjà hashé (commence généralement par $2y$)
            if (!str_starts_with($currentPassword, '$2y$')) {
                // Hasher le mot de passe
                $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, $currentPassword);
                $utilisateur->setMdp($hashedPassword);
                $count++;
                
                $io->writeln(sprintf(
                    'Mot de passe hashé pour: %s %s (%s)',
                    $utilisateur->getPrenom(),
                    $utilisateur->getNom(),
                    $utilisateur->getEmail()
                ));
            }
        }

        if ($count > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('%d mot(s) de passe ont été hashés avec succès!', $count));
        } else {
            $io->info('Tous les mots de passe sont déjà hashés.');
        }

        return Command::SUCCESS;
    }
}
