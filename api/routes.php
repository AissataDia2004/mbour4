<?php
// api/routes.php
// Récupère toutes les routes/voies au format GeoJSON

require_once '../config/db_config.php';

try {
    $sql = "SELECT 
                row_number() OVER () as id_0,
                *,
                ST_AsGeoJSON(geom) as geometry,
                ST_Length(geom::geography) as longueur_calculee
            FROM routes
            ORDER BY id";
    
    $stmt = $pdo->query($sql);
    $features = [];
    
    while ($row = $stmt->fetch()) {
        // Extraire la géométrie
        $geometry = json_decode($row['geometry'], true);
        
        // Enlever la géométrie des propriétés
        unset($row['geometry']);
        unset($row['geom']);
        
        // Ajouter la longueur calculée en mètres
        if (isset($row['longueur_calculee'])) {
            $row['longueur_calculee'] = round(floatval($row['longueur_calculee']), 2);
        }
        
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
        "error" => "Erreur lors de la récupération des routes",
        "message" => $e->getMessage()
    ]);
}

$pdo = null;
?>