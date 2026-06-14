<?php
require_once 'db.php';
//este archivo es el observador de estado de las variables.
function normalizar($texto) {
    $buscar =  ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ'];
    $reemplazar = ['a','e','i','o','u','A','E','I','O','U','n','N'];
    return strtoupper(trim(str_replace($buscar, $reemplazar, $texto)));
}

$data = getData();
$config = json_decode(file_get_contents('../data/config.json'), true);

$stats = [];
foreach($config['areas'] as $a) {
    $norm_a = normalizar($a);
    $stats[$norm_a] = [
        'atendidos' => 0,
        'espera' => 0,
        'llamado_actual' => '---'
    ];
}

foreach($data['tickets'] as $t) {
    $area_norm = normalizar($t['tramite'] ?? '');
    if(!isset($stats[$area_norm])) continue;

    if($t['estado'] === 'esperando') $stats[$area_norm]['espera']++;
    if($t['estado'] === 'atendiendo' || $t['estado'] === 'llamado') {
        $stats[$area_norm]['llamado_actual'] = $t['id'];
    }
    if($t['estado'] === 'atendido' || $t['estado'] === 'finalizado') {
        $stats[$area_norm]['atendidos']++;
    }
}

header('Content-Type: application/json');
echo json_encode($stats);