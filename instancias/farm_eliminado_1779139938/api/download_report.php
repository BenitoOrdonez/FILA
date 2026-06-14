<?php
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') { die("Acceso denegado"); }

$data = getData();
$filename = "reporte_atenciones_" . date("d-m-Y") . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// Encabezados del CSV
fputcsv($output, ['ID Ticket', 'Nombre Ciudadano', 'RUT', 'Area/Tramite', 'Estado', 'Hora Llegada', 'Hora Atencion', 'Atendido Por', 'Calificacion', 'Observaciones']);

foreach ($data['tickets'] as $t) {
    fputcsv($output, [
        $t['id'],
        $t['nombre'],
        $t['rut'],
        $t['tramite'],
        $t['estado'],
        $t['hora_llegada'],
        $t['hora_atencion'] ?? 'N/A',
        $t['atendido_por'] ?? 'N/A',
        $t['calificacion'] ?? 'Sin calificar',
        $t['observacion'] ?? ''
    ]);
}

fclose($output);
exit;