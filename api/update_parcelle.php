<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/api_auth.php';
requireApiEdit();

require_once __DIR__ . '/../config_supabase.php';
require_once __DIR__ . '/log_activite.php';
ob_clean();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if (!$data) { http_response_code(400); echo json_encode(['error' => 'Données invalides']); exit; }

$id = $data['id'] ?? null;
if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID manquant']); exit; }

$allowed_fields = [
    'liste_attributaire', 'attribution_2026', 'prenom_nom',
    'n_parcelle', 'cni', 'tel', 'recensement', 'observation', 'recommendation'
];

$update_data = [];
foreach ($data as $key => $value) {
    if (in_array($key, $allowed_fields)) {
        $update_data[$key] = $value;
    }
}

// ⚡ Logique automatique du statut
if (isset($data['prenom_nom'])) {
    $update_data['statut'] = !empty($data['prenom_nom']) ? 'affecte' : 'non affecte';
}

if (empty($update_data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Aucun champ à mettre à jour']);
    exit;
}

$filter = "id=eq.$id";
$result = supabaseUpdate('parcelle', $filter, $update_data);

ob_clean();
if (isset($result['error'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $result['error']], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── LOG ──
logActivite('modification', 'parcelle', (int)$id, $update_data);

echo json_encode([
    'success' => true,
    'message' => 'Parcelle mise à jour avec succès',
    'data'    => $result
], JSON_UNESCAPED_UNICODE);