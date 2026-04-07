<?php
// api/log_activite.php
// Fonction utilitaire — ne pas appeler directement

require_once __DIR__ . '/../config_supabase.php';

function logActivite(string $action, string $tableCible, ?int $enregistrementId, array $details = []): void {
    try {
        $payload = [
            'utilisateur'      => $_SESSION['nom']  ?? $_SESSION['user'] ?? 'inconnu',
            'role'             => $_SESSION['role']  ?? '',
            'action'           => $action,
            'table_cible'      => $tableCible,
            'enregistrement_id'=> $enregistrementId,
            'details'          => $details,
            'ip'               => $_SERVER['HTTP_X_FORWARDED_FOR']
                                  ?? $_SERVER['REMOTE_ADDR']
                                  ?? 'inconnue',
        ];

        $url = SUPABASE_URL . '/rest/v1/logs_activite';
        $headers = [
            'apikey: '        . SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . SUPABASE_ANON_KEY,
            'Content-Type: application/json',
            'Prefer: return=minimal'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER,     $headers);
        curl_setopt($ch, CURLOPT_POST,           true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,     json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT,        5);
        curl_exec($ch);
        curl_close($ch);
    } catch (Exception $e) {
        // Le log ne doit jamais bloquer l'opération principale
    }
}