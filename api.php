<?php
// ============================================
// Fichier 1: api.php
// Placez ce fichier à la racine de votre site
// ============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Fichier de stockage des utilisateurs
$usersFile = 'users.json';

// Credentials admin
define('ADMIN_USERNAME', 'chef_division');
define('ADMIN_PASSWORD', 'Chef2026');

// Initialiser le fichier si inexistant
if (!file_exists($usersFile)) {
    file_put_contents($usersFile, json_encode([]));
}

// Fonction pour lire les utilisateurs
function getUsers() {
    global $usersFile;
    $content = file_get_contents($usersFile);
    return json_decode($content, true) ?: [];
}

// Fonction pour sauvegarder les utilisateurs
function saveUsers($users) {
    global $usersFile;
    return file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
}

// Récupérer l'action
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        // Connexion agent
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        
        $users = getUsers();
        $user = null;
        
        foreach ($users as $u) {
            if ($u['email'] === $email && $u['password'] === $password) {
                $user = $u;
                break;
            }
        }
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'user' => [
                    'firstName' => $user['firstName'],
                    'lastName' => $user['lastName'],
                    'email' => $user['email']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Identifiants incorrects']);
        }
        break;
    
    case 'admin_login':
        // Connexion admin
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        
        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Identifiants incorrects']);
        }
        break;
    
    case 'get_users':
        // Récupérer tous les utilisateurs (admin seulement)
        $users = getUsers();
        echo json_encode(['success' => true, 'users' => $users]);
        break;
    
    case 'add_user':
        // Ajouter un utilisateur
        $data = json_decode(file_get_contents('php://input'), true);
        
        $users = getUsers();
        
        // Vérifier si l'email existe déjà
        foreach ($users as $u) {
            if ($u['email'] === $data['email']) {
                echo json_encode(['success' => false, 'message' => 'Email déjà existant']);
                exit;
            }
        }
        
        // Ajouter le nouvel utilisateur
        $users[] = [
            'firstName' => $data['firstName'],
            'lastName' => $data['lastName'],
            'email' => $data['email'],
            'password' => $data['password']
        ];
        
        if (saveUsers($users)) {
            echo json_encode(['success' => true, 'message' => 'Utilisateur ajouté']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur de sauvegarde']);
        }
        break;
    
    case 'delete_user':
        // Supprimer un utilisateur
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        
        $users = getUsers();
        $newUsers = array_filter($users, function($u) use ($email) {
            return $u['email'] !== $email;
        });
        
        // Réindexer le tableau
        $newUsers = array_values($newUsers);
        
        if (saveUsers($newUsers)) {
            echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur de suppression']);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        break;
}
?>