<?php
// api/update_parcelle.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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

// Connexion à PostgreSQL
$host = "localhost";
$port = "5432";
$dbname = "urbanisme"; // CHANGEZ ICI
$user = "postgres";     // CHANGEZ ICI
$password = "Aicha2004"; // CHANGEZ ICI

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer l'ID et les champs à mettre à jour
    $id = $data['id'] ?? null;
    $gid = $data['gid'] ?? null;
    
    if (!$id && !$gid) {
        throw new Exception("ID ou GID manquant");
    }
    
    // Construire la requête UPDATE dynamiquement
    $updates = [];
    $params = [];
    
    // Liste des champs autorisés à être modifiés (AJUSTEZ SELON VOS BESOINS)
    $allowed_fields = ['liste_attributaire', 'adresse', 'attribution_2026', 'prenom_nom', 'n_parcelle', 'cni', 'tel', 'recensement', 'observation', 'recommendation', 'statut'];
    
    foreach ($data as $key => $value) {
        if (in_array($key, $allowed_fields)) {
            $updates[] = "$key = :$key";
            $params[":$key"] = $value;
        }
    }
    
    if (empty($updates)) {
        throw new Exception("Aucun champ à mettre à jour");
    }
    
    // Ajouter l'ID dans les paramètres
    if ($id) {
        $where = "id = :id";
        $params[':id'] = $id;
    } else {
        $where = "gid = :gid";
        $params[':gid'] = $gid;
    }
    
    // Construire et exécuter la requête
    $sql = "UPDATE parcelle SET " . implode(', ', $updates) . " WHERE $where";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    // Vérifier si une ligne a été modifiée
    if ($stmt->rowCount() === 0) {
        throw new Exception("Parcelle non trouvée ou aucune modification");
    }
    
    // Récupérer les données mises à jour
    $select_sql = "SELECT * FROM parcelle WHERE $where";
    $select_stmt = $conn->prepare($select_sql);
    $select_stmt->execute($id ? [':id' => $id] : [':gid' => $gid]);
    $updated_data = $select_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Parcelle mise à jour avec succès',
        'data' => $updated_data
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>