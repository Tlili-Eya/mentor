<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'mentorai');
$res = $mysqli->query("DESCRIBE profil_apprentissage");
$cols = [];
while ($row = $res->fetch_assoc()) {
    $cols[] = $row['Field'];
}
echo implode(',', $cols);
$mysqli->close();
