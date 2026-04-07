<?php
// config/api_auth.php
// À inclure en haut de chaque fichier api/*.php

ob_start();
ini_set('display_errors', 0);
error_reporting(0);

session_start();
require_once __DIR__ . '/users_config.php';

function requireApiAuth(): void {
    // Vérifier session active
    if (!isset($_SESSION['user']) || !isset($_SESSION['expire']) || time() >= $_SESSION['expire']) {
        http_response_code(401);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Non autorisé — session expirée']);
        exit;
    }
}

function requireApiEdit(): void {
    requireApiAuth();
    // Vérifier que l'utilisateur peut modifier
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, ['editeur'])) {
        http_response_code(403);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Accès refusé — droits insuffisants']);
        exit;
    }
}

function getApiUser(): string {
    return $_SESSION['nom'] ?? $_SESSION['user'] ?? 'inconnu';
}

function getApiRole(): string {
    return $_SESSION['role'] ?? '';
}