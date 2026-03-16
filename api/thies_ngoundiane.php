<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Configuration de la base de données
$host = 'localhost';
$dbname = 'urbanisme';
$user = 'postgres';
$password = 'Aicha2004';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Requête pour récupérer la commune de Thiès Nord en GeoJSON
    $sql = "
        SELECT jsonb_build_object(
            'type', 'FeatureCollection',
            'features', jsonb_agg(feature)
        ) AS geojson
        FROM (
            SELECT jsonb_build_object(
                'type', 'Feature',
                'geometry', ST_AsGeoJSON(geom)::jsonb,
                'properties', to_jsonb(row) - 'geom'
            ) AS feature
            FROM (
                SELECT * FROM thies_ngoundiane
            ) row
        ) features;
    ";
    
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['geojson']) {
        echo $result['geojson'];
    } else {
        echo json_encode([
            'type' => 'FeatureCollection',
            'features' => []
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de connexion: ' . $e->getMessage()]);
}
?>