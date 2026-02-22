<?php
require_once 'vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

$dbUrl = $_ENV['DATABASE_URL'];
$url = parse_url($dbUrl);
$host = $url['host'];
$user = $url['user'];
$pass = $url['pass'];
$db = ltrim($url['path'], '/');

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

$query = "
    SELECT u.id, u.nom, u.prenom,
           (SELECT AVG(h.valeur_humeur) 
            FROM humeur h 
            JOIN profil_apprentissage pa ON h.profil_apprentissage_id = pa.id 
            WHERE pa.utilisateur_id = u.id) as avg_mood
    FROM utilisateur u 
    WHERE u.role = 'ETUDIANT'
    ORDER BY avg_mood ASC
    LIMIT 5
";

$result = $mysqli->query($query);
if (!$result) {
    echo "Error: " . $mysqli->error;
} else {
    while ($row = $result->fetch_assoc()) {
        echo "Student: " . $row['prenom'] . " " . $row['nom'] . " - Mood: " . $row['avg_mood'] . "\n";
    }
}
$mysqli->close();
