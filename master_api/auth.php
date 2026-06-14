<?php
// master_api/auth.php
session_start();

function checkSuperAuth() {
    if (!isset($_SESSION['super_token'])) {
        header("Location: login.php");
        exit;
    }
}

function getGlobalData() {
    $path = __DIR__ . '/../global_data/instituciones.json';
    if (!file_exists($path)) {
        return ['instituciones' => []]; // Evita errores si está vacío
    }
    return json_decode(file_get_contents($path), true);
}

function saveGlobalData($data) {
    $path = __DIR__ . '/../global_data/instituciones.json';
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
}
?>