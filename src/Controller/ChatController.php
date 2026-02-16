<?php

namespace App\Controller;

use App\Service\OllamaService;
use App\Service\RasaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\ExpressionLanguage\Expression;

class ChatController extends AbstractController
{
    #[Route('/chat', name: 'app_chat')]
    #[IsGranted(new Expression("is_granted('ROLE_ENSEIGNANT') or is_granted('ROLE_ADMINM')"))]
    public function index(): Response
    {
        // Déterminer le template selon le rôle
        if ($this->isGranted('ROLE_ADMINM')) {
            $template = 'front/admin/chat_ia.html.twig';
        } else {
            $template = 'front/enseignant/chat_ia.html.twig';
        }
        
        return $this->render($template);
    }

    #[Route('/chat/send', name: 'app_chat_send', methods: ['POST'])]
    #[IsGranted(new Expression("is_granted('ROLE_ENSEIGNANT') or is_granted('ROLE_ADMINM')"))]
    public function send(Request $request, OllamaService $ollama): JsonResponse
    {
        $message = $request->request->get('message');
        
        if (!$message) {
            return $this->json(['success' => false, 'error' => 'Message vide'], 400);
        }

        $result = $ollama->sendMessage($message);
        
        return $this->json($result);
    }

    #[Route('/chat/history', name: 'app_chat_history', methods: ['GET'])]
    #[IsGranted(new Expression("is_granted('ROLE_ENSEIGNANT') or is_granted('ROLE_ADMINM')"))]
    public function history(RasaService $rasa): JsonResponse
    {
        return $this->json([
            'success' => true,
            'history' => $rasa->getHistory()
        ]);
    }

    #[Route('/chat/reset', name: 'app_chat_reset', methods: ['POST'])]
    #[IsGranted(new Expression("is_granted('ROLE_ENSEIGNANT') or is_granted('ROLE_ADMINM')"))]
    public function reset(RasaService $rasa): JsonResponse
    {
        $rasa->resetConversation();
        return $this->json(['success' => true]);
    }

    #[Route('/chat/config', name: 'app_chat_config', methods: ['POST'])]
    #[IsGranted('ROLE_ADMINM')]  // Seulement les admins peuvent configurer
    public function config(Request $request): JsonResponse
    {
        $config = json_decode($request->getContent(), true);
        $session = $this->get('session');
        $session->set('chat_config', $config);
        
        return $this->json(['success' => true]);
    }

    #[Route('/chat/stats', name: 'app_chat_stats', methods: ['GET'])]
    #[IsGranted('ROLE_ADMINM')]  // Seulement les admins voient les stats
    public function stats(): JsonResponse
    {
        $session = $this->get('session');
        $history = $session->get('chat_history', []);
        
        return $this->json([
            'success' => true,
            'stats' => [
                'messages_in_session' => count($history),
                'conversations_count' => $session->get('conversations_count', 0),
                'user_role' => $this->isGranted('ROLE_ADMINM') ? 'admin' : 'enseignant'
            ]
        ]);
    }

    private function extractJson(string $text): ?string
    {
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $text, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $candidate = $matches[0];
            json_decode($candidate);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $candidate;
            }
        }
        
        return null;
    }
}