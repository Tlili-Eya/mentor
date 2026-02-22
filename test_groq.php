<?php
require_once 'vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

$apiKey = $_ENV['GROQ_API_KEY'];
echo "API Key: " . substr($apiKey, 0, 10) . "...\n";

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'llama-3.3-70b-versatile',
    'messages' => [
        ['role' => 'user', 'content' => 'Hello']
    ]
]));

$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "Status Code: " . $info['http_code'] . "\n";
echo "Response: " . $response . "\n";
