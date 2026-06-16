<?php
require_once '/var/www/html/Class files/db.php';
require_once '/var/www/html/Class files/QueryBuilder.php';

$vendor = getenv('DB_VENDOR') ?: 'mysql';
$host = getenv('DB_HOST') ?: 'db';
$dbname = getenv('DB_NAME') ?: 'stackDB';
$username = getenv('DB_USER') ?: 'docker_user_lemp';
$password = getenv('DB_PASS') ?: 'docker_user_lemp';

$dsn = "$vendor:host=$host;dbname=$dbname";
$db = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$stmt = $db->query("SELECT evsPK, evsEventType, evsPreviousPayload FROM tblEventStore WHERE evsEventType = 'AccountUpdated' ORDER BY evsPK DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "ID: " . $row['evsPK'] . " | Type: " . $row['evsEventType'] . " | Prev Payload: " . ($row['evsPreviousPayload'] ? "exists" : "NULL") . "\n";
}
