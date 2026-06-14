<?php
header('Content-Type: application/json');
require_once 'db.php';

$config = json_decode(file_get_contents('../data/config.json'), true);

$id = $_GET['id'] ?? '';
$hash = $_GET['h'] ?? ''; // Recibimos el hash
$data = getData();
$user_ticket = null;
$pos_final = null;

// Primero encontrar el ticket del usuario y su área
foreach ($data['tickets'] as $t) {
    if ($t['id'] === $id) {
        // VALIDACIÓN DE SEGURIDAD
        if (!isset($t['hash']) || $t['hash'] !== $hash) {
            echo json_encode(["error" => "Acceso no autorizado"]);
            exit;
        }
        $user_ticket = $t;
        break;
    }
}

if (!$user_ticket) {
    echo json_encode(["error" => "No encontrado"]);
    exit;
}

// Ahora contar la posición en la cola
if ($user_ticket['estado'] !== 'esperando') {
    // Si no está esperando (está siendo atendido, finalizado, etc.), posición es 0
    $pos_final = 0;
} else {
    // Contar cuántos tickets "esperando" de la misma área están antes de este
    $area_user = explode('-', $id)[1] ?? '';
    $posicion = 0;
    
    foreach ($data['tickets'] as $t) {
        if ($t['estado'] === 'esperando' && $t['id'] !== $id) {
            $area_ticket = explode('-', $t['id'])[1] ?? '';
            if ($area_ticket === $area_user) {
                // Comparar el número correlativo para determinar orden
                $user_corr = (int)explode('-', $id)[2] ?? 0;
                $ticket_corr = (int)explode('-', $t['id'])[2] ?? 0;
                if ($ticket_corr < $user_corr) {
                    $posicion++;
                }
            }
        }
    }
    $pos_final = $posicion + 1; // +1 porque la posición es 1-based
}

echo json_encode([
    "id" => $user_ticket['id'],
    "nombre" => $user_ticket['nombre'],
    "estado" => $user_ticket['estado'],
    "posicion" => $pos_final,
    "institucion" => $config['nombre_institucion'],
    "calificacion" => $user_ticket['calificacion'] ?? null,
    "comentario_ciudadano" => $user_ticket['comentario_ciudadano'] ?? null,
    "hora_salida" => $user_ticket['hora_salida'] ?? null
]);