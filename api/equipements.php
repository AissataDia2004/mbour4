<?php
// api/equipements.php
// Récupère tous les équipements au format GeoJSON

require_once '../config/db_config.php';

try {
    $sql = "SELECT 
                row_number() OVER () as id_0,
                *,
                ST_AsGeoJSON(geom) as geometry
            FROM equipement_zar
            ORDER BY id";
    
    $stmt = $pdo->query($sql);
    $features = [];
    
    while ($row = $stmt->fetch()) {
        $geometry = json_decode($row['geometry'], true);
        
        unset($row['geometry']);
        unset($row['geom']);
        
        $feature = [
            "type" => "Feature",
            "geometry" => $geometry,
            "properties" => $row
        ];
        
        $features[] = $feature;
    }
    
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
        "error" => "Erreur lors de la récupération des équipements",
        "message" => $e->getMessage()
    ]);
}

$pdo = null;
?>