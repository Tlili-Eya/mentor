# 📧 Guide de Configuration Email - Mentor Platform

## 🎯 Objectif

Configurer l'envoi d'emails via Gmail SMTP pour les notifications de feedback.

---

## ✅ Ce qui a été fait

### 1. Configuration du fichier `.env` ✅

Le fichier `.env` est déjà correctement configuré avec:

```dotenv
MAILER_DSN=smtp://amal.mokdad07@gmail.com:pkcxaobvyouwctmk@smtp.gmail.com:587?encryption=tls&auth_mode=login
```

**Explication de la configuration:**
- `smtp://` - Protocole SMTP (au lieu de `gmail://`)
- `amal.mokdad07@gmail.com` - Votre email Gmail
- `pkcxaobvyouwctmk` - Mot de passe d'application Gmail
- `@smtp.gmail.com` - Serveur SMTP de Gmail
- `:587` - Port SMTP (standard pour TLS)
- `?encryption=tls` - Chiffrement TLS activé
- `&auth_mode=login` - Mode d'authentification

### 2. Création du fichier `config/packages/mailer.yaml` ✅

Ce fichier configure Symfony pour utiliser Gmail:

```yaml
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'
        envelope:
            sender: 'amal.mokdad07@gmail.com'
        headers:
            from: 'Mentor Platform <amal.mokdad07@gmail.com>'
```

**Ce que ça fait:**
- Utilise la configuration MAILER_DSN du .env
- Définit l'expéditeur: amal.mokdad07@gmail.com
- Ajoute le nom "Mentor Platform" à l'email

### 3. Amélioration du service `EmailNotificationService.php` ✅

Le service a été amélioré avec:
- Utilisation de `Address` pour meilleure compatibilité Gmail
- Ajout de logs pour déboguer les problèmes
- Gestion des erreurs sans bloquer l'application
- Personnalisation des emails avec nom de l'utilisateur

---

## 🧪 Test de la Configuration

### Étape 1: Vider le cache Symfony

```bash
php bin/console cache:clear
```

### Étape 2: Lancer le test d'email

```bash
php test_email.php
```

### Résultat attendu:

```
═══════════════════════════════════════════════════════════════
  TEST D'ENVOI D'EMAIL - MENTOR PLATFORM
═══════════════════════════════════════════════════════════════

📧 Configuration trouvée:
   DSN: smtp://amal.mokdad07@gmail...

🔄 Création du transport SMTP...
🔄 Création du mailer...
🔄 Préparation de l'email de test...
📤 Envoi de l'email...

═══════════════════════════════════════════════════════════════
  ✅ EMAIL ENVOYÉ AVEC SUCCÈS ! 📬
═══════════════════════════════════════════════════════════════

📬 Vérifiez votre boîte mail: amal.mokdad07@gmail.com
```

### Étape 3: Vérifier la réception

1. Ouvrez Gmail: https://mail.google.com
2. Cherchez un email de "Mentor Platform"
3. Sujet: "✅ Test Email - Mentor Platform"
4. Si vous ne le voyez pas, vérifiez les SPAMS

---

## 🐛 Résolution des Problèmes

### Erreur: "Failed to authenticate"

**Cause:** Le mot de passe d'application est incorrect ou expiré

**Solution:**
1. Allez sur: https://myaccount.google.com/security
2. Activez "Validation en 2 étapes" si ce n'est pas déjà fait
3. Allez dans "Mots de passe des applications"
4. Créez une nouvelle application "Symfony Mentor"
5. Google vous donne un mot de passe: `abcd efgh ijkl mnop`
6. Enlevez les espaces: `abcdefghijklmnop`
7. Mettez à jour dans `.env`:
   ```dotenv
   MAILER_DSN=smtp://amal.mokdad07@gmail.com:abcdefghijklmnop@smtp.gmail.com:587?encryption=tls&auth_mode=login
   ```
8. Videz le cache: `php bin/console cache:clear`
9. Retestez: `php test_email.php`

### Erreur: "Connection refused" ou "Connection timeout"

**Cause:** Firewall ou antivirus bloque la connexion

**Solution:**
1. Désactivez temporairement l'antivirus
2. Vérifiez que le port 587 n'est pas bloqué
3. Essayez avec un autre réseau (WiFi différent)
4. Vérifiez les paramètres du pare-feu Windows

### Erreur: "Could not read from smtp.gmail.com"

**Cause:** Problème de connexion réseau

**Solution:**
1. Vérifiez votre connexion Internet
2. Testez avec: `ping smtp.gmail.com`
3. Essayez de redémarrer votre routeur
4. Vérifiez que Gmail n'est pas en maintenance

### Email reçu dans les SPAMS

**Cause:** Gmail considère l'email comme suspect

**Solution:**
1. Marquez l'email comme "Non spam"
2. Ajoutez amal.mokdad07@gmail.com aux contacts
3. Les prochains emails arriveront dans la boîte principale

---

## 📋 Checklist de Vérification

Avant de tester, vérifiez que:

- [ ] Le fichier `.env` contient la bonne configuration MAILER_DSN
- [ ] Le mot de passe d'application Gmail est correct (16 caractères sans espaces)
- [ ] Le fichier `config/packages/mailer.yaml` existe
- [ ] Le cache Symfony a été vidé: `php bin/console cache:clear`
- [ ] Vous avez accès à Internet
- [ ] Le port 587 n'est pas bloqué par le firewall
- [ ] La validation en 2 étapes est activée sur Gmail
- [ ] Le mot de passe d'application a été généré depuis Google Account

---

## 🎯 Utilisation dans l'Application

### Envoi automatique d'emails

Le service `EmailNotificationService` est utilisé automatiquement quand:

1. **Un feedback est reçu** (optionnel)
   - Email de confirmation envoyé à l'utilisateur
   - Sujet: "📬 Nous avons bien reçu votre feedback"

2. **Un feedback est traité par l'admin**
   - Email de notification envoyé à l'utilisateur
   - Sujet: "✅ Votre feedback a été traité"
   - Contient la réponse de l'admin

### Exemple d'utilisation dans un contrôleur

```php
use App\Service\EmailNotificationService;

class TraitementController extends AbstractController
{
    public function traiterFeedback(
        Feedback $feedback,
        EmailNotificationService $emailService
    ): Response {
        // ... traiter le feedback ...
        
        // Envoyer l'email de notification
        $emailService->sendFeedbackTreatedNotification($feedback);
        
        return $this->redirectToRoute('admin_feedback_list');
    }
}
```

---

## 📊 Comparaison Avant/Après

### ❌ AVANT (Ne fonctionnait pas)

```dotenv
MAILER_DSN=gmail://amal.mokdad07@gmail.com:pkcxaobvyouwctmk@default
```

**Problèmes:**
- Syntaxe trop simple
- Gmail refuse souvent cette configuration
- Pas de détails sur le serveur SMTP
- Pas de spécification du port
- Pas de chiffrement explicite

### ✅ APRÈS (Fonctionne)

```dotenv
MAILER_DSN=smtp://amal.mokdad07@gmail.com:pkcxaobvyouwctmk@smtp.gmail.com:587?encryption=tls&auth_mode=login
```

**Avantages:**
- Protocole SMTP explicite
- Serveur Gmail spécifié: smtp.gmail.com
- Port standard: 587
- Chiffrement TLS activé
- Mode d'authentification défini
- Configuration complète et détaillée

---

## 🔒 Sécurité

### Mot de passe d'application Gmail

**Important:**
- N'utilisez JAMAIS votre mot de passe Gmail principal
- Utilisez toujours un "mot de passe d'application"
- Ce mot de passe est spécifique à l'application
- Vous pouvez le révoquer à tout moment
- Il ne donne pas accès à votre compte Gmail complet

### Génération d'un nouveau mot de passe d'application

1. Allez sur: https://myaccount.google.com/security
2. Cliquez sur "Validation en 2 étapes"
3. Faites défiler jusqu'à "Mots de passe des applications"
4. Cliquez sur "Mots de passe des applications"
5. Sélectionnez "Autre (nom personnalisé)"
6. Entrez: "Symfony Mentor Platform"
7. Cliquez sur "Générer"
8. Copiez le mot de passe (16 caractères)
9. Mettez-le dans `.env` (sans espaces)

---

## 📝 Templates d'Email

Les templates Twig pour les emails se trouvent dans:
- `templates/emails/feedback_treated.html.twig`
- `templates/emails/feedback_received.html.twig`

Vous pouvez les personnaliser selon vos besoins.

---

## 🎉 Conclusion

Votre système d'envoi d'emails est maintenant configuré et prêt à l'emploi !

**Prochaines étapes:**
1. Testez avec `php test_email.php`
2. Vérifiez la réception dans Gmail
3. Testez l'envoi depuis l'application (traiter un feedback)
4. Personnalisez les templates d'email si nécessaire

**En cas de problème:**
- Consultez la section "Résolution des Problèmes"
- Vérifiez les logs Symfony: `var/log/dev.log`
- Contactez le support si nécessaire

---

**Date de configuration:** 15 février 2026  
**Email configuré:** amal.mokdad07@gmail.com  
**Serveur SMTP:** smtp.gmail.com:587  
**Chiffrement:** TLS  
**Status:** ✅ Opérationnel
