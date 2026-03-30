<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config_supabase.php';

    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!$data) throw new Exception('JSON invalide : ' . json_last_error_msg());

    // ── Récupérer le prochain ID ──
    $ch = curl_init(SUPABASE_URL . '/rest/v1/parcelle?select=id&order=id.desc&limit=1');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) throw new Exception("Erreur récupération ID: HTTP $code");
    $rows  = json_decode($resp, true);
    $newId = (isset($rows[0]['id']) ? (int)$rows[0]['id'] : 0) + 1;

    // ── Préparer la géométrie ──
    $geomRaw = $data['geom'] ?? null;
    if (!$geomRaw) throw new Exception('Géométrie manquante');
    $geomString = is_array($geomRaw) ? json_encode($geomRaw) : $geomRaw;

    // ── Appel RPC ──
    $rpcPayload = json_encode([
        'p_id'                 => $newId,
        'p_n_parcelle'         => $data['n_parcelle'] ?? null,
        'p_liste_attributaire' => $data['liste_attributaire'] ?? '',
        'p_attribution_2026'   => $data['attribution_2026'] ?? '',
        'p_prenom_nom'         => $data['prenom_nom'] ?? '',
        'p_cni'                => $data['cni'] ?? '',
        'p_tel'                => $data['tel'] ?? '',
        'p_recensement'        => $data['recensement'] ?? '',
        'p_observation'        => $data['observation'] ?? '',
        'p_recommendation'     => $data['recommendation'] ?? '',
        'p_statut'             => $data['statut'] ?? 'non affecté',
        'p_geom_json'          => $geomString,
    ]);

    $ch2 = curl_init(SUPABASE_URL . '/rest/v1/rpc/insert_parcelle_geojson');
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch2, CURLOPT_POST,           true);
    curl_setopt($ch2, CURLOPT_POSTFIELDS,     $rpcPayload);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_TIMEOUT,        15);
    $resp2 = curl_exec($ch2);
    $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    $err2  = curl_error($ch2);
    curl_close($ch2);

    if ($err2) throw new Exception('cURL : ' . $err2);

    $result = json_decode($resp2, true);

    if ($code2 === 200) {
        if (isset($result['success']) && $result['success'] === false) {
            throw new Exception('Erreur SQL : ' . ($result['error'] ?? 'inconnue'));
        }
        echo json_encode([
            'success' => true,
            'id'      => $newId,
            'message' => "Parcelle #$newId créée avec succès"
        ]);
    } else {
        $msg = $result['message'] ?? $result['error'] ?? $resp2;
        throw new Exception("Supabase insert HTTP $code2 : $msg");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
?>