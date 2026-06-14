<?php
date_default_timezone_set('America/Santiago'); 
require_once 'api/db.php';

// --- FUNCIÓN DE NORMALIZACIÓN ---
function normalizar($texto) {
    $buscar =  ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ'];
    $reemplazar = ['a','e','i','o','u','A','E','I','O','U','n','N'];
    return strtoupper(trim(str_replace($buscar, $reemplazar, $texto)));
}

function getCorrelativo($ticketId) {
    if (!$ticketId || $ticketId === '---') {
        return '---';
    }
    $parts = explode('-', $ticketId);
    return end($parts);
}

$config_file = 'data/config.json';
$config = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : ['areas' => []];
$data = getData(); 

// Obtener áreas seleccionadas
$areas_seleccionadas = isset($_GET['areas']) ? $_GET['areas'] : $config['areas'];
$areas_sel_norm = array_map('normalizar', $areas_seleccionadas);

$stats = [];
// Inicializar solo las áreas que están en la configuración
foreach($config['areas'] as $a) {
    $norm_a = normalizar($a);
    $stats[$norm_a] = [
        'nombre_original' => $a,
        'en_espera' => 0,
        'atendidos' => 0,
        'llamado_actual' => '---',
        'suma_tiempo' => 0
    ];
}

// PROCESAMIENTO DE TICKETS
if (isset($data['tickets']) && is_array($data['tickets'])) {
    foreach($data['tickets'] as $t) {
        $area_ticket_norm = normalizar($t['tramite'] ?? '');
        
        if(!isset($stats[$area_ticket_norm])) continue;

        // Estado en fila
        if($t['estado'] === 'esperando') {
            $stats[$area_ticket_norm]['en_espera']++;
        }
        
        // Estado de llamado/atención activo
        if($t['estado'] === 'atendiendo' || $t['estado'] === 'llamado') {
            $stats[$area_ticket_norm]['llamado_actual'] = $t['id'];
        }

        // Estado finalizado / atendido
        if($t['estado'] === 'finalizado' || $t['estado'] === 'atendido') {
            $stats[$area_ticket_norm]['atendidos']++;
            if(!empty($t['hora_atencion']) && !empty($t['hora_finalizado'])) {
                $inicio = strtotime($t['hora_atencion']);
                $fin = strtotime($t['hora_finalizado']);
                if ($fin > $inicio) { 
                    $stats[$area_ticket_norm]['suma_tiempo'] += ($fin - $inicio) / 60; 
                }
            }
        }
    }
}

// Filtrar las estadísticas para mostrar solo las seleccionadas
$stats_finales = array_filter($stats, function($key) use ($areas_sel_norm) {
    return in_array($key, $areas_sel_norm);
}, ARRAY_FILTER_USE_KEY);

// Calcular columnas dinámicas (máximo 4)
$total_visibles = count($stats_finales);
$columnas = $total_visibles > 0 ? min($total_visibles, 4) : 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Monitor Municipal</title>
    <style>
        :root {
            --page-bg: #0a192f;
            --text-color: #ffffff;
            --panel-bg: rgba(255,255,255,0.05);
            --card-bg: #112240;
            --card-border: #233554;
            --subtle-text: #8892b0;
            --button-bg: #2c9eb5;
            --button-color: #ffffff;
            --button-border: rgba(255,255,255,0.25);
            --area-item-bg: rgba(255,255,255,0.08);
            --stats-row-bg: rgba(255,255,255,0.08);
            --current-number-color: #ffffff;
            --neon-green: #39ff14;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--page-bg);
            color: var(--text-color);
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            height: 100vh;
            box-sizing: border-box;
        }

        body.light-theme {
            --page-bg: #f5f7fb;
            --text-color: #0f172a;
            --panel-bg: rgba(255,255,255,0.95);
            --card-bg: #ffffff;
            --card-border: #d1d5db;
            --subtle-text: #475569;
            --button-bg: #4781ff;
            --button-color: #0a192f;
            --button-border: rgba(0,0,0,0.2);
            --area-item-bg: #e2e8f0;
            --stats-row-bg: rgba(15,23,42,0.08);
            --current-number-color: #0f172a;
        }

        .selector-panel {
            background: var(--panel-bg);
            padding: 10px; border-radius: 12px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
            border: 1px solid var(--button-border);
        }
        .area-item { background: var(--area-item-bg); padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; display: flex; align-items: center; gap: 5px; }
        .btn-update {
            background: var(--button-bg);
            color: var(--button-color);
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-theme {
            background: transparent;
            color: var(--button-color);
            border: 1px solid var(--button-border);
        }

        .selector-panel input[type="checkbox"] {
            accent-color: #64ffda;
        }

        .attended-num {
            color: #4fff34;
        }

        body.light-theme .attended-num {
            color: #000000;
        }

        /* GRID DINÁMICO */
        .grid { 
            display: grid; 
            grid-template-columns: repeat(<?= $columnas ?>, 1fr); 
            gap: 20px; 
            flex-grow: 1;
            align-items: stretch;
        }

        .card { 
            background: var(--card-bg);
			max-height: 500px;
            border-radius: 25px; 
            padding: 30px; 
            text-align: center; 
            border: 2px solid var(--card-border); 
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            transition: transform 0.3s;
        }

        .area-title { 
            color: var(--button-bg); 
            font-size: clamp(1.5rem, 4vw, 2.5rem); 
            font-weight: bold; 
            text-transform: uppercase;
        }

        .number-box { margin: 20px 0; }
        .current-number { 
            font-size: clamp(4rem, 10vw, 8rem); 
            font-weight: 900; 
            color: var(--current-number-color); 
            line-height: 1;
        }
        
        /* EFECTO VIBRANTE */
        .vibrant {
            color: var(--neon-green) !important;
            text-shadow: 0 0 10px var(--neon-green);
            animation: vibrate 0.2s infinite, glow 1.5s infinite alternate;
            display: inline-block;
        }

        @keyframes vibrate {
            0% { transform: translate(0); }
            25% { transform: translate(2px, -2px); }
            50% { transform: translate(-2px, 2px); }
            100% { transform: translate(0); }
        }
        @keyframes glow {
            from { opacity: 0.8; transform: scale(1); }
            to { opacity: 1; transform: scale(1.05); }
        }

        .stats-row { 
            display: flex; 
            justify-content: space-around; 
            background: var(--stats-row-bg); 
            border-radius: 15px; 
            padding: 20px;
        }
        .stat-group .lab { font-size: 1rem; color: var(--subtle-text); display: block; margin-bottom: 5px; }
        .stat-group .num { font-size: 2.5rem; font-weight: bold; }

        @media print { .selector-panel { display: none; } }
    </style>
</head>
<body>

    <div class="selector-panel">
        <form method="GET" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <strong>Áreas:</strong>
            <?php foreach($config['areas'] as $a): ?>
                <label class="area-item">
                    <input type="checkbox" name="areas[]" value="<?= $a ?>" <?= in_array(normalizar($a), $areas_sel_norm) ? 'checked' : '' ?>>
                    <?= $a ?>
                </label>
            <?php endforeach; ?>
            <button type="submit" class="btn-update">ACTUALIZAR MONITOR</button>
            <button type="button" id="themeToggleBtn" class="btn-update btn-theme" onclick="toggleTheme()">☀️Modo Claro</button>
        </form>
    </div>

    <div class="grid">
        <?php foreach($stats_finales as $norm_key => $s): 
            $isCalling = ($s['llamado_actual'] !== '---');
        ?>
            <div class="card" data-area="<?= $norm_key ?>">
				<div class="area-title"><?= $s['nombre_original'] ?></div>

				<div class="number-box">
					<div class="current-number" id="num-<?= $norm_key ?>">
						<?= htmlspecialchars(getCorrelativo($s['llamado_actual'])) ?>
					</div>
				</div>

				<div class="stats-row">
					<div class="stat-group">
						<span class="lab">EN FILA</span>
					<span class="num" id="fila-<?= $norm_key ?>" style="color: #ff5252;"><?= $s['en_espera'] ?></span>
					</div>
					<div class="stat-group">
						<span class="lab">ATENDIDOS</span>
					<span class="num attended-num" id="atendidos-<?= $norm_key ?>"><?= $s['atendidos'] ?></span>
					</div>
				</div>
			</div>
        <?php endforeach; ?>
    </div>

    <script>
        // Recarga automática cada 3 segundos para detectar cambios de turno
        //setTimeout(function(){
        //    location.reload();
        //}, 3000);
		
		async function actualizarMonitor() {
			try {
				const response = await fetch('api/get_stats.php');
				const data = await response.json();

				// Recorremos cada tarjeta presente en el monitor
				document.querySelectorAll('.card').forEach(card => {
					const areaKey = card.getAttribute('data-area');
					const info = data[areaKey];

					if (info) {
						const elNum = document.getElementById(`num-${areaKey}`);
						const elFila = document.getElementById(`fila-${areaKey}`);
						const elAtendidos = document.getElementById(`atendidos-${areaKey}`);

						// 1. Actualizar números básicos
						elFila.innerText = info.espera;
						elAtendidos.innerText = info.atendidos;

						const callNumber = parseCorrelativo(info.llamado_actual);
					// 2. Lógica del número vibrante
						if (info.llamado_actual !== '---') {
							if (elNum.innerText !== callNumber) {
								// Si el número cambió, podrías disparar un sonido aquí
								console.log("Nuevo turno en " + areaKey);
							}
							elNum.innerText = callNumber;
							elNum.classList.add('vibrant');
						} else {
							elNum.innerText = '---';
							elNum.classList.remove('vibrant');
						}
					}
				});
			} catch (error) {
				console.error("Error al observar datos:", error);
			}
		}

		function parseCorrelativo(ticketId) {
			if (!ticketId || ticketId === '---') return '---';
			const parts = ticketId.split('-');
			return parts.length ? parts[parts.length - 1] : ticketId;
		}

		function applyTheme(theme) {
			const isLight = theme === 'light';
			document.body.classList.toggle('light-theme', isLight);
			const toggleButton = document.getElementById('themeToggleBtn');
			if (toggleButton) {
				toggleButton.textContent = isLight ? '🌒Modo Oscuro' : '🌒Modo Claro';
			}
			localStorage.setItem('monitor_theme', theme);
		}

		function toggleTheme() {
			const currentTheme = document.body.classList.contains('light-theme') ? 'light' : 'dark';
			applyTheme(currentTheme === 'light' ? 'dark' : 'light');
		}

		const savedTheme = localStorage.getItem('monitor_theme') || 'dark';
		applyTheme(savedTheme);

		// Ejecutar cada 1 segundos sin recargar página
		setInterval(actualizarMonitor, 1000);
		actualizarMonitor(); // Carga inicial
    </script>
</body>
</html>