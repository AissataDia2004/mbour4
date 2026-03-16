<?php
// config/auth.php
// À inclure EN HAUT de chaque page protégée

session_start();
require_once __DIR__ . '/users_config.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user'])
        && isset($_SESSION['expire'])
        && time() < $_SESSION['expire'];
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        // Nettoyer la session expirée
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
    }
    // Renouveler la session à chaque requête
    $_SESSION['expire'] = time() + SESSION_DURATION;
}

function getCurrentUser(): array {
    return [
        'username' => $_SESSION['user']  ?? '',
        'role'     => $_SESSION['role']  ?? '',
        'nom'      => $_SESSION['nom']   ?? '',
        'icon'     => $_SESSION['icon']  ?? '',
    ];
}