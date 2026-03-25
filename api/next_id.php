<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config_supabase.php';

function supabaseGet($endpoint) {
    $url = SUPABASE_URL . '/rest/v1/' . $endpoint;
    $headers = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json'
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

try {
    // Récupérer le MAX(id) via Supabase
    $rows = supabaseGet('parcelle?select=id&order=id.desc&limit=1');

    $maxId   = isset($rows[0]['id']) ? (int)$rows[0]['id'] : 0;
    $next_id = $maxId + 1;

    echo json_encode(['next_id' => $next_id]);

} catch (Exception $e) {
    echo json_encode(['next_id' => null, 'error' => $e->getMessage()]);
}
?>