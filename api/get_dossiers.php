<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // 🔑 CONNEXION POSTGRESQL
    $host = "localhost";
    $port = "5432";
    $dbname = "urbanisme";
    $user = "postgres";
    $password = "Aicha2004";  // ⚠️ Mettez votre mot de passe

    $conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password";
    $conn = pg_connect($conn_string);

    if (!$conn) {
        throw new Exception('Connexion à PostgreSQL échouée');
    }

    $query = "SELECT * FROM dossiers ORDER BY date_creation DESC";
    $result = pg_query($conn, $query);

    if (!$result) {
        throw new Exception('Erreur requête: ' . pg_last_error($conn));
    }

    $dossiers = [];
    while ($row = pg_fetch_assoc($result)) {
        $dossiers[] = $row;
    }

    echo json_encode($dossiers);

    pg_close($conn);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>