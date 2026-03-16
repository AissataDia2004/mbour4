<?php
require_once '../config/db_config.php';
header('Content-Type: application/json');

try {
    $sql = "SELECT *, ST_AsGeoJSON(ST_Force2D(geom)) as geometry 
            FROM modifier";  // ← nom de votre table
    
    $stmt = $pdo->query($sql);
    $features = [];

    while ($row = $stmt->fetch()) {
        $geometry = json_decode($row['geometry'], true);
        unset($row['geometry']);
        unset($row['geom']);
        $features[] = [
            "type"       => "Feature",
            "geometry"   => $geometry,
            "properties" => $row
        ];
    }

    echo json_encode([
        "type"     => "FeatureCollection",
        "features" => $features
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
$pdo = null;
?>