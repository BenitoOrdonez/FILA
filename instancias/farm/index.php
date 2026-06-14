<?php 
require_once 'api/db.php';

$config_path = getBasePath() . '/data/config.json';
$config = file_exists($config_path) ? json_decode(file_get_contents($config_path), true) : [];
$areas = $config['areas'] ?? ['General'];

// --- LÓGICA DE VALIDACIÓN (5 MINUTOS) ---
$token_recibido = $_GET['t'] ?? '';
$token_valido = false;

for ($i = 0; $i < 5; $i++) {
    $check_time = gmdate("Y-m-d\TH:i", strtotime("-$i minutes"));
    $token_check = str_replace('=', '', base64_encode($check_time));
    if ($token_recibido === $token_check) {
        $token_valido = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['nombre_institucion'] ?? 'Turnos') ?></title>
    <style>
        body { font-family: sans-serif; background: #f1f5f9; display: flex; justify-content: center; padding: 20px; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h1 { font-size: 1.4rem; color: #1e293b; text-align: center; margin-bottom: 25px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #475569; font-size: 0.9rem; }
        /* Estética unificada */
        .u-input { 
            width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; 
            font-size: 1rem; box-sizing: border-box; 
        }
        .btn { 
            width: 100%; padding: 15px; background: #2563eb; color: white; border: none; 
            border-radius: 8px; font-size: 1.1rem; font-weight: bold; cursor: pointer; margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="card">
        <?php if (!$token_valido): ?>
            <div style="text-align:center; color:#ef4444;">
                <h2>QR Expirado</h2>
                <p>Por favor, escanee el código nuevamente.</p>
            </div>
        <?php else: ?>
            <h1><?= htmlspecialchars($config['nombre_institucion'] ?? 'Solicitar Turno') ?></h1>
            <form action="api/create_ticket.php" method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token_recibido) ?>">
                
                <div class="form-group">
                    <label>Departamento:</label>
                    <select name="tramite" class="u-input" required>
                        <?php foreach ($areas as $area): ?>
                            <option value="<?= htmlspecialchars($area) ?>"><?= htmlspecialchars($area) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nombre Completo:</label>
                    <input type="text" name="nombre" class="u-input" required placeholder="Ej: Juan Pérez">
                </div>

                <div class="form-group">
                    <label>RUT (Opcional):</label>
                    <input type="text" name="rut" class="u-input" placeholder="12.345.678-9">
                </div>

                <button type="submit" class="btn">Obtener Número</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>