<?php
// api/parcelles.php
// Récupère toutes les parcelles au format GeoJSON

require_once '../config/db_config.php';

try {
    $sql = "SELECT 
                row_number() OVER () as id_0,
                *,
                ST_AsGeoJSON(ST_Force2D(geom)) as geometry
            FROM parcelle
            ORDER BY id";
    
    $stmt = $pdo->query($sql);
    $features = [];
    while ($row = $stmt->fetch()) {
        // Extraire la géométrie
        $geometry = json_decode($row['geometry'], true);
        
        // Enlever la géométrie des propriétés
        unset($row['geometry']);
        unset($row['geom']);
        
        // Créer le feature GeoJSON
        $feature = [
            "type" => "Feature",
            "geometry" => $geometry,
            "properties" => $row
        ];
        
        $features[] = $feature;
    }
    
    // Construire le GeoJSON final
    $geojson = [
        "type" => "FeatureCollection",
        "crs" => [
            "type" => "name",
            "properties" => [
                "name" => "EPSG:4326"
            ]
        ],
        "features" => $features,
        "totalFeatures" => count($features)
    ];
    
    echo json_encode($geojson);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Erreur lors de la récupération des parcelles",
        "message" => $e->getMessage()
    ]);
}

$pdo = null;
?>