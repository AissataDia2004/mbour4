<?php
// config_supabase.php - Configuration API REST Supabase

define('SUPABASE_URL', 'https://hbjpwdidmbzpafkcqxpn.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImhianB3ZGlkbWJ6cGFma2NxeHBuIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjgzNzkzNTksImV4cCI6MjA4Mzk1NTM1OX0.29YH5-6GZUaRCUJ_MxwL9YYRLxhRyzSIJC9BG3ucQ_0'); // Remplacez par votre clé anon

// Fonction pour récupérer des données (GET)
function supabaseGet($table, $filters = '') {
    $url = SUPABASE_URL . '/rest/v1/' . $table;
    
    if (!empty($filters)) {
        $url .= '?' . $filters;
    }
    
    $headers = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }
    
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    } else {
        return ['error' => 'HTTP ' . $httpCode, 'response' => $response];
    }
}

// Fonction pour insérer des données (POST)
function supabaseInsert($table, $data) {
    $url = SUPABASE_URL . '/rest/v1/' . $table;
    
    $headers = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }
    
    curl_close($ch);
    
    if ($httpCode === 201 || $httpCode === 200) {
        return json_decode($response, true);
    } else {
        return ['error' => 'HTTP ' . $httpCode, 'response' => $response];
    }
}

// Fonction pour mettre à jour (PATCH)
function supabaseUpdate($table, $filters, $data) {
    $url = SUPABASE_URL . '/rest/v1/' . $table . '?' . $filters;
    
    $headers = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }
    
    curl_close($ch);
    
    if ($httpCode === 200 || $httpCode === 204) {
        return json_decode($response, true);
    } else {
        return ['error' => 'HTTP ' . $httpCode, 'response' => $response];
    }
}

// Fonction pour supprimer (DELETE)
function supabaseDelete($table, $filters) {
    $url = SUPABASE_URL . '/rest/v1/' . $table . '?' . $filters;
    
    $headers = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }
    
    curl_close($ch);
    
    if ($httpCode === 204 || $httpCode === 200) {
        return ['success' => true];
    } else {
        return ['error' => 'HTTP ' . $httpCode, 'response' => $response];
    }
}
?>