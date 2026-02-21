<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class WebAuthnController extends AbstractController
{
    // ===== LOGIN =====
    #[Route('/webauthn/login/qr', name: 'webauthn_login_qr')]
    public function loginQr(Request $request): Response
    {
        $token = bin2hex(random_bytes(16));
        $filePath = __DIR__ . '/../../var/webauthn_login_' . $token . '.json';
        file_put_contents($filePath, json_encode([
            'token' => $token,
            'status' => 'pending',
            'user_id' => null,
            'created_at' => time()
        ]));

        $request->getSession()->set('webauthn_token', $token);
        $request->getSession()->set('webauthn_status', 'pending');

        $url = 'https://dibasic-batlike-keira.ngrok-free.dev/webauthn/mobile/login/' . $token;

        $qrCode = new QrCode($url);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return new Response($result->getString(), 200, ['Content-Type' => 'image/png']);
    }

    #[Route('/webauthn/mobile/login/{token}', name: 'webauthn_mobile_login')]
    public function mobileLogin(string $token): Response
    {
        return $this->render('webauthn/mobile_login.html.twig', ['token' => $token]);
    }

    #[Route('/webauthn/mobile/register/{token}', name: 'webauthn_mobile_register', methods: ['GET'])]
    public function mobileRegister(string $token): Response
    {
        return $this->render('webauthn/mobile_register.html.twig', ['token' => $token]);
    }

    #[Route('/webauthn/login/confirm/{token}', name: 'webauthn_login_confirm', methods: ['POST'])]
    public function loginConfirm(string $token, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $filePath = __DIR__ . '/../../var/webauthn_login_' . $token . '.json';
        if (!file_exists($filePath)) {
            return new JsonResponse(['success' => false, 'message' => 'Token invalide ou session expirée'], 400);
        }

        $fileData = json_decode(file_get_contents($filePath), true);
        $data = json_decode($request->getContent(), true);
        $descriptor = $data['descriptor'] ?? null;

        if (!$descriptor) {
            return new JsonResponse(['success' => false, 'message' => 'Données biométriques manquantes'], 400);
        }

        // --- Vérification FaceID API (Port 8002) ---
        try {
            $ch = curl_init('http://127.0.0.1:8002/api/face/verify');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['descriptor' => $descriptor]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            
            $response = curl_exec($ch);
            $err = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false) {
                return new JsonResponse(['success' => false, 'message' => 'API Face-ID non joignable (Port 8002). Vérifiez qu\'elle est lancée. 🛠️'], 503);
            }

            if ($httpCode === 404) {
                return new JsonResponse(['success' => false, 'message' => 'Aucun visage enregistré dans la base Face-ID. Veuillez vous ré-inscrire. ❌'], 404);
            }

            if ($httpCode !== 200) {
                return new JsonResponse(['success' => false, 'message' => 'Erreur API Face-ID (Code: ' . $httpCode . ') ❌'], 500);
            }

            $verifyData = json_decode($response, true);
            if (!isset($verifyData['success']) || !$verifyData['success']) {
                return new JsonResponse(['success' => false, 'message' => 'Visage non reconnu ❌'], 401);
            }

            $faceIdToken = $verifyData['user_id']; // Le token utilisé lors de l'inscription
            
            // ✅ Trouver le VRAI utilisateur associé à ce credential_id
            $conn = $em->getConnection();
            $credentialRow = $conn->fetchAssociative(
                'SELECT user_id FROM webauthn_credentials WHERE credential_id = ?',
                [$faceIdToken]
            );

            if (!$credentialRow) {
                return new JsonResponse(['success' => false, 'message' => 'Utilisateur non trouvé pour ce visage ❌'], 404);
            }

            $dbUserId = $credentialRow['user_id'];
            
            // Mise à jour du fichier pour que le PC puisse voir la confirmation
            $fileData['status'] = 'confirmed';
            $fileData['user_id'] = $dbUserId;
            file_put_contents($filePath, json_encode($fileData));

            return new JsonResponse(['success' => true, 'message' => 'Connecté avec succès ✅']);

        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur technique: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/webauthn/login/status', name: 'webauthn_login_status')]
    public function loginStatus(Request $request, Security $security, UtilisateurRepository $repo, TokenStorageInterface $tokenStorage): JsonResponse
    {
        $token = $request->getSession()->get('webauthn_token');
        if (!$token) {
            return new JsonResponse(['status' => 'pending']);
        }

        $filePath = __DIR__ . '/../../var/webauthn_login_' . $token . '.json';
        if (!file_exists($filePath)) {
            return new JsonResponse(['status' => 'pending']);
        }

        $fileData = json_decode(file_get_contents($filePath), true);
        
        if ($fileData['status'] === 'confirmed') {
            // ✅ On connecte réellement l'utilisateur en session sur le PC
            $user = $repo->find($fileData['user_id']);
            
            if ($user) {
                try {
                    // 1. Connexion via le service Security
                    $security->login($user, \App\Security\CustomAuthenticator::class, 'main');
                    
                    // 2. Double check du token storage pour assurer la persistance
                    $sessionToken = new PostAuthenticationToken($user, 'main', $user->getRoles());
                    $tokenStorage->setToken($sessionToken);
                    
                    $request->getSession()->set('_security_main', serialize($sessionToken));
                    $request->getSession()->set('webauthn_status', 'confirmed');
                    $request->getSession()->set('webauthn_user_id', $user->getId());
                    
                    // 3. Sauvegarde forcée
                    $request->getSession()->save();
                    
                    file_put_contents(__DIR__ . '/../../var/log/mentor_debug.log', sprintf("[%s] loginStatus: Login SUCCESS for User ID %s (%s)\n", date('H:i:s'), $user->getId(), $user->getEmail()), FILE_APPEND);
                } catch (\Exception $e) {
                    file_put_contents(__DIR__ . '/../../var/log/mentor_debug.log', sprintf("[%s] loginStatus ERROR: %s\n", date('H:i:s'), $e->getMessage()), FILE_APPEND);
                }
            }
            
            // Nettoyage du fichier après connexion réussie
            @unlink($filePath); 
        }

        return new JsonResponse([
            'status' => $fileData['status'],
            'user_id' => $fileData['user_id']
        ]);
    }

    // ===== REGISTER =====
    #[Route('/webauthn/register/qr', name: 'webauthn_register_qr', methods: ['POST'])]
    public function registerQr(Request $request, EntityManagerInterface $em): Response
    {
        file_put_contents(
            __DIR__ . '/../../var/log/mentor_debug.log',
            sprintf("[%s] /register/qr called. JSON: %s\n", date('H:i:s'), $request->getContent()),
            FILE_APPEND
        );
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';

        // ✅ Vérifier si l'utilisateur existe DÉJÀ avant même de générer le QR
        if ($em->getRepository(Utilisateur::class)->findOneBy(['email' => $email])) {
            return new JsonResponse(['status' => 'error', 'message' => 'Cet email est déjà utilisé. ❌'], 400);
        }
        $userData = [
            'nom' => $data['nom'] ?? '',
            'prenom' => $data['prenom'] ?? '',
            'email' => $data['email'] ?? '',
            'mdp' => $data['mdp'] ?? '',
            'role' => $data['role'] ?? 'etudiant',
        ];

        $token = bin2hex(random_bytes(16));

        $filePath = __DIR__ . '/../../var/webauthn_' . $token . '.json';
        file_put_contents($filePath, json_encode([
            'token' => $token,
            'userData' => $userData,
            'status' => 'pending',
            'user_id' => null,
            'created_at' => time()
        ]));

        $request->getSession()->set('webauthn_register_token', $token);
        $request->getSession()->set('webauthn_register_status', 'pending');

        // ✅ Ajout d'un cache-buster pour le téléphone
        $timestamp = time();
        $url = 'https://dibasic-batlike-keira.ngrok-free.dev/webauthn/mobile/register/' . $token . '?v=' . $timestamp;

        $qrCode = new QrCode($url);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return new Response($result->getString(), 200, ['Content-Type' => 'image/png']);
    }

    #[Route('/webauthn/register/confirm/{token}', name: 'webauthn_register_confirm', methods: ['POST'])]
    public function registerConfirm(string $token, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $filePath = __DIR__ . '/../../var/webauthn_' . $token . '.json';

        if (!file_exists($filePath)) {
            return new JsonResponse(['success' => false, 'message' => 'Token invalide ou expiré ❌']);
        }

        $fileData = json_decode(file_get_contents($filePath), true);

        if ($fileData['token'] !== $token) {
            return new JsonResponse(['success' => false, 'message' => 'Token invalide ❌']);
        }

        $data = json_decode($request->getContent(), true);
        $credentialId = $data['credential_id'] ?? null;
        $publicKey = $data['public_key'] ?? null;

        if (!$credentialId || !$publicKey) {
            return new JsonResponse(['success' => false, 'message' => 'Face ID non validé ❌']);
        }

        // --- Intégration FaceID API (Port 8002) ---
        // On enregistre le descripteur dans le service séparé
        try {
            $descriptor = json_decode($publicKey, true);
            if (!$descriptor) {
                // Si c'est pas du JSON, c'est peut-être déjà un array (string decoded) or raw.
                $descriptor = $publicKey;
            }

            $ch = curl_init('http://127.0.0.1:8002/api/face/register');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'user_id' => $token,
                'descriptor' => $descriptor
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $res = curl_exec($ch);
            if ($res === false) {
                file_put_contents(__DIR__ . '/../../var/log/mentor_debug.log', sprintf("[%s] API REGISTER ERROR: %s\n", date('H:i:s'), curl_error($ch)), FILE_APPEND);
            }
            curl_close($ch);
        } catch (\Throwable $e) {
            // On ignore l'erreur de l'API externe pour ne pas bloquer l'inscription locale
        }

        $userData = $fileData['userData'];

        $existingUser = $em->getRepository(Utilisateur::class)->findOneBy(['email' => $userData['email']]);
        if ($existingUser) {
            return new JsonResponse(['success' => false, 'message' => 'Email déjà utilisé ❌']);
        }

        $user = new Utilisateur();
        $user->setNom($userData['nom']);
        $user->setPrenom($userData['prenom']);
        $user->setEmail($userData['email']);
        $user->setMdp(password_hash($userData['mdp'] ?: bin2hex(random_bytes(8)), PASSWORD_BCRYPT));
        $user->setRole($userData['role'] ?? 'etudiant');
        $user->setStatus('actif');
        $user->setDateInscription(new \DateTime());
        $em->persist($user);
        $em->flush();

        try {
            $conn = $em->getConnection();
            $conn->insert('webauthn_credentials', [
                'user_id' => $user->getId(),
                'credential_id' => $token,
                'public_key' => $publicKey,
                'counter' => 0,
            ]);
        } catch (\Exception $e) {
            // Si l'insertion échoue, on logue et on renvoie une erreur JSON propre
            file_put_contents(__DIR__ . '/../../var/log/mentor_debug.log', sprintf("[%s] DB ERROR: %s\n", date('H:i:s'), $e->getMessage()), FILE_APPEND);
            return new JsonResponse(['success' => false, 'message' => 'Erreur Base de données ❌ : ' . $e->getMessage()]);
        }

        $fileData['status'] = 'confirmed';
        $fileData['user_id'] = $user->getId();
        file_put_contents($filePath, json_encode($fileData));

        file_put_contents(__DIR__ . '/../../var/log/mentor_debug.log', sprintf("[%s] REGISTRATION CONFIRMED. User ID: %s, Email: %s\n", date('H:i:s'), $user->getId(), $user->getEmail()), FILE_APPEND);

        return new JsonResponse(['success' => true, 'user_id' => $user->getId()]);
    }

    #[Route('/webauthn/register/status', name: 'webauthn_register_status')]
    public function registerStatus(Request $request): JsonResponse
    {
        $token = $request->getSession()->get('webauthn_register_token');

        if (!$token) {
            return new JsonResponse(['status' => 'pending']);
        }

        $filePath = __DIR__ . '/../../var/webauthn_' . $token . '.json';

        if (!file_exists($filePath)) {
            return new JsonResponse(['status' => 'pending']);
        }

        $fileData = json_decode(file_get_contents($filePath), true);

        return new JsonResponse([
            'status' => $fileData['status'],
            'user_id' => $fileData['user_id']
        ]);
    }
}