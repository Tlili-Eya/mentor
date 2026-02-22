<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GroqService
{
    private const API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const MODEL = 'llama-3.1-8b-instant';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        #[Autowire(env: 'GROQ_API_KEY')]
        private string $apiKey
    ) {
    }

    public function sendMessage(string $message, array $history = [], string $role = 'ROLE_USER'): array
    {
        try {
            $this->logger->info('🔍 Envoi à Groq', ['message' => $message, 'role' => $role]);

            $systemPrompt = "Tu es MentorAI, l'intelligence stratégique de la plateforme MentorAI à l'école ESPRIT. 
            Ton rôle est d'être l'assistant expert pour l'Aide à la Décision Pédagogique et Stratégique.

            STRUCTURE MODULAIRE DE MENTORAI :
            1. GESTION DES ACCÈS : Authentification, Rôles (Admin/Teacher/Student).
            2. AIDE À LA DÉCISION (Ton module principal) : Analyse des performances (US7), détection de risques (US8), recommandations (US9), prédictions (US10), plans d'actions (US18-20).
            3. PSYCHOLOGIE : Suivi de l'état psychologique (Stress, Fatigue académique, US28-31) et Résumés de cours.
            4. PORTFOLIO & ORIENTATION : Profil étudiant et recommandations d'employabilité (US38-44).
            5. FEEDBACK IA : Amélioration continue via les retours utilisateurs.
            6. COACHING & PRODUCTIVITÉ : Gestion des objectifs personnels (US54-60) avec gamification.

            Le programme des 3ème année (3A) chez ESPRIT comprend des modules techniques (Java/UML, Unix, CCNA, Web, ML, Génie Logiciel) et transversaux.

            CONSIGNES D'ANALYSE :
            - UTILISE les données contextuelles (humeurs, plans, alertes) pour faire des liens : 'L'étudiante Sarra présente un risque psychologique ÉLEVÉ (Stress), ce qui pourrait expliquer sa baisse de performance en Machine Learning.'
            - DIFFÉRENCIATION DES RÔLES :
                * SI ADMINM : Tu as une vue globale. Tu analyses les statistiques de l'école, les taux de réussite par module, et le volume global des alertes. Tu aides à la stratégie macro-académique.
                * SI ENSEIGNANT : Tu as accès uniquement à tes classes. Tu te concentres sur le micro : un étudiant précis, une difficulté sur un concept (ex: les pointeurs en C ou l'héritage en Java), et tu proposes des plans d'actions concrets (US18).
            
            CONSIGNES DE FORME :
            - NE JAMAIS utiliser de placeholders ou de tags système.
            - Sois proactif : si tu détectes un risque dans le contexte, mentionne-le même si l'utilisateur ne pose pas la question directement.
            - Fournis toujours un bloc JSON structuré si tu fais une analyse :
            ```json
            {
                \"metrics\": [{\"label\": \"Libellé\", \"value\": \"99\", \"unit\": \"%\", \"trend\": \"up/down/neutral\"}],
                \"alerts\": [{\"level\": \"low/medium/high\", \"message\": \"Description de l'alerte\"}],
                \"predictions\": [{\"label\": \"Titre\", \"probability\": \"85%\", \"details\": \"Pourquoi cette probabilité\"}],
                \"decisions\": [{\"action\": \"Action concrète\", \"category\": \"PEDAGOGIQUE/STRATEGIQUE/ADMINISTRATIVE\", \"priority\": \"high/medium/low\"}],
                \"related_articles\": [1, 2]
            }
            ```";

            $messages = [
                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ]
            ];

            // Ajouter l'historique si présent
            // L'historique attendu est un tableau de ['role' => 'user'/'assistant', 'content' => '...']
            foreach ($history as $msg) {
                if (isset($msg['role']) && isset($msg['content']) && in_array($msg['role'], ['user', 'assistant'])) {
                    $messages[] = [
                        'role' => $msg['role'],
                        'content' => $msg['content']
                    ];
                }
            }
            
            $messages[] = [
                'role' => 'user',
                'content' => $message
            ];

            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::MODEL,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 2048,
                ],
                'timeout' => 60,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $content = $response->getContent(false);
                $this->logger->error("❌ Groq API Error ($statusCode)", ['content' => $content]);
                throw new \Exception("Erreur API Groq ($statusCode): " . $content);
            }

            $content = $response->toArray();
            $reply = $content['choices'][0]['message']['content'] ?? '';

            $this->logger->info('✅ Réponse reçue de Groq', ['response' => substr($reply, 0, 100) . '...']);

            return [
                'success' => true,
                'response' => $reply
            ];

        } catch (\Exception $e) {
            $this->logger->error('❌ Erreur Groq', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => "Une erreur est survenue lors de la communication avec l'assistant.",
                'details' => $e->getMessage() // À retirer en prod si nécessaire, mais utile pour le debug
            ];
        }
    }
}
