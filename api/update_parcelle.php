
<?php
// api/update_parcelle.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config_supabase.php';

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Vérifier que c'est bien une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

// Lire les données JSON envoyées
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
    exit();
}

// Récupérer l'ID
$id = $data['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID manquant']);
    exit();
}

// Liste des champs autorisés à être modifiés
$allowed_fields = ['liste_attributaire', 'attribution_2026', 'prenom_nom', 'n_parcelle', 'cni', 'tel', 'recensement', 'observation', 'recommendation'];

// Préparer les données à mettre à jour
$update_data = [];
foreach ($data as $key => $value) {
    if (in_array($key, $allowed_fields)) {
        $update_data[$key] = $value;
    }
}

// ⚡ Mise à jour automatique du statut
if (isset($data['prenom_nom'])) {
    $update_data['statut'] = !empty($data['prenom_nom']) ? 'affecte' : 'non affecte';
}

if (empty($update_data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Aucun champ à mettre à jour']);
    exit();
}

// Construire le filtre Supabase
$filter = "id=eq.$id";

// Appeler Supabase REST (PATCH)
$result = supabaseUpdate('parcelle', $filter, $update_data);

if (isset($result['error'])) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur Supabase',
        'message' => $result['error'],
        'response' => $result['response'] ?? null
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Retourner la réponse
echo json_encode([
    'success' => true,
    'message' => 'Parcelle mise à jour avec succès',
    'data' => $result
], JSON_UNESCAPED_UNICODE);
