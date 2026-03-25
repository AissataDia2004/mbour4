<?php
header('Content-Type: application/json; charset=utf-8');

// Bloquer l'affichage des erreurs PHP dans la réponse
ini_set('display_errors', 0);
error_reporting(0);

try {
    require_once __DIR__ . '/../config_supabase.php';

    // Vérifier que les constantes existent
    if (!defined('SUPABASE_URL') || !defined('SUPABASE_ANON_KEY')) {
        throw new Exception('Configuration Supabase manquante');
    }

    $url = SUPABASE_URL . '/rest/v1/parcelle?select=id&order=id.desc&limit=1';

    $headers = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER,     $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT,        10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception('Erreur cURL : ' . $curlError);
    }

    if ($httpCode !== 200) {
        throw new Exception("Supabase HTTP $httpCode : $response");
    }

    $rows  = json_decode($response, true);
    $maxId = isset($rows[0]['id']) ? (int)$rows[0]['id'] : 0;

    echo json_encode(['next_id' => $maxId + 1]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'next_id' => null,
        'error'   => $e->getMessage()
    ]);
}
?>