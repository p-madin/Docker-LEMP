<?php
require_once __DIR__ . '/app/Class files/db_connect.php';
require_once __DIR__ . '/app/Class files/QueryBuilder.php';

$vendor = getenv('DB_VENDOR') ?: 'mysql';
$host = getenv('DB_HOST') ?: 'db';
$dbname = getenv('DB_NAME') ?: 'stackDB';
$username = getenv('DB_USER') ?: 'docker_user_lemp';
$password = getenv('DB_PASS') ?: 'docker_user_lemp';

$dsn = "$vendor:host=$host;dbname=$dbname";
$db = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$stmt = $db->query("SELECT * FROM event_store ORDER BY id DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
