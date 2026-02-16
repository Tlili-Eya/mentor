<?php

namespace App\Service;

use App\Entity\Feedback;
use App\Entity\Traitement;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Twig\Environment;
use Psr\Log\LoggerInterface;

/**
 * Service pour envoyer des notifications par email aux utilisateurs
 * Configuration optimisée pour Gmail SMTP
 */
class EmailNotificationService
{
    private MailerInterface $mailer;
    private Environment $twig;
    private string $fromEmail;
    private string $fromName;
    private LoggerInterface $logger;
    
    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        LoggerInterface $logger,
        string $fromEmail = 'amal.mokdad07@gmail.com',
        string $fromName = 'Mentor Platform'
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->logger = $logger;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }
    
    /**
     * Envoie un email de notification quand un feedback est traité
     */
    public function sendFeedbackTreatedNotification(Feedback $feedback): void
    {
        try {
            $user = $feedback->getUtilisateur();
            $traitement = $feedback->getTraitement();
            
            if (!$user || !$user->getEmail()) {
                $this->logger->warning('Cannot send email: user or email missing', [
                    'feedback_id' => $feedback->getId()
                ]);
                return;
            }
            
            // Générer le contenu HTML de l'email (version simple en anglais)
            $htmlContent = $this->twig->render('emails/feedback_treated_simple.html.twig', [
                'feedback' => $feedback,
                'user' => $user,
                'traitement' => $traitement,
            ]);
            
            // Créer l'email avec Address pour meilleure compatibilité Gmail
            $email = (new Email())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to(new Address($user->getEmail(), $user->getPrenom() . ' ' . $user->getNom()))
                ->subject('✅ Your feedback has been answered - MentorAI Platform')
                ->html($htmlContent)
                ->priority(Email::PRIORITY_HIGH);
            
            // Envoyer
            $this->mailer->send($email);
            
            $this->logger->info('Feedback treated notification sent successfully', [
                'feedback_id' => $feedback->getId(),
                'user_email' => $user->getEmail()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to send feedback treated notification', [
                'feedback_id' => $feedback->getId(),
                'error' => $e->getMessage()
            ]);
            // Ne pas propager l'exception pour ne pas bloquer le traitement
        }
    }
    
    /**
     * Envoie un email de confirmation quand un feedback est reçu
     */
    public function sendFeedbackReceivedNotification(Feedback $feedback): void
    {
        try {
            $user = $feedback->getUtilisateur();
            
            if (!$user || !$user->getEmail()) {
                $this->logger->warning('Cannot send email: user or email missing', [
                    'feedback_id' => $feedback->getId()
                ]);
                return;
            }
            
            // Générer le contenu HTML de l'email
            $htmlContent = $this->twig->render('emails/feedback_received.html.twig', [
                'feedback' => $feedback,
                'user' => $user,
            ]);
            
            // Créer l'email avec Address pour meilleure compatibilité Gmail
            $email = (new Email())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to(new Address($user->getEmail(), $user->getPrenom() . ' ' . $user->getNom()))
                ->subject('📬 Nous avons bien reçu votre feedback - Mentor Platform')
                ->html($htmlContent);
            
            // Envoyer
            $this->mailer->send($email);
            
            $this->logger->info('Feedback received notification sent successfully', [
                'feedback_id' => $feedback->getId(),
                'user_email' => $user->getEmail()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to send feedback received notification', [
                'feedback_id' => $feedback->getId(),
                'error' => $e->getMessage()
            ]);
            // Ne pas propager l'exception pour ne pas bloquer le traitement
        }
    }
}
