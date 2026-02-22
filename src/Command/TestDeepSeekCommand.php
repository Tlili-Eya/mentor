<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TestDeepSeekCommand extends Command
{
    protected static $defaultName = 'app:test-deepseek';
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Test DeepSeek API');
        
        // Afficher la clé (cachée partiellement)
        $keyPreview = substr($this->apiKey, 0, 10) . '...' . substr($this->apiKey, -5);
        $io->info("Clé API: $keyPreview");
        
        try {
            $io->section('Envoi de la requête...');
            
            $response = $this->httpClient->request('POST', 'https://api.deepseek.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Tu es un assistant utile.'],
                        ['role' => 'user', 'content' => 'Dis bonjour en français']
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 100
                ],
                'timeout' => 30
            ]);
            
            $statusCode = $response->getStatusCode();
            $io->writeln("Code HTTP: $statusCode");
            
            if ($statusCode === 200) {
                $data = $response->toArray();
                $content = $data['choices'][0]['message']['content'] ?? 'Pas de contenu';
                
                $io->success('✅ API DeepSeek répond correctement !');
                $io->writeln("\nRéponse: " . $content);
                
                // Afficher l'utilisation des tokens
                if (isset($data['usage'])) {
                    $io->section('Utilisation');
                    $io->writeln("Tokens d'entrée: " . $data['usage']['prompt_tokens']);
                    $io->writeln("Tokens de sortie: " . $data['usage']['completion_tokens']);
                    $io->writeln("Total: " . $data['usage']['total_tokens']);
                }
                
                return Command::SUCCESS;
            } else {
                $error = $response->getContent(false);
                $io->error("Erreur API: $error");
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $io->error('Exception: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}