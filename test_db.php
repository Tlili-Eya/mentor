<?php
$dsn = 'mysql:host=127.0.0.1;dbname=mentorai';
$username = 'root';
$password = '';

try {
    $pdo = new PDO($dsn, $username, $password);
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in mentorai:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
