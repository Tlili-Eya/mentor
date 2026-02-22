<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$apiKey = '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';
$url = 'https://api.groq.com/openai/v1/chat/completions';

$client = HttpClient::create();

try {
    echo "Sending request to $url...\n";
    
    $response = $client->request('POST', $url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello!']
            ],
            'max_tokens' => 10
        ],
    ]);

    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Content: \n";
    print_r($response->toArray(false)); // false to not throw on error status

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if ($e instanceof \Symfony\Component\HttpClient\Exception\HttpExceptionInterface) {
        echo "Response Body: " . $e->getResponse()->getContent(false) . "\n";
    }
}
