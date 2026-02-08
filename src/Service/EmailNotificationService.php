<?php

namespace App\Service;

use App\Entity\Feedback;
use App\Entity\Traitement;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Service pour envoyer des notifications par email aux utilisateurs
 */
class EmailNotificationService
{
    private MailerInterface $mailer;
    private Environment $twig;
    private string $fromEmail;
    
    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        string $fromEmail = 'noreply@mentor.com'
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->fromEmail = $fromEmail;
    }
    
    /**
     * Envoie un email de notification quand un feedback est traité
     */
    public function sendFeedbackTreatedNotification(Feedback $feedback): void
    {
        $user = $feedback->getUtilisateur();
        $traitement = $feedback->getTraitement();
        
        if (!$user || !$user->getEmail()) {
            return;
        }
        
        // Générer le contenu HTML de l'email
        $htmlContent = $this->twig->render('emails/feedback_treated.html.twig', [
            'feedback' => $feedback,
            'user' => $user,
            'traitement' => $traitement,
        ]);
        
        // Créer l'email
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject('Votre feedback a été traité - Mentor Platform')
            ->html($htmlContent);
        
        // Envoyer
        $this->mailer->send($email);
    }
    
    /**
     * Envoie un email de confirmation quand un feedback est reçu
     */
    public function sendFeedbackReceivedNotification(Feedback $feedback): void
    {
        $user = $feedback->getUtilisateur();
        
        if (!$user || !$user->getEmail()) {
            return;
        }
        
        // Générer le contenu HTML de l'email
        $htmlContent = $this->twig->render('emails/feedback_received.html.twig', [
            'feedback' => $feedback,
            'user' => $user,
        ]);
        
        // Créer l'email
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject('Nous avons bien reçu votre feedback - Mentor Platform')
            ->html($htmlContent);
        
        // Envoyer
        $this->mailer->send($email);
    }
}
