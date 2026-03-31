<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config_supabase.php';
ob_clean();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if (!$data || empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID manquant']);
    exit();
}

$id     = intval($data['id']);
$filter = "id=eq.$id";
$result = supabaseDelete('parcelle', $filter);

ob_clean();
if (isset($result['error'])) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $result['error'],
        'details' => $result['response'] ?? null
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

echo json_encode([
    'success' => true,
    'message' => "Parcelle #$id supprimée avec succès"
], JSON_UNESCAPED_UNICODE);