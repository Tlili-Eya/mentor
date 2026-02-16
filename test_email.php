<?php

/**
 * Script de test pour vérifier l'envoi d'emails via Gmail SMTP
 * 
 * Usage: php test_email.php
 */

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

require __DIR__.'/vendor/autoload.php';

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  TEST D'ENVOI D'EMAIL - MENTOR PLATFORM\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$mailerDsn = $_ENV['MAILER_DSN'] ?? null;

if (!$mailerDsn) {
    echo "❌ ERREUR: MAILER_DSN non trouvé dans .env\n";
    echo "\n";
    echo "Vérifiez que votre fichier .env contient:\n";
    echo "MAILER_DSN=smtp://amal.mokdad07@gmail.com:pkcxaobvyouwctmk@smtp.gmail.com:587?encryption=tls&auth_mode=login\n";
    echo "\n";
    exit(1);
}

echo "📧 Configuration trouvée:\n";
echo "   DSN: " . substr($mailerDsn, 0, 30) . "...\n";
echo "\n";

try {
    echo "🔄 Création du transport SMTP...\n";
    $transport = Transport::fromDsn($mailerDsn);
    
    echo "🔄 Création du mailer...\n";
    $mailer = new Mailer($transport);
    
    echo "🔄 Préparation de l'email de test...\n";
    $email = (new Email())
        ->from(new Address('amal.mokdad07@gmail.com', 'Mentor Platform'))
        ->to(new Address('amal.mokdad07@gmail.com', 'Test User'))
        ->subject('✅ Test Email - Mentor Platform')
        ->html('
            <html>
                <body style="font-family: Arial, sans-serif; padding: 20px; background-color: #f5f5f5;">
                    <div style="max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h1 style="color: #102c59; text-align: center;">🎉 Test Réussi !</h1>
                        <p style="font-size: 16px; color: #333; line-height: 1.6;">
                            Félicitations ! Votre configuration Gmail SMTP fonctionne parfaitement.
                        </p>
                        <div style="background-color: #e8f5e9; padding: 15px; border-radius: 5px; margin: 20px 0;">
                            <p style="margin: 0; color: #2e7d32; font-weight: bold;">
                                ✅ Le système d\'envoi d\'emails est opérationnel
                            </p>
                        </div>
                        <p style="font-size: 14px; color: #666;">
                            <strong>Configuration utilisée:</strong><br>
                            • Serveur: smtp.gmail.com<br>
                            • Port: 587<br>
                            • Chiffrement: TLS<br>
                            • Authentification: Login
                        </p>
                        <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
                        <p style="font-size: 12px; color: #999; text-align: center;">
                            Email envoyé depuis Mentor Platform<br>
                            ' . date('d/m/Y à H:i:s') . '
                        </p>
                    </div>
                </body>
            </html>
        ')
        ->text('
            Test Réussi !
            
            Félicitations ! Votre configuration Gmail SMTP fonctionne parfaitement.
            
            ✅ Le système d\'envoi d\'emails est opérationnel
            
            Configuration utilisée:
            • Serveur: smtp.gmail.com
            • Port: 587
            • Chiffrement: TLS
            • Authentification: Login
            
            Email envoyé depuis Mentor Platform
            ' . date('d/m/Y à H:i:s') . '
        ');
    
    echo "📤 Envoi de l'email...\n";
    $mailer->send($email);
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  ✅ EMAIL ENVOYÉ AVEC SUCCÈS ! 📬\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "📬 Vérifiez votre boîte mail: amal.mokdad07@gmail.com\n";
    echo "\n";
    echo "Si vous ne voyez pas l'email:\n";
    echo "  1. Vérifiez le dossier SPAM/Courrier indésirable\n";
    echo "  2. Attendez quelques minutes (délai de livraison)\n";
    echo "  3. Vérifiez que l'email est bien amal.mokdad07@gmail.com\n";
    echo "\n";
    echo "🎉 Le système d'envoi d'emails fonctionne correctement !\n";
    echo "\n";
    
} catch (\Exception $e) {
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  ❌ ERREUR LORS DE L'ENVOI\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "Message d'erreur:\n";
    echo $e->getMessage() . "\n";
    echo "\n";
    echo "Solutions possibles:\n";
    echo "\n";
    echo "1. Vérifier le mot de passe d'application Gmail\n";
    echo "   • Allez sur: https://myaccount.google.com/security\n";
    echo "   • Activez la validation en 2 étapes\n";
    echo "   • Créez un nouveau mot de passe d'application\n";
    echo "   • Mettez à jour MAILER_DSN dans .env\n";
    echo "\n";
    echo "2. Vérifier la configuration dans .env\n";
    echo "   • Format: smtp://email:password@smtp.gmail.com:587?encryption=tls&auth_mode=login\n";
    echo "   • Pas d'espaces dans le mot de passe\n";
    echo "\n";
    echo "3. Vérifier le firewall/antivirus\n";
    echo "   • Désactivez temporairement l'antivirus\n";
    echo "   • Autorisez PHP à accéder au réseau\n";
    echo "\n";
    
    exit(1);
}
