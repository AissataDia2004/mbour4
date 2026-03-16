<?php
// config/users_config.php
// Configuration des utilisateurs du géoportail

define('USERS', [
    // Urbanisme
    'urbanisme' => [
        'password' => password_hash('urbanisme2026', PASSWORD_DEFAULT),
        'role'     => 'urbanisme',
        'nom'      => 'Service Urbanisme',
        'icon'     => ''
    ],
    // Domaine
    'domaine' => [
        'password' => password_hash('domaine2026', PASSWORD_DEFAULT),
        'role'     => 'domaine',
        'nom'      => 'Service Domaine',
        'icon'     => ''
    ],
    // Cadastre
    'cadastre' => [
        'password' => password_hash('cadastre2026', PASSWORD_DEFAULT),
        'role'     => 'cadastre',
        'nom'      => 'Service Cadastre',
        'icon'     => ''
    ],
]);

// Durée de session en secondes (8 heures)
define('SESSION_DURATION', 28800);