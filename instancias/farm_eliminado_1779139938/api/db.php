<?php
session_name('filas_' . preg_replace('/[^a-zA-Z0-9_]/', '_', basename(dirname(__DIR__))));
session_start();

// Detecta la ruta de la carpeta actual de la instancia (ej: /instancias/pitrufquen/)
function getBasePath() {
    return dirname(__DIR__);
}

function getData() {
    $path = getBasePath() . '/data/queue.json';
    if (!file_exists($path)) {
        $initial = ["ultimo_numero" => 0, "tickets" => [], "stats" => ["total" => 0]];
        file_put_contents($path, json_encode($initial, JSON_PRETTY_PRINT));
    }
    $data = json_decode(file_get_contents($path), true);
    if (!isset($data['stats']) || !is_array($data['stats'])) {
        $data['stats'] = ['total' => 0];
    }
    if (!isset($data['stats']['total'])) {
        $data['stats']['total'] = 0;
    }
    return $data;
}

function updateData($data) {
    file_put_contents(getBasePath() . '/data/queue.json', json_encode($data, JSON_PRETTY_PRINT));
}

function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Función de autenticación
function checkAuth($required_role = null) {
    if (!isset($_SESSION['user'])) {
        header("Location: ../login.php");
        exit;
    }
    if ($required_role && (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role)) {
        header("Location: ../login.php?error=access_denied");
        exit;
    }
}

// Función de Log Unificada
function writeLog($mensaje) {
    $log_path = getBasePath() . '/data/audit_log.json';
    $logs = file_exists($log_path) ? json_decode(file_get_contents($log_path), true) : [];
    array_unshift($logs, [
        "fecha" => date("d-m-Y H:i:s"),
        "admin" => $_SESSION['user'] ?? 'Sistema',
        "accion" => $mensaje,
        "ip" => $_SERVER['REMOTE_ADDR']
    ]);
    file_put_contents($log_path, json_encode(array_slice($logs, 0, 100), JSON_PRETTY_PRINT));
}
?>