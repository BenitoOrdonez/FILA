<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$token_recibido = $_POST['token'] ?? '';
$es_valido = false;

// Verificamos los últimos 5 minutos
for ($i = 0; $i < 5; $i++) {
    $check_time = gmdate("Y-m-d\TH:i", strtotime("-$i minutes"));
    $token_check = str_replace('=', '', base64_encode($check_time));
    if ($token_recibido === $token_check) {
        $es_valido = true;
        break;
    }
}

if (!$es_valido) {
    die("Error: El código QR ha expirado. Por favor escanee el nuevo código en pantalla.");
}

$data = getData();
$tramite = clean($_POST['tramite']);
$nombre = clean($_POST['nombre']);
$rut = clean($_POST['rut'] ?? ""); // Evita el Warning de "rut" no definido

if (!isset($data['stats']) || !is_array($data['stats'])) {
    $data['stats'] = ['total' => 0];
}
if (!isset($data['stats']['total'])) {
    $data['stats']['total'] = 0;
}

// Generar ID
$area_pref = strtoupper(substr($tramite, 0, 3));

// Calcular correlativo por área
$max_correlativo = 0;
foreach ($data['tickets'] as $t) {
    if (isset($t['tramite']) && $t['tramite'] === $tramite) {
        // Extraer correlativo si el ID tiene el formato esperado
        if (preg_match('/^\d{4}-' . preg_quote($area_pref) . '-\d{3}$/', $t['id'])) {
            $parts = explode('-', $t['id']);
            $num = intval($parts[2]);
            if ($num > $max_correlativo) $max_correlativo = $num;
        }
    }
}
$correlativo = $max_correlativo + 1;

$id = date("dm") . "-" . $area_pref . "-" . str_pad($correlativo, 3, "0", STR_PAD_LEFT);
$hash = bin2hex(random_bytes(5));

$nuevo_ticket = [
    "id" => $id,
    "hash" => $hash,
    "nombre" => $nombre,
    "rut" => $rut,
    "tramite" => $tramite,
    "estado" => "esperando",
    "creado" => date("H:i:s")
];

$data['tickets'][] = $nuevo_ticket;
$data['stats']['total']++;
updateData($data);

header("Location: ../status.php?id=$id&h=$hash");
exit;