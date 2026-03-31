<?php
// config/users_config.php
define('USERS', [
    'urbanisme' => [
        'password' => password_hash('urbanisme2026', PASSWORD_DEFAULT),
        'role'     => 'editeur',
        'nom'      => 'Service Urbanisme',
        'icon'     => ''
    ],
    'cadastre' => [
        'password' => password_hash('cadastre2026', PASSWORD_DEFAULT),
        'role'     => 'editeur',
        'nom'      => 'Service Cadastre',
        'icon'     => ''
    ],
    'domaine' => [
        'password' => password_hash('domaine2026', PASSWORD_DEFAULT),
        'role'     => 'visiteur',
        'nom'      => 'Service Domaine',
        'icon'     => ''
    ],
    'gouverneur' => [
        'password' => password_hash('gouverneur2026', PASSWORD_DEFAULT),
        'role'     => 'visiteur',
        'nom'      => 'Gouverneur',
        'icon'     => ''
    ],
    'dgua' => [
        'password' => password_hash('dgua2026', PASSWORD_DEFAULT),
        'role'     => 'visiteur',
        'nom'      => 'DGUA',
        'icon'     => ''
    ],
]);

define('SESSION_DURATION', 28800);