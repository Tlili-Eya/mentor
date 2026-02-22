<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'mentorai');
foreach(['humeur', 'profil_apprentissage'] as $table) {
    echo "--- $table ---\n";
    $res = $mysqli->query("DESCRIBE $table");
    if (!$res) echo "Error: " . $mysqli->error . "\n";
    else {
        while ($row = $res->fetch_assoc()) {
            echo $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    }
}
