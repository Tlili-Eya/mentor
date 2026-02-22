<?php
require_once 'vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

$dbUrl = $_ENV['DATABASE_URL'];
$url = parse_url($dbUrl);
$host = $url['host'];
$user = $url['user'];
$pass = $url['pass'] ?? '';
$db = ltrim($url['path'], '/');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

echo "Connected successfully to $db\n";

$tables = $mysqli->query("SHOW TABLES");
while ($row = $tables->fetch_row()) {
    echo "Table: " . $row[0] . "\n";
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
    echo "Query Error: " . $mysqli->error . "\n";
} else {
    echo "Found " . $result->num_rows . " student rows matching the query.\n";
    while ($row = $result->fetch_assoc()) {
        echo "Student ID: " . $row['id'] . " - " . $row['prenom'] . " " . $row['nom'] . " - Mood: " . ($row['avg_mood'] ?? 'NULL') . "\n";
    }
}
$mysqli->close();
