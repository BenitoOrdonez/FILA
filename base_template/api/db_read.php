<?php
// api/db_read.php
header('Content-Type: application/json');
require_once 'db.php';

$data = getData();

// Si por algún error la estructura no es correcta, la reparamos al vuelo
if (!$data || !isset($data['tickets'])) {
    echo json_encode(["tickets" => [], "error" => "Archivo vacío o mal formado"]);
} else {
    echo json_encode($data);
}