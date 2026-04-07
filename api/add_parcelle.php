<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/api_auth.php';
requireApiEdit(); // ← bloque si non connecté ou pas éditeur

require_once __DIR__ . '/../config_supabase.php';
require_once __DIR__ . '/log_activite.php';
ob_clean();

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Données JSON invalides']);
    exit;
}

// ── 1. Prochain ID ──
$url = SUPABASE_URL . '/rest/v1/parcelle?select=id&order=id.desc&limit=1';
$headers = [
    'apikey: '        . SUPABASE_ANON_KEY,
    'Authorization: Bearer ' . SUPABASE_ANON_KEY,
    'Content-Type: application/json'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER,     $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT,        10);
$resp     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => "Supabase get ID HTTP $httpCode"]);
    exit;
}

$rows  = json_decode($resp, true);
$maxId = isset($rows[0]['id']) ? (int)$rows[0]['id'] : 0;
$newId = $maxId + 1;

// ── 2. Géométrie Polygon → MultiPolygon ──
$geomRaw = $data['geom'] ?? null;
$geomObj = null;

if ($geomRaw) {
    $geomDecoded = is_string($geomRaw) ? json_decode($geomRaw, true) : $geomRaw;
    if ($geomDecoded && $geomDecoded['type'] === 'Polygon') {
        $geomObj = [
            'type'        => 'MultiPolygon',
            'coordinates' => [$geomDecoded['coordinates']]
        ];
    } else {
        $geomObj = $geomDecoded;
    }
}

// ── 3. Payload ──
$payload = [
    'id'                 => $newId,
    'n_parcelle'         => $data['n_parcelle']         ?? null,
    'liste_attributaire' => $data['liste_attributaire'] ?? null,
    'attribution_2026'   => $data['attribution_2026']   ?? null,
    'prenom_nom'         => $data['prenom_nom']         ?? null,
    'cni'                => $data['cni']                ?? null,
    'tel'                => $data['tel']                ?? null,
    'recensement'        => $data['recensement']        ?? null,
    'observation'        => $data['observation']        ?? null,
    'recommendation'     => $data['recommendation']     ?? null,
    'statut'             => $data['statut']             ?? 'non affecté',
];
if ($geomObj) $payload['geom'] = $geomObj;

// ── 4. Insertion ──
$insertUrl = SUPABASE_URL . '/rest/v1/parcelle';
$insertHeaders = [
    'apikey: '        . SUPABASE_ANON_KEY,
    'Authorization: Bearer ' . SUPABASE_ANON_KEY,
    'Content-Type: application/json',
    'Prefer: return=minimal'
];

$ch2 = curl_init($insertUrl);
curl_setopt($ch2, CURLOPT_HTTPHEADER,     $insertHeaders);
curl_setopt($ch2, CURLOPT_POST,           true);
curl_setopt($ch2, CURLOPT_POSTFIELDS,     json_encode($payload));
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_TIMEOUT,        15);
$resp2     = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

ob_clean();
if ($httpCode2 === 201 || $httpCode2 === 200) {
    // ── LOG ──
    logActivite('ajout', 'parcelle', $newId, [
        'n_parcelle'  => $payload['n_parcelle'],
        'prenom_nom'  => $payload['prenom_nom'],
        'statut'      => $payload['statut'],
    ]);
    echo json_encode([
        'success' => true,
        'id'      => $newId,
        'message' => "Parcelle #$newId créée avec succès"
    ]);
} else {
    $errBody = json_decode($resp2, true);
    $errMsg  = $errBody['message'] ?? $errBody['error'] ?? $resp2;
    echo json_encode(['success' => false, 'error' => "Supabase insert HTTP $httpCode2 : $errMsg"]);
}