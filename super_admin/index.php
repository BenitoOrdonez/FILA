<?php
// master/index.php
require_once '../master_api/auth.php';
checkSuperAuth();

// Lógica de Cerrar Sesión
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

$data_global = getGlobalData();
$metricas = [];
$stats_global = ['total' => 0, 'salud' => 0, 'inactivas' => 0];

foreach ($data_global['instituciones'] as $inst) {
    $slug = $inst['slug'];
    $fecha = date("Y-m-d");
    $path = __DIR__ . "/../instancias/$slug/data/daily/queue_$fecha.json";
    
    $is_healthy = file_exists(__DIR__ . "/../instancias/$slug/index.php") && file_exists(__DIR__ . "/../instancias/$slug/data/config.json");
    if($is_healthy) $stats_global['salud']++;
    if(($inst['estado'] ?? 'activo') === 'inactivo') $stats_global['inactivas']++;

    $tickets_hoy = 0;
    if (file_exists($path)) {
        $j = json_decode(file_get_contents($path), true);
        $tickets_hoy = $j['stats']['total'] ?? 0;
        $stats_global['total'] += $tickets_hoy;
    }

    $metricas[] = [
        "nombre" => $inst['nombre'],
        "slug" => $slug,
        "tickets" => $tickets_hoy,
        "salud" => $is_healthy,
        "estado" => $inst['estado'] ?? 'activo'
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Maestro | Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4f46e5; --dark: #0f172a; --bg: #f1f5f9; --danger: #ef4444; --success: #10b981; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; display: flex; color: var(--dark); }
        
        /* Sidebar Unificado */
        .sidebar { width: 260px; background: var(--dark); color: white; height: 100vh; position: fixed; padding: 30px 20px; box-sizing: border-box; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 1.2rem; margin-bottom: 40px; color: white; display: flex; align-items: center; gap: 10px; }
        .sidebar a { color: #94a3b8; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 12px; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: white; }
        .sidebar a.active { background: var(--primary); color: white; }
        .sidebar .logout { margin-top: auto; color: #f87171; }
        .sidebar .logout:hover { background: rgba(239, 68, 68, 0.1); }

        .main { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-bottom: 4px solid #e2e8f0; }
        .stat-card h3 { margin: 0; font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-card p { margin: 15px 0 0 0; font-size: 2.5rem; font-weight: 600; }

        .list-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .inst-row { display: flex; align-items: center; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #f1f5f9; }
        .health-badge { font-size: 0.7rem; padding: 4px 10px; border-radius: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fa-solid fa-layer-group"></i> Panel Maestro</h2>
        <a href="index.php" class="active"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
        <a href="instituciones.php"><i class="fa-solid fa-building-columns"></i> Instituciones</a>
        <a href="?action=logout" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
    </div>

    <div class="main">
        <header style="margin-bottom: 40px;">
            <h1 style="margin:0;">Dashboard Global</h1>
            <p style="color: #64748b;">Resumen de actividad en toda la red de servicios.</p>
        </header>

        <div class="stats-grid">
            <div class="stat-card" style="border-bottom-color: var(--primary);">
                <h3><i class="fa-solid fa-ticket"></i> Tickets Hoy</h3>
                <p><?= number_format($stats_global['total']) ?></p>
            </div>
            <div class="stat-card" style="border-bottom-color: var(--success);">
                <h3><i class="fa-solid fa-heart-pulse"></i> Nodos Activos</h3>
                <p><?= $stats_global['salud'] ?> <span style="font-size: 1rem; color: #94a3b8;">/ <?= count($metricas) ?></span></p>
            </div>
            <div class="stat-card" style="border-bottom-color: #f59e0b;">
                <h3><i class="fa-solid fa-pause"></i> Instituciones Pausadas</h3>
                <p><?= $stats_global['inactivas'] ?></p>
            </div>
        </div>

        <div class="list-card">
            <h3>Estado de Conectividad</h3>
            <?php foreach($metricas as $m): ?>
            <div class="inst-row">
                <div>
                    <strong style="font-size: 1.1rem;"><?= $m['nombre'] ?></strong><br>
                    <span class="health-badge" style="background: <?= $m['salud'] ? '#dcfce7' : '#fee2e2' ?>; color: <?= $m['salud'] ? '#166534' : '#b91c1c' ?>;">
                        <i class="fa-solid <?= $m['salud'] ? 'fa-check-circle' : 'fa-circle-exclamation' ?>"></i>
                        <?= $m['salud'] ? 'SISTEMA ÓPTIMO' : 'ERROR DE CONFIGURACIÓN' ?>
                    </span>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 1.5rem; font-weight: 600;"><?= $m['tickets'] ?></span><br>
                    <small style="color: #64748b;">Tickets generados hoy</small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>