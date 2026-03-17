<?php
// api/parcelles.php
// Récupère toutes les parcelles au format GeoJSON via Supabase REST API avec pagination

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config_supabase.php';

// Fonction pour récupérer un batch avec Range
function supabaseGetRange($table, $from, $to) {
    $url = SUPABASE_URL . '/rest/v1/' . $table;

    $headers = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json',
        "Range: $from-$to"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 || $httpCode === 206) {
        return json_decode($response, true);
    } else {
        return ['error' => "HTTP $httpCode", 'response' => $response];
    }
}

try {
    $batchSize = 1000;
    $offset = 0;
    $allRows = [];

    // Boucle jusqu'à ce qu'il n'y ait plus de résultats
    while (true) {
        $rows = supabaseGetRange('parcelle', $offset, $offset + $batchSize - 1);

        if (isset($rows['error'])) {
            http_response_code(500);
            echo json_encode([
                "error" => "Erreur Supabase",
                "message" => $rows['error'],
                "response" => $rows['response'] ?? null
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (empty($rows)) {
            break; // plus de données
        }

        $allRows = array_merge($allRows, $rows);
        $offset += $batchSize;
    }

    // Transformer en GeoJSON
    $features = [];
    foreach ($allRows as $row) {
        $geometry = $row['geom'] ?? null;
        unset($row['geom']);

        $features[] = [
            "type" => "Feature",
            "geometry" => $geometry,
            "properties" => $row
        ];
    }

    $geojson = [
        "type" => "FeatureCollection",
        "crs" => [
            "type" => "name",
            "properties" => ["name" => "EPSG:4326"]
        ],
        "features" => $features,
        "totalFeatures" => count($features)
    ];

    echo json_encode($geojson, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Erreur lors de la récupération des parcelles",
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
