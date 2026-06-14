<?php
date_default_timezone_set('America/Santiago');
require_once 'api/db.php';
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }

$config_path = 'data/config.json';
$config = file_exists($config_path) ? json_decode(file_get_contents($config_path), true) : [];
if (!is_array($config)) $config = [];
if (!isset($config['areas']) || !is_array($config['areas'])) $config['areas'] = [];
if (!isset($config['users']) || !is_array($config['users'])) $config['users'] = [];

$data = getData();
$stats_by_area = [];
$overall_rating_sum = 0;
$overall_rating_count = 0;
$area_details = [];
foreach ($config['areas'] as $area) {
    $stats_by_area[$area] = [
        'atendidos' => 0,
        'espera' => 0,
        'finalizado' => 0,
        'tiempo_total' => 0,
        'promedio' => 0,
        'suma_calificacion' => 0,
        'calificaciones' => 0,
        'rating_avg' => 0
    ];
}

$staff_stats = [];
foreach ($config['users'] as $user) {
    $staff_stats[$user['user']] = [
        'user' => $user['user'],
        'nombre' => $user['nombre'],
        'area' => $user['area'],
        'atendidos' => 0,
        'atendiendo' => 0
    ];
}

$today_prefix = date('dm');
$day_tickets = [];
foreach ($data['tickets'] as $ticket) {
    $ticket_area = $ticket['tramite'] ?? 'Sin área';
    if (!isset($stats_by_area[$ticket_area])) {
        $stats_by_area[$ticket_area] = ['atendidos' => 0, 'espera' => 0, 'finalizado' => 0, 'tiempo_total' => 0, 'promedio' => 0];
    }

    if ($ticket['estado'] === 'esperando') {
        $stats_by_area[$ticket_area]['espera']++;
    }

    if ($ticket['estado'] === 'atendido' || $ticket['estado'] === 'finalizado') {
        $stats_by_area[$ticket_area]['atendidos']++;
        if (!empty($ticket['hora_atencion']) && !empty($ticket['hora_salida'])) {
            $inicio = strtotime($ticket['hora_atencion']);
            $fin = strtotime($ticket['hora_salida']);
            if ($inicio && $fin && $fin > $inicio) {
                $stats_by_area[$ticket_area]['tiempo_total'] += ($fin - $inicio) / 60;
                $stats_by_area[$ticket_area]['finalizado']++;
            }
        }
    }

    if (isset($ticket['calificacion']) && $ticket['calificacion'] !== '' && is_numeric($ticket['calificacion'])) {
        $stats_by_area[$ticket_area]['suma_calificacion'] += floatval($ticket['calificacion']);
        $stats_by_area[$ticket_area]['calificaciones']++;
        $overall_rating_sum += floatval($ticket['calificacion']);
        $overall_rating_count++;
    }

    if (!isset($area_details[$ticket_area])) {
        $area_details[$ticket_area] = [];
    }
    $area_details[$ticket_area][] = [
        'id' => $ticket['id'] ?? '',
        'nombre' => $ticket['nombre'] ?? '',
        'tramite' => $ticket['tramite'] ?? '',
        'estado' => $ticket['estado'] ?? '',
        'creado' => $ticket['creado'] ?? '',
        'hora_atencion' => $ticket['hora_atencion'] ?? '-',
        'hora_salida' => $ticket['hora_salida'] ?? '-',
        'atendido_por' => $ticket['atendido_por'] ?? '-',
        'calificacion' => $ticket['calificacion'] ?? '-',
        'comentario_ciudadano' => $ticket['comentario_ciudadano'] ?? '-',
        'observaciones' => $ticket['observaciones'] ?? '-'
    ];

    if (!empty($ticket['atendido_por']) && isset($staff_stats[$ticket['atendido_por']])) {
        if ($ticket['estado'] === 'atendido' || $ticket['estado'] === 'finalizado') {
            $staff_stats[$ticket['atendido_por']]['atendidos']++;
        }
        if ($ticket['estado'] === 'atendiendo') {
            $staff_stats[$ticket['atendido_por']]['atendiendo']++;
        }
    }

    if (strpos($ticket['id'] ?? '', $today_prefix . '-') === 0) {
        $day_tickets[] = $ticket;
    }
}

foreach ($stats_by_area as $area_name => &$stats) {
    $stats['promedio'] = $stats['finalizado'] > 0 ? round($stats['tiempo_total'] / $stats['finalizado'], 1) : 0;
    $stats['rating_avg'] = $stats['calificaciones'] > 0 ? round($stats['suma_calificacion'] / $stats['calificaciones'], 1) : 0;
}
unset($stats);

$overall_rating_avg = $overall_rating_count > 0 ? round($overall_rating_sum / $overall_rating_count, 1) : 0;
$staff_stats = array_values($staff_stats);

$log_path = 'data/audit_log.json';
$logs = [];
if (file_exists($log_path)) {
    $logs = json_decode(file_get_contents($log_path), true);
    if (!is_array($logs)) {
        $logs = [];
    }
}
usort($logs, function ($a, $b) {
    return strcmp($b['fecha'] ?? '', $a['fecha'] ?? '');
});

$allowed_tabs = ['resumen', 'staff', 'areas', 'display', 'log', 'reportes'];
$active_tab = $_GET['tab'] ?? 'resumen';
if (!in_array($active_tab, $allowed_tabs)) {
    $active_tab = 'resumen';
}

$message = '';
$message_type = 'success';
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'area_added':
            $message = 'Área creada correctamente.';
            break;
        case 'area_deleted':
            $message = 'Área eliminada correctamente.';
            break;
        case 'area_exists':
            $message = 'Esa área ya existe.';
            $message_type = 'error';
            break;
        default:
            $message = 'Acción completada.';
            break;
    }
}

$nombre_institucion = $config['nombre_institucion'] ?? 'Sistema de Filas';
$color_header = $config['color_header'] ?? '#2563eb';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { margin: 0; }
        .header { background: linear-gradient(135deg, <?= $color_header ?> 0%, rgba(37, 99, 235, 0.85) 100%); color: white; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.15); }
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; letter-spacing: 0.5px; color: #eee; }
        .header p { margin: 8px 0 0; font-size: 0.95rem; opacity: 0.95; }
        .tabs { display: flex; background: #fff; border-bottom: 2px solid #ddd; align-items: center; }
        .tab-btn { padding: 15px 25px; border: none; background: none; cursor: pointer; font-weight: bold; color: #666; }
        .tab-btn.active { color: <?= $color_header ?>; border-bottom: 3px solid <?= $color_header ?>; }
        .content { padding: 20px; display: none; }
        .content.active { display: block; }
        .grid-areas { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 15px; margin-top: 20px; }
        .area-card { background: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); display: flex; flex-direction: column; justify-content: space-between; gap: 12px; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
        .summary-card { background: white; padding: 18px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .summary-card h4 { margin: 0 0 10px; font-size: 1rem; color: #444; }
        .summary-card .value { font-size: 2rem; font-weight: bold; color: <?= $color_header ?>; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; }
        .data-table th, .data-table td { border: 1px solid #e5e7eb; padding: 12px 14px; text-align: left; }
        .data-table th { background: #f8fafc; color: #0f172a; }
        .data-table tbody tr:nth-child(even) { background: #f8fafc; }
        .action-btn { padding: 10px 14px; background: <?= $color_header ?>; color: white; border: none; border-radius: 8px; cursor: pointer; }
        .action-btn.small { padding: 8px 10px; font-size: 0.9rem; }
        .modal { position: fixed; inset: 0; display: none; align-items: center; justify-content: center; background: rgba(15,23,42,0.55); z-index: 2000; }
        .modal.active { display: flex; }
        .modal-content { width: min(95%, 760px); max-height: 85vh; overflow-y: auto; background: white; border-radius: 16px; padding: 25px; position: relative; }
        .modal-content h4 { margin-top: 0; }
        .modal-close { position: absolute; right: 18px; top: 18px; cursor: pointer; font-size: 1.5rem; color: #334155; }
        .flash { padding: 14px 18px; border-radius: 10px; margin: 20px 0; }
        .flash.success { background: #ecfdf5; color: #166534; border-left: 4px solid #22c55e; }
        .flash.error { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }
        .table-scroll { overflow-x: auto; }
        .rating-stars { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; }
        .rating-star { color: #fbbf24; font-size: 1rem; }
        .rating-star.empty { color: #e2e8f0; }
        .sort-controls { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 15px; }
        .sort-controls input { max-width: 320px; }
    </style>
</head>
<body>

<div class="header">
    <h1><?= htmlspecialchars($nombre_institucion) ?></h1>
    <p>Panel de Administración</p>
</div>

<div class="tabs" style="justify-content: space-between;">
    <div style="display: flex; flex-wrap: wrap; gap: 0;">
        <button class="tab-btn" onclick="tab(event, 'resumen')">Resumen</button>
        <button class="tab-btn" onclick="tab(event, 'staff')">Ejecutivos</button>
        <button class="tab-btn" onclick="tab(event, 'areas')">Áreas</button>
        <button class="tab-btn" onclick="tab(event, 'display')">Pantalla de Filas</button>
        <button class="tab-btn" onclick="tab(event, 'log')">Log Eventos</button>
        <button class="tab-btn" onclick="tab(event, 'reportes')">Reportes</button>
    </div>
    <button class="tab-btn" style="color:red;" onclick="location.href='api/auth.php?action=logout'">Cerrar Sesión</button>
</div>
<?php if ($message): ?>
<div class="flash <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div id="areas" class="content">
    <h3>Gestión de Áreas Atendidas</h3>
    <form action="api/setup.php?action=add_area" method="POST" style="margin-bottom:20px;">
        <input type="text" name="new_area" placeholder="Nombre de la nueva área..." required style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd; margin-bottom:10px;">
        <button type="submit" class="btn-primary" style="width:100%;">Agregar Área</button>
    </form>
    <div class="grid-areas">
        <?php foreach($config['areas'] as $a): ?>
        <div class="area-card">
            <div>
                <strong><?= htmlspecialchars($a) ?></strong><br>
                En fila: <?= htmlspecialchars($stats_by_area[$a]['espera'] ?? 0) ?><br>
                Atendidos: <?= htmlspecialchars($stats_by_area[$a]['atendidos'] ?? 0) ?><br>
                Promedio: <?= htmlspecialchars($stats_by_area[$a]['promedio'] ?? 0) ?> min
            </div>
            <div style="display:flex; flex-direction: column; gap:8px; align-items:flex-end;">
                <button class="action-btn small" onclick="showAreaStaff('<?= htmlspecialchars(addslashes($a)) ?>')">Ver Staff</button>
                <a href="api/setup.php?action=del_area&name=<?= urlencode($a) ?>&tab=areas" style="color:red; text-decoration:none;">Eliminar</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="resumen" class="content">
    <h3>Resumen del Sistema</h3>
    <div class="summary-grid">
        <div class="summary-card">
            <h4>Áreas</h4>
            <div class="value"><?= count($config['areas']) ?></div>
        </div>
        <div class="summary-card">
            <h4>Ejecutivos</h4>
            <div class="value"><?= count($config['users']) ?></div>
        </div>
        <div class="summary-card">
            <h4>En Fila</h4>
            <div class="value"><?php $espera_total = array_sum(array_column($stats_by_area, 'espera')); echo $espera_total; ?></div>
        </div>
        <div class="summary-card">
            <h4>Atendidos Hoy</h4>
            <div class="value"><?php $atendidos_hoy = count(array_filter($day_tickets, function($t) { return $t['estado'] === 'atendido' || $t['estado'] === 'finalizado'; })); echo $atendidos_hoy; ?></div>
        </div>
        <div class="summary-card">
            <h4>⭐ Promedio General</h4>
            <div class="value"><?= $overall_rating_avg ?: '-' ?></div>
        </div>
    </div>

    <h4 style="margin-top:25px;">Indicadores por área</h4>
    <div class="sort-controls">
        <label style="display:flex; align-items:center; gap:10px;">
            Buscar por área:
            <input id="area-search" type="text" placeholder="Ingresar nombre de área..." oninput="renderAreaRows()" style="padding:10px; border-radius:10px; border:1px solid #ddd; width:280px;">
        </label>
        <button class="action-btn small" id="toggle-sort-btn" onclick="toggleAreaSort()">Ordenar calificación ↑</button>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Área</th>
                    <th>En fila</th>
                    <th>Atendidos</th>
                    <th>Tiempo promedio (min)</th>
                    <th>Calificación promedio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="area-table-body">
            </tbody>
        </table>
    </div>

</div>

<div id="staff" class="content">
    <h3>Gestión de Ejecutivos</h3>
    <div id="message" style="display:none; padding:15px; margin-bottom:15px; border-radius:8px; border-left:4px solid #2563eb;"></div>
    <form id="add-staff-form" onsubmit="submitStaffForm(event, 'add')" style="background:white; padding:20px; border-radius:10px; margin-bottom:20px;">
        <input type="hidden" name="action" value="add">
        <div style="display:flex; gap:10px; margin-bottom:10px;">
            <input type="text" name="user" placeholder="Usuario" required style="flex:1; padding:10px;">
            <input type="text" name="nombre" placeholder="Nombre" required style="flex:1; padding:10px;">
            <select name="area" required style="flex:1; padding:10px;">
                <option value="">Seleccionar área...</option>
                <?php foreach($config['areas'] as $a): ?>
                <option value="<?= htmlspecialchars($a) ?>"><?= htmlspecialchars($a) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="password" name="pass" placeholder="Contraseña" required style="flex:1; padding:10px;">
        </div>
        <button type="submit" class="btn-primary">Agregar Ejecutivo</button>
    </form>
    <form id="edit-form" onsubmit="submitStaffForm(event, 'edit')" style="background:#f0f0f0; padding:20px; border-radius:10px; margin-bottom:20px; display:none;">
        <input type="hidden" name="action" value="edit">
        <h4>Editar Ejecutivo</h4>
        <div style="display:flex; gap:10px; margin-bottom:10px;">
            <input type="text" id="edit-user" name="user" readonly style="flex:1; padding:10px; background:#e0e0e0;">
            <input type="text" id="edit-nombre" name="nombre" placeholder="Nombre" required style="flex:1; padding:10px;">
            <select id="edit-area" name="area" required style="flex:1; padding:10px;">
                <?php foreach($config['areas'] as $a): ?>
                <option value="<?= htmlspecialchars($a) ?>"><?= htmlspecialchars($a) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="password" id="edit-pass" name="pass" placeholder="Nueva Contraseña (opcional)" style="flex:1; padding:10px;">
        </div>
        <button type="submit" class="btn-primary">Guardar Cambios</button>
        <button type="button" onclick="cancelEdit()" style="margin-left:10px;">Cancelar</button>
    </form>
    <div class="grid-areas" id="staff-list">
        <?php if(isset($config['users'])): foreach($config['users'] as $s): ?>
        <div class="area-card" data-user="<?= htmlspecialchars($s['user']) ?>">
            <div>
                <strong><?= htmlspecialchars($s['nombre']) ?> (<?= htmlspecialchars($s['user']) ?>)</strong><br>
                Área: <?= htmlspecialchars($s['area']) ?>
            </div>
            <div>
                <a href="#" onclick="editStaff(event, '<?= htmlspecialchars($s['user']) ?>', '<?= htmlspecialchars($s['nombre']) ?>', '<?= htmlspecialchars($s['area']) ?>')" style="color:blue; text-decoration:none; margin-right:10px;">Editar</a>
                <a href="#" onclick="deleteStaff(event, '<?= htmlspecialchars($s['user']) ?>')" style="color:red; text-decoration:none; border:none; background:none;">✕</a>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<div id="log" class="content">
    <h3>Registro de Eventos</h3>
    <div class="table-scroll" style="max-height:420px; overflow:auto; background:white; padding:20px; border-radius:12px;">
        <?php if (!empty($logs)): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Usuario</th>
                    <th>IP</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log):
                    $partes = explode(' ', $log['fecha'] ?? '', 2);
                    $fecha = $partes[0] ?? '';
                    $hora = $partes[1] ?? '';
                ?>
                <tr>
                    <td><?= htmlspecialchars($fecha) ?></td>
                    <td><?= htmlspecialchars($hora) ?></td>
                    <td><?= htmlspecialchars($log['admin'] ?? '') ?></td>
                    <td><?= htmlspecialchars($log['ip'] ?? '') ?></td>
                    <td><?= htmlspecialchars($log['accion'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No hay registros de eventos.</p>
        <?php endif; ?>
    </div>
</div>

<div id="reportes" class="content">
    <h3>Exportar Históricos</h3>
    <div style="margin-bottom:20px; display:flex; gap:10px; flex-wrap:wrap;">
        <button class="action-btn" onclick="toggleReporteForm('por-dia')">Por Día Específico</button>
        <button class="action-btn" onclick="toggleReporteForm('por-rango')">Por Rango de Fechas</button>
    </div>
    
    <form id="form-por-dia" action="api/export_excel.php" method="GET" style="background:white; padding:25px; border-radius:12px; display:flex; gap:15px; align-items:flex-end; border:1px solid #eee; flex-wrap:wrap;">
        <input type="hidden" name="tipo" value="personalizado">
        <div><label>Día</label><br><select name="d"><?php for($i=1;$i<=31;$i++) echo "<option value='".str_pad($i,2,'0',STR_PAD_LEFT)."'>$i</option>"; ?></select></div>
        <div><label>Mes</label><br><select name="m"><?php $m=["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"]; foreach($m as $k=>$v) echo "<option value='".str_pad($k+1,2,'0',STR_PAD_LEFT)."'>$v</option>"; ?></select></div>
        <div><label>Año</label><br><select name="y"><option value="2026">2026</option><option value="2025">2025</option></select></div>
        <button type="submit" class="btn-primary">Descargar Reporte .CSV</button>
    </form>
    
    <form id="form-por-rango" action="api/export_excel.php" method="GET" style="background:white; padding:25px; border-radius:12px; display:none; gap:15px; align-items:flex-end; border:1px solid #eee; flex-wrap:wrap;">
        <input type="hidden" name="tipo" value="rango">
        <div><label>Desde</label><br><input type="date" name="fecha_desde" required style="padding:10px; border-radius:5px; border:1px solid #ddd;"></div>
        <div><label>Hasta</label><br><input type="date" name="fecha_hasta" required style="padding:10px; border-radius:5px; border:1px solid #ddd;"></div>
        <button type="submit" class="btn-primary">Descargar Reporte .CSV</button>
    </form>

    <h4 style="margin-top:25px;">Datos del día</h4>
    <div class="table-scroll" style="background:white; padding:15px; border-radius:12px;">
        <?php if (!empty($day_tickets)): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Área</th>
                    <th>Estado</th>
                    <th>Hora creación</th>
                    <th>Hora atención</th>
                    <th>Hora salida</th>
                    <th>Atendido por</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($day_tickets as $ticket): ?>
                <tr>
                    <td><?= htmlspecialchars($ticket['id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ticket['nombre'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ticket['tramite'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ticket['estado'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ticket['creado'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ticket['hora_atencion'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($ticket['hora_salida'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($ticket['atendido_por'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No hay datos del día disponibles.</p>
        <?php endif; ?>
    </div>
</div>

<div id="display" class="content">
    <h3>Gestión de Credenciales - Pantalla de Filas</h3>
    <div id="message-display" style="display:none; padding:15px; margin-bottom:15px; border-radius:8px; border-left:4px solid #2563eb;"></div>
    
    <form id="display-form" onsubmit="submitDisplayForm(event)" style="background:white; padding:20px; border-radius:10px; margin-bottom:20px; max-width:600px;">
        <div style="margin-bottom:20px;">
            <label style="display:block; font-weight:600; margin-bottom:8px; color:#334155;">Usuario actual:</label>
            <div style="padding:10px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0;">
                <strong><?= htmlspecialchars($config['display_user'] ?? 'display') ?></strong>
            </div>
        </div>
        
        <div style="margin-bottom:15px;">
            <label for="display_user" style="display:block; font-weight:600; margin-bottom:8px; color:#334155;">Nuevo usuario:</label>
            <input type="text" id="display_user" name="display_user" value="<?= htmlspecialchars($config['display_user'] ?? 'display') ?>" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
        </div>
        
        <div style="margin-bottom:15px;">
            <label for="display_pass" style="display:block; font-weight:600; margin-bottom:8px; color:#334155;">Nueva contraseña:</label>
            <input type="password" id="display_pass" name="display_pass" placeholder="Ingrese una nueva contraseña" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
        </div>
        
        <div style="margin-bottom:20px;">
            <label for="confirm_pass" style="display:block; font-weight:600; margin-bottom:8px; color:#334155;">Confirmar contraseña:</label>
            <input type="password" id="confirm_pass" name="confirm_pass" placeholder="Confirme la contraseña" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
        </div>
        
        <button type="submit" class="action-btn">Actualizar Credenciales</button>
    </form>

    <div style="background:#f8fafc; padding:20px; border-radius:10px; border-left:4px solid #2563eb;">
        <h4 style="margin-top:0; color:#334155;">Instrucciones:</h4>
        <ul style="margin:10px 0; padding-left:20px;">
            <li>El usuario y contraseña son requeridos para acceder a la pantalla de filas (tótem).</li>
            <li>Ingrese un nuevo usuario y contraseña en los campos correspondientes.</li>
            <li>La contraseña debe confirmarse en el campo de confirmación.</li>
            <li>Haga clic en "Actualizar Credenciales" para guardar los cambios.</li>
        </ul>
    </div>
</div>

<div id="staff-modal" class="modal" onclick="hideModal(event)">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal('staff-modal')">&times;</span>
        <h4>Ejecutivos en área</h4>
        <div id="modal-staff-list"></div>
    </div>
</div>

<div id="details-modal" class="modal" onclick="hideModal(event)">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal('details-modal')">&times;</span>
        <h4>Detalle de atenciones</h4>
        <div id="modal-detail-list"></div>
    </div>
</div>

<script>
    let staffData = <?= json_encode($config['users'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    let staffStats = <?= json_encode($staff_stats, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const areaStats = <?= json_encode($stats_by_area, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const areaDetails = <?= json_encode($area_details, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const initialTab = '<?= htmlspecialchars($active_tab) ?>';

    function tab(event, id) {
        if (!id) return;
        localStorage.setItem('admin_active_tab', id);
        document.querySelectorAll('.content').forEach(c => c.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        const current = document.getElementById(id);
        if (current) {
            current.classList.add('active');
        }
        if (event && event.currentTarget) {
            event.currentTarget.classList.add('active');
        } else {
            const btn = Array.from(document.querySelectorAll('.tab-btn')).find(b => b.textContent.trim().toLowerCase() === id.toLowerCase());
            if (btn) btn.classList.add('active');
        }
    }

    function showMessage(message, success = true) {
        const msgEl = document.getElementById('message');
        msgEl.textContent = message;
        msgEl.style.display = 'block';
        msgEl.style.borderLeftColor = success ? '#22c55e' : '#ef4444';
        msgEl.style.backgroundColor = success ? '#f0fdf4' : '#fef2f2';
        msgEl.style.color = success ? '#166534' : '#991b1b';
        setTimeout(() => msgEl.style.display = 'none', 5000);
    }

    function submitStaffForm(e, action) {
        e.preventDefault();
        const form = action === 'add' ? document.getElementById('add-staff-form') : document.getElementById('edit-form');
        const formData = new FormData(form);
        
        fetch('api/manage_staff.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, true);
                syncStaffArrays(data.users, action, formData.get('user'));
                updateStaffList(data.users);
                form.reset();
                if (action === 'edit') cancelEdit();
            } else {
                showMessage(data.message, false);
            }
        })
        .catch(error => {
            showMessage('Error: ' + error.message, false);
        });
    }

    function updateStaffList(users) {
        const listEl = document.getElementById('staff-list');
        listEl.innerHTML = '';
        users.forEach(user => {
            const card = document.createElement('div');
            card.className = 'area-card';
            card.setAttribute('data-user', user.user);
            card.innerHTML = `
                <div>
                    <strong>${escapeHtml(user.nombre)} (${escapeHtml(user.user)})</strong><br>
                    Área: ${escapeHtml(user.area)}
                </div>
                <div style="display:flex; gap:10px; flex-wrap: wrap; align-items:center;">
                    <button class="action-btn small" onclick="editStaff(event, '${escapeHtml(user.user)}', '${escapeHtml(user.nombre)}', '${escapeHtml(user.area)}')">Editar</button>
                    <button class="action-btn small" style="background:#ef4444;" onclick="deleteStaff(event, '${escapeHtml(user.user)}')">Eliminar</button>
                </div>
            `;
            listEl.appendChild(card);
        });
    }

    function syncStaffArrays(users, action, userId) {
        staffData = users;

        if (action === 'add') {
            const existingUsers = new Set(staffStats.map(item => item.user));
            users.forEach(user => {
                if (!existingUsers.has(user.user)) {
                    staffStats.push({
                        user: user.user,
                        nombre: user.nombre,
                        area: user.area,
                        atendidos: 0,
                        atendiendo: 0
                    });
                }
            });
        }

        if (action === 'edit') {
            const updated = users.find(item => item.user === userId);
            if (updated) {
                staffStats.forEach(item => {
                    if (item.user === userId) {
                        item.nombre = updated.nombre;
                        item.area = updated.area;
                    }
                });
            }
        }

        if (action === 'delete') {
            staffStats = staffStats.filter(item => item.user !== userId);
        }
    }

    function editStaff(e, user, nombre, area) {
        e.preventDefault();
        document.getElementById('edit-user').value = user;
        document.getElementById('edit-nombre').value = nombre;
        document.getElementById('edit-area').value = area;
        document.getElementById('edit-pass').value = '';
        document.getElementById('edit-form').style.display = 'block';
    }

    function deleteStaff(e, user) {
        e.preventDefault();
        if (confirm('¿Estás seguro de que deseas eliminar este ejecutivo?')) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('user', user);
            
            fetch('api/manage_staff.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, true);
                    updateStaffList(data.users);
                } else {
                    showMessage(data.message, false);
                }
            })
            .catch(error => {
                showMessage('Error: ' + error.message, false);
            });
        }
    }

    function cancelEdit() {
        document.getElementById('edit-form').style.display = 'none';
    }

    function submitDisplayForm(e) {
        e.preventDefault();
        const form = document.getElementById('display-form');
        const user = document.getElementById('display_user').value;
        const pass = document.getElementById('display_pass').value;
        const confirm_pass = document.getElementById('confirm_pass').value;
        
        if (pass !== confirm_pass) {
            showMessageDisplay('Las contraseñas no coinciden', false);
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('display_user', user);
        formData.append('display_pass', pass);
        formData.append('confirm_pass', confirm_pass);
        
        fetch('api/manage_display.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessageDisplay(data.message, true);
                document.getElementById('display_user').value = data.display_user;
                document.getElementById('display_pass').value = '';
                document.getElementById('confirm_pass').value = '';
            } else {
                showMessageDisplay(data.message, false);
            }
        })
        .catch(error => {
            showMessageDisplay('Error: ' + error.message, false);
        });
    }

    function showMessageDisplay(message, success = true) {
        const msgEl = document.getElementById('message-display');
        msgEl.textContent = message;
        msgEl.style.display = 'block';
        msgEl.style.borderLeftColor = success ? '#22c55e' : '#ef4444';
        msgEl.style.backgroundColor = success ? '#f0fdf4' : '#fef2f2';
        msgEl.style.color = success ? '#166534' : '#991b1b';
        setTimeout(() => msgEl.style.display = 'none', 5000);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function toggleReporteForm(form) {
        document.getElementById('form-por-dia').style.display = form === 'por-dia' ? 'flex' : 'none';
        document.getElementById('form-por-rango').style.display = form === 'por-rango' ? 'flex' : 'none';
    }

    function showAreaStaff(area) {
        const filtered = staffData.filter(staff => staff.area === area);
        const listEl = document.getElementById('modal-staff-list');
        listEl.innerHTML = '';
        if (filtered.length === 0) {
            listEl.innerHTML = '<p>No hay ejecutivos asignados a esta área.</p>';
        } else {
            const totalAtendidos = areaStats[area] ? Number(areaStats[area].atendidos || 0) : 0;
            const header = document.createElement('div');
            header.style.marginBottom = '12px';
            header.innerHTML = `
                <strong>Área:</strong> ${escapeHtml(area)}<br>
                <strong>Total atendidos:</strong> ${escapeHtml(totalAtendidos.toString())}
            `;
            listEl.appendChild(header);

            const table = document.createElement('table');
            table.className = 'data-table';
            table.innerHTML = `
                <thead>
                    <tr><th>Usuario</th><th>Nombre</th><th>Área</th><th>Atendidos</th><th>Atendiendo ahora</th></tr>
                </thead>
                <tbody>${filtered.map(staff => {
                    const stats = staffStats.find(entry => entry.user === staff.user) || {atendidos: 0, atendiendo: 0};
                    return `
                    <tr>
                        <td>${escapeHtml(staff.user)}</td>
                        <td>${escapeHtml(staff.nombre)}</td>
                        <td>${escapeHtml(staff.area)}</td>
                        <td>${escapeHtml(stats.atendidos.toString())}</td>
                        <td>${escapeHtml(stats.atendiendo.toString())}</td>
                    </tr>`;
                }).join('')}</tbody>`;
            listEl.appendChild(table);
        }
        document.querySelector('#staff-modal').classList.add('active');
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    }

    function hideModal(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('active');
        }
    }

    function showAreaDetails(area) {
        const details = areaDetails[area] || [];
        const listEl = document.getElementById('modal-detail-list');
        listEl.innerHTML = '';
        if (details.length === 0) {
            listEl.innerHTML = '<p>No hay atenciones registradas para esta área.</p>';
        } else {
            const table = document.createElement('table');
            table.className = 'data-table';
            table.innerHTML = `
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ciudadano</th>
                        <th>Área</th>
                        <th>Estado</th>
                            <th>Tiempo en fila (min)</th>
                            <th>Tiempo de atención (min)</th>
                        <th>Ejecutivo</th>
                        <th>Calificación</th>
                        <th>Comentario ciudadano</th>
                        <th>Observaciones ejecutivo</th>
                    </tr>
                </thead>
                <tbody>${details.map(item => `
                    <tr>
                        <td>${escapeHtml(item.id)}</td>
                        <td>${escapeHtml(item.nombre)}</td>
                        <td>${escapeHtml(item.tramite)}</td>
                        <td>${escapeHtml(item.estado)}</td>
                            <td>${calcularTiempo(item.creado, item.hora_atencion)}</td>
                            <td>${calcularTiempo(item.hora_atencion, item.hora_salida)}</td>
                        <td>${escapeHtml(item.atendido_por)}</td>
                        <td>${item.calificacion === '-' ? '-' : escapeHtml(item.calificacion.toString())}</td>
                        <td>${escapeHtml(item.comentario_ciudadano)}</td>
                        <td>${escapeHtml(item.observaciones)}</td>
                    </tr>`).join('')}</tbody>`;
            listEl.appendChild(table);
        }
        document.querySelector('#details-modal').classList.add('active');
    }

    let areaSortAsc = true;

        function calcularTiempo(inicio, fin) {
            if (!inicio || !fin || inicio === '-' || fin === '-') return '-';
            try {
                const t1 = new Date(inicio);
                const t2 = new Date(fin);
                if (isNaN(t1.getTime()) || isNaN(t2.getTime())) return '-';
                const diff = (t2 - t1) / (1000 * 60);
                return diff >= 0 ? Math.round(diff) : '-';
            } catch (e) {
                return '-';
            }
        }

    function formatRatingStars(value) {
        if (!value || Number(value) <= 0) {
            return '<span style="color:#64748b;">-</span>';
        }
        const rating = Number(value);
        const fullStars = Math.round(rating);
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += `<span class="rating-star ${i <= fullStars ? '' : 'empty'}">★</span>`;
        }
        return `<span class="rating-stars">${stars}<span style="color:#334155; font-size:0.95rem; margin-left:8px;">${rating.toFixed(1)}</span></span>`;
    }

    function renderAreaRows() {
        const query = document.getElementById('area-search').value.toLowerCase().trim();
        const rows = Object.keys(areaStats).map(areaName => ({
            areaName,
            stats: areaStats[areaName]
        })).filter(item => item.areaName.toLowerCase().includes(query));

        rows.sort((a, b) => {
            const left = Number(a.stats.rating_avg) || 0;
            const right = Number(b.stats.rating_avg) || 0;
            return areaSortAsc ? left - right : right - left;
        });

        const tbody = document.getElementById('area-table-body');
        tbody.innerHTML = rows.map(item => `
            <tr>
                <td>${escapeHtml(item.areaName)}</td>
                <td>${escapeHtml(item.stats.espera)}</td>
                <td>${escapeHtml(item.stats.atendidos)}</td>
                <td>${escapeHtml(item.stats.promedio)}</td>
                <td>${formatRatingStars(item.stats.rating_avg)}</td>
                <td style="display:flex; gap:8px; flex-wrap:wrap;">
                    <button class="action-btn small" onclick="showAreaStaff('${escapeHtml(item.areaName)}')">Ver ejecutivos</button>
                    <button class="action-btn small" onclick="showAreaDetails('${escapeHtml(item.areaName)}')">Ver detalle</button>
                </td>
            </tr>`).join('');
    }

    function toggleAreaSort() {
        areaSortAsc = !areaSortAsc;
        const btn = document.getElementById('toggle-sort-btn');
        btn.textContent = areaSortAsc ? 'Ordenar calificación ↑' : 'Ordenar calificación ↓';
        renderAreaRows();
    }

    document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const urlTab = urlParams.get('tab');
            const storedTab = localStorage.getItem('admin_active_tab');
            let startTab = 'resumen';
            if (urlTab) {
                startTab = urlTab;
            } else if (storedTab) {
                startTab = storedTab;
            }
        tab(null, startTab);
        renderAreaRows();
    });
</script>

</body>
</html>