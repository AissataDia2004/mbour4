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
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function supabaseInsert($table, $data) {
    $url = SUPABASE_URL . '/rest/v1/' . $table;
    $headers = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'  // retourne la ligne insérée
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Données invalides']);
        exit;
    }

    // ── 1. Récupérer le prochain ID ──────────────────────────
    $rows  = supabaseGet('parcelle?select=id&order=id.desc&limit=1');
    $maxId = isset($rows[0]['id']) ? (int)$rows[0]['id'] : 0;
    $newId = $maxId + 1;

    // ── 2. Construire l'objet à insérer ──────────────────────
    // Supabase stocke la géométrie en WKT ou GeoJSON selon votre config
    // Si votre colonne 'geom' est de type geometry dans Supabase,
    // passez le GeoJSON directement (Supabase le convertit)
    $geomData = $data['geom'] ?? null;

    $payload = [
        'id'             => $newId,
        'n_parcelle'     => $data['n_parcelle']     ?? null,
        'prenom_nom'     => $data['prenom_nom']      ?? null,
        'adresse'        => $data['adresse']          ?? null,
        'tel'            => $data['tel']              ?? null,
        'cni'            => $data['cni']              ?? null,
        'statut'         => $data['statut']           ?? 'non affecté',
        'observation'    => $data['observation']      ?? null,
        'recommendation' => $data['recommendation']   ?? null,
        'geom'           => $geomData,
    ];

    // ── 3. Insérer dans Supabase ──────────────────────────────
    $result = supabaseInsert('parcelle', $payload);

    if ($result['code'] === 201) {
        echo json_encode([
            'success' => true,
            'id'      => $newId,
            'message' => "Parcelle #$newId créée avec succès"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'error'   => 'Erreur Supabase',
            'code'    => $result['code'],
            'details' => $result['body']
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>