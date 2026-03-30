<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config_supabase.php';

    if (!defined('SUPABASE_URL') || !defined('SUPABASE_ANON_KEY')) {
        throw new Exception('Configuration Supabase manquante');
    }

    // Lire les données POST
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!$data) {
        throw new Exception('Données JSON invalides : ' . json_last_error_msg());
    }

    // ── 1. Récupérer le prochain ID ──────────────────────────
    $url = SUPABASE_URL . '/rest/v1/parcelle?select=id&order=id.desc&limit=1';
    $headers = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER,     $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT,        10);
    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) throw new Exception('cURL error (get ID): ' . $curlErr);
    if ($httpCode !== 200) throw new Exception("Supabase get ID HTTP $httpCode: $resp");

    $rows  = json_decode($resp, true);
    $maxId = isset($rows[0]['id']) ? (int)$rows[0]['id'] : 0;
    $newId = $maxId + 1;

    // ── 2. Préparer la géométrie ─────────────────────────────
    // Le JS envoie geom comme string JSON → on décode pour l'envoyer comme objet
    $geomRaw = $data['geom'] ?? null;
    $geomObj = null;

    if ($geomRaw) {
        // Si c'est une string JSON, on décode
        if (is_string($geomRaw)) {
            $geomObj = json_decode($geomRaw, true);
        } else {
            $geomObj = $geomRaw;
        }
    }

    // ── 3. Construire le payload ─────────────────────────────
    $payload = [
        'id'          => $newId,
        'n_parcelle'  => $data['n_parcelle']  ?? null,
        'prenom_nom'  => $data['prenom_nom']   ?? null,
        'adresse'     => $data['adresse']       ?? null,
        'tel'         => $data['tel']           ?? null,
        'cni'         => $data['cni']           ?? null,
        'statut'      => $data['statut']        ?? 'non affecté',
        'observation' => $data['observation']   ?? null,
    ];

    // Ajouter la géométrie seulement si valide
    if ($geomObj) {
        $payload['geom'] = $geomObj;
    }

    // ── 4. Insérer dans Supabase ─────────────────────────────
    $insertUrl = SUPABASE_URL . '/rest/v1/parcelle';
    $insertHeaders = [
        'apikey: ' . SUPABASE_ANON_KEY,
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
    $curlErr2  = curl_error($ch2);
    curl_close($ch2);

    if ($curlErr2) throw new Exception('cURL error (insert): ' . $curlErr2);

    // Supabase retourne 201 pour un insert réussi
    if ($httpCode2 === 201 || $httpCode2 === 200) {
        echo json_encode([
            'success' => true,
            'id'      => $newId,
            'message' => "Parcelle #$newId créée avec succès"
        ]);
    } else {
        // Décoder l'erreur Supabase
        $errBody = json_decode($resp2, true);
        $errMsg  = $errBody['message'] ?? $errBody['error'] ?? $resp2;
        throw new Exception("Supabase insert HTTP $httpCode2 : $errMsg");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
?>