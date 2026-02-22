<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class DeepSeekService
{
    private string $apiKey;
    private HttpClientInterface $httpClient;
    private ?SessionInterface $session;
    private LoggerInterface $logger;
    private array $config;

    public function __construct(
        string $apiKey,
        HttpClientInterface $httpClient,
        RequestStack $requestStack,  // ← Changé ici
        LoggerInterface $logger
    ) {
        $this->apiKey = $apiKey;
        $this->httpClient = $httpClient;
        $this->session = $requestStack->getCurrentRequest()?->getSession(); // ← Récupère la session
        $this->logger = $logger;
        
        // Configuration par défaut
        $this->config = [
            'model' => 'deepseek-chat',
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'top_p' => 0.95,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ];

        // Initialiser l'historique de session si la session existe
        if ($this->session && !$this->session->has('chat_history')) {
            $this->session->set('chat_history', []);
        }
    }

    public function sendMessage(string $message, string $userRole = 'enseignant', string $format = 'text'): array
    {
        try {
            // Vérifier que la session existe
            if (!$this->session) {
                return [
                    'success' => false,
                    'error' => 'Session non disponible'
                ];
            }

            // Construire le prompt système selon le rôle
            $systemPrompt = $this->getSystemPrompt($userRole, $format);
            
            // Récupérer l'historique
            $history = $this->session->get('chat_history', []);
            
            // Préparer les messages pour l'API
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt]
            ];
            
            // Ajouter l'historique récent (max 10 messages)
            $recentHistory = array_slice($history, -10);
            foreach ($recentHistory as $msg) {
                $messages[] = $msg;
            }
            
            // Ajouter le nouveau message
            $messages[] = ['role' => 'user', 'content' => $message];

            $this->logger->info('Envoi à DeepSeek', [
                'role' => $userRole,
                'message_length' => strlen($message),
                'history_count' => count($history)
            ]);

            // Appel à l'API DeepSeek
            $response = $this->httpClient->request('POST', 'https://api.deepseek.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => array_merge($this->config, [
                    'messages' => $messages,
                    'stream' => false
                ]),
                'timeout' => 30
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode !== 200) {
                $error = $response->getContent(false);
                $this->logger->error('Erreur API DeepSeek', [
                    'status' => $statusCode,
                    'error' => $error
                ]);
                return [
                    'success' => false,
                    'error' => 'Erreur API: ' . $statusCode
                ];
            }

            $data = $response->toArray();
            $assistantResponse = $data['choices'][0]['message']['content'] ?? null;

            if (!$assistantResponse) {
                return [
                    'success' => false,
                    'error' => 'Réponse vide de l\'API'
                ];
            }

            // Sauvegarder dans l'historique
            $history[] = ['role' => 'user', 'content' => $message];
            $history[] = ['role' => 'assistant', 'content' => $assistantResponse];
            $this->session->set('chat_history', $history);

            // Vérifier si la réponse est du JSON (pour le format structuré)
            $isJson = false;
            if ($format === 'json') {
                $jsonResponse = $this->extractJson($assistantResponse);
                if ($jsonResponse) {
                    $assistantResponse = $jsonResponse;
                    $isJson = true;
                }
            }

            return [
                'success' => true,
                'response' => $assistantResponse,
                'isJson' => $isJson,
                'usage' => $data['usage'] ?? null
            ];

        } catch (\Exception $e) {
            $this->logger->error('Exception DeepSeekService', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Erreur technique: ' . $e->getMessage()
            ];
        }
    }

    public function resetConversation(): void
    {
        if ($this->session) {
            $this->session->remove('chat_history');
        }
    }

    public function getHistory(): array
    {
        return $this->session ? $this->session->get('chat_history', []) : [];
    }

    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    private function getSystemPrompt(string $role, string $format): string
    {
        $basePrompt = "Tu es un assistant IA spécialisé pour MentorAI, une plateforme éducative.\n";
        
        if ($role === 'admin') {
            $basePrompt .= "Tu aides les administrateurs à gérer la plateforme, analyser les données, optimiser les processus et générer des alertes administratives.\n";
        } else {
            $basePrompt .= "Tu aides les enseignants à créer des plans pédagogiques, rédiger des articles éducatifs, générer des alertes sur les élèves, faire des prédictions de réussite et donner des recommandations pédagogiques.\n";
        }

        if ($format === 'json') {
            $basePrompt .= "Tu DOIS répondre UNIQUEMENT avec un objet JSON valide contenant ces champs possibles : type (Alerte, Prédiction, Recommandation), cible (Enseignant, Admin, Élève), criticite (Faible, Moyen, Élevé), categorie, contenu, recommandation, confiance (0-1).\n";
            $basePrompt .= "Exemple: {\"type\": \"Alerte\", \"cible\": \"Enseignant\", \"criticite\": \"Élevé\", \"categorie\": \"Académique\", \"contenu\": \"Description de l'alerte\", \"recommandation\": \"Action à prendre\", \"confiance\": 0.85}\n";
        } else {
            $basePrompt .= "Réponds de manière professionnelle et éducative en français. Sois précis et utile.\n";
        }

        $basePrompt .= "\nContexte: Tu parles à un " . ($role === 'admin' ? 'administrateur' : 'enseignant') . " de la plateforme MentorAI.\n";
        
        return $basePrompt;
    }

    private function extractJson(string $text): ?string
    {
        // Chercher du JSON entre ```json et ```
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $text, $matches)) {
            return $matches[1];
        }
        
        // Chercher du JSON entre { et }
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