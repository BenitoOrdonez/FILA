<?php
// Configurar zona horaria de Chile
date_default_timezone_set('America/Santiago'); 

require_once 'api/db.php';

$config_file = 'data/config.json';
$config = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];
$data = getData(); 

// --- FUNCIÓN DE NORMALIZACIÓN PARA UNIFICAR "TRANSITO" ---
function normalizar($texto) {
    if (!$texto) return '';
    $buscar =  ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ'];
    $reemplazar = ['a','e','i','o','u','A','E','I','O','U','n','N'];
    // Reemplaza tildes y espacios extras para asegurar coincidencia
    return trim(str_replace($buscar, $reemplazar, $texto));
}

$stats_areas = [];

// 1. Inicializar áreas desde config (Normalizando nombres)
if (isset($config['areas']) && is_array($config['areas'])) {
    foreach($config['areas'] as $a) { 
        $nombre_limpio = normalizar($a);
        // Si no existe, lo inicializamos. Si ya existe (ej: "Tránsito" y "Transito"), se agrupan.
        if (!isset($stats_areas[$nombre_limpio])) {
            $stats_areas[$nombre_limpio] = [
                'display_name' => $nombre_limpio, 
                'atendidos' => 0, 
                'espera' => 0, 
                'suma_tiempo' => 0
            ];
        }
    }
}

// 2. Procesar tickets dinámicamente
if (isset($data['tickets']) && is_array($data['tickets'])) {
    foreach($data['tickets'] as $t) {
        $area_ticket = normalizar($t['tramite'] ?? 'Sin Area');
        
        // Si el área no estaba en la configuración inicial, la agregamos
        if(!isset($stats_areas[$area_ticket])) { 
            $stats_areas[$area_ticket] = [
                'display_name' => $area_ticket, 
                'atendidos' => 0, 
                'espera' => 0, 
                'suma_tiempo' => 0
            ]; 
        }

        // Conteo según estado
        if($t['estado'] === 'esperando') {
            $stats_areas[$area_ticket]['espera']++;
        }

        if($t['estado'] === 'finalizado') {
            $stats_areas[$area_ticket]['atendidos']++;
            if(!empty($t['hora_atencion']) && !empty($t['hora_finalizado'])) {
                $inicio = strtotime($t['hora_atencion']);
                $fin = strtotime($t['hora_finalizado']);
                if ($fin > $inicio) { 
                    $stats_areas[$area_ticket]['suma_tiempo'] += ($fin - $inicio) / 60; 
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Atención Municipal de hoy</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f9; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; padding: 20px; background: #1a73e8; color: white; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; font-size: 1.8rem; }
        .header p { margin: 5px 0 0; opacity: 0.8; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-top: 5px solid #1a73e8; }
        .area-title { font-size: 1.4rem; font-weight: bold; color: #1a73e8; margin-bottom: 15px; text-transform: uppercase; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        
        .stat-row { display: flex; justify-content: space-between; align-items: center; margin: 12px 0; }
        .label { font-size: 1rem; color: #666; font-weight: 500; }
        .val { font-size: 1.8rem; font-weight: 700; }
        .val-espera { color: #d93025; } /* Rojo Google */
        .val-atendidos { color: #1e8e3e; } /* Verde Google */
        
        .footer-card { margin-top: 15px; padding-top: 10px; border-top: 1px dashed #ccc; font-size: 0.9rem; color: #777; }
        
        @media (max-width: 600px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <div class="header">
        <h1>ATENCIÓN MUNICIPAL DE HOY</h1>
        <p>Datos de Hoy, hasta las: <?= date('H:i:s') ?></p>
    </div>

    <div class="grid">
        <?php foreach($stats_areas as $s): 
            // Ignorar el área vacía si no tiene actividad
            if ($s['display_name'] === 'Sin Area' && $s['espera'] == 0 && $s['atendidos'] == 0) continue;
            
            $promedio = $s['atendidos'] > 0 ? round($s['suma_tiempo'] / $s['atendidos'], 1) : 0;
        ?>
        <div class="card">
            <div class="area-title"><?= $s['display_name'] ?></div>
            
            <div class="stat-row">
                <span class="label">Tickets en Fila:</span>
                <span class="val val-espera"><?= $s['espera'] ?></span>
            </div>

            <div class="stat-row">
                <span class="label">Total Atendidos:</span>
                <span class="val val-atendidos"><?= $s['atendidos'] ?></span>
            </div>

            <div class="footer-card">
                <span>Tiempo Promedio de Atención: <strong><?= $promedio ?> min</strong></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        // Recarga automática cada 30 segundos para administración
        setInterval(() => { location.reload(); }, 30000);
    </script>
</body>
</html>