<?php
require_once 'db.php';
date_default_timezone_set('America/Santiago');

$tipo = $_GET['tipo'] ?? 'hoy';
$target_d = $_GET['d'] ?? date('d');
$target_m = $_GET['m'] ?? date('m');
$target_y = $_GET['y'] ?? date('Y');
$fecha_desde = $_GET['fecha_desde'] ?? null;
$fecha_hasta = $_GET['fecha_hasta'] ?? null;

// Definir nombre del archivo según el rango
if ($tipo === 'hoy') {
    $rango_txt = date('Y-m-d');
} elseif ($tipo === 'rango' && $fecha_desde && $fecha_hasta) {
    $rango_txt = "{$fecha_desde}_a_{$fecha_hasta}";
} else {
    $rango_txt = "$target_y-$target_m-$target_d";
}
$filename = "Reporte_Fila_{$rango_txt}.csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "\xEF\xBB\xBF"; // BOM para Excel

$output = fopen('php://output', 'w');
fputcsv($output, ['ID Ticket', 'Fecha', 'Nombre', 'RUT', 'Tramite', 'Estado', 'Ejecutivo', 'Hora Atencion', 'Calificacion'], ';');

$archivos = glob('../data/daily/queue_*.json');
foreach ($archivos as $archivo) {
    $fecha_archivo = str_replace(['../data/daily/queue_', '.json'], '', $archivo);
    $partes = explode('-', $fecha_archivo);
    
    $incluir = false;
    if ($tipo === 'hoy' && $fecha_archivo === date('Y-m-d')) {
        $incluir = true;
    } elseif ($tipo === 'rango' && $fecha_desde && $fecha_hasta) {
        $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha_archivo);
        $desde_obj = DateTime::createFromFormat('Y-m-d', $fecha_desde);
        $hasta_obj = DateTime::createFromFormat('Y-m-d', $fecha_hasta);
        if ($fecha_obj && $desde_obj && $hasta_obj && $fecha_obj >= $desde_obj && $fecha_obj <= $hasta_obj) {
            $incluir = true;
        }
    } elseif ($tipo === 'personalizado' && $partes[0] == $target_y && $partes[1] == $target_m && $partes[2] == $target_d) {
        $incluir = true;
    }

    if ($incluir) {
        $contenido = json_decode(file_get_contents($archivo), true);
        if (isset($contenido['tickets'])) {
            foreach ($contenido['tickets'] as $t) {
                fputcsv($output, [
                    $t['id'], $fecha_archivo, $t['nombre'], $t['rut'], $t['tramite'], 
                    $t['estado'], $t['atendido_por'] ?? '-', $t['hora_atencion'] ?? '-', $t['calificacion'] ?? '-'
                ], ';');
            }
        }
    }
}
fclose($output);