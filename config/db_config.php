<?php
// config/db_config.php
// Configuration de connexion à PostgreSQL pour la base urbanisme

$host = 'localhost';
$dbname = 'urbanisme'; 
$user = 'postgres';
$pass = 'Aicha2004';
$port = 5432;

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode([
        "error" => "Erreur de connexion à la base de données",
        "message" => $e->getMessage()
    ]));
}

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
?>