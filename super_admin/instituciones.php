<?php
// master/instituciones.php
require_once '../master_api/auth.php';
checkSuperAuth();
$data_global = getGlobalData();

// --- LÓGICA DE AUTO-SINCRONIZACIÓN (NUEVA) ---
$ruta_instancias = __DIR__ . "/../instancias/";
$carpetas = array_filter(glob($ruta_instancias . '*'), 'is_dir');
$modificado = false;

foreach ($carpetas as $carpeta) {
    $slug_carpeta = basename($carpeta);
    // Ignorar las carpetas marcadas como eliminadas estratégicamente
    if (strpos($slug_carpeta, '_eliminado_') !== false) continue;

    // Buscar si el slug ya existe en nuestro registro JSON
    $existe_en_json = false;
    foreach ($data_global['instituciones'] as $inst) {
        if ($inst['slug'] === $slug_carpeta) {
            $existe_en_json = true;
            break;
        }
    }

    // Si la carpeta existe pero no está en el JSON, la re-vinculamos
    if (!$existe_en_json) {
        $config_file = $carpeta . "/data/config.json";
        if (file_exists($config_file)) {
            $conf_inst = json_decode(file_get_contents($config_file), true);
            $data_global['instituciones'][] = [
                "nombre" => $conf_inst['nombre_institucion'] ?? $slug_carpeta,
                "slug" => $slug_carpeta,
                "admin_user" => $conf_inst['admin_user'] ?? 'admin',
                "estado" => "activo",
                "fecha" => date('Y-m-d')
            ];
            $modificado = true;
        }
    }
}
if ($modificado) saveGlobalData($data_global);

// --- FUNCIONES DE APOYO ---
function checkHealth($slug) {
    $base = __DIR__ . "/../instancias/$slug";
    return (file_exists("$base/index.php") && file_exists("$base/data/config.json"));
}

function clonarPlantilla($src, $dst) {
    if (!is_dir($src)) return;
    @mkdir($dst, 0755, true);
    $dir = opendir($src);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) clonarPlantilla($src . '/' . $file, $dst . '/' . $file);
            else copy($src . '/' . $file, $dst . '/' . $file);
        }
    }
    closedir($dir);
}

// --- ACCIONES ---
if (isset($_GET['action']) && isset($_GET['slug'])) {
    $slug = preg_replace('/[^a-z0-9_]/', '', $_GET['slug']);
    $ruta_base = __DIR__ . "/../instancias/";

    if ($_GET['action'] === 'logout') {
        session_destroy();
        header("Location: login.php");
        exit;
    }
    
    if ($_GET['action'] === 'toggle') {
        foreach ($data_global['instituciones'] as &$inst) {
            if ($inst['slug'] === $slug) {
                $nuevo_estado = ($inst['estado'] === 'activo') ? 'inactivo' : 'activo';
                $inst['estado'] = $nuevo_estado;
                $f_act = $ruta_base . $slug . "/index.php";
                $f_ina = $ruta_base . $slug . "/index_inactivo.php";
                if ($nuevo_estado === 'inactivo' && file_exists($f_act)) rename($f_act, $f_ina);
                elseif ($nuevo_estado === 'activo' && file_exists($f_ina)) rename($f_ina, $f_act);
                break;
            }
        }
        saveGlobalData($data_global);
        header("Location: instituciones.php"); exit;
    }

    if ($_GET['action'] === 'delete') {
        $nuevas = [];
        foreach ($data_global['instituciones'] as $inst) {
            if ($inst['slug'] === $slug) {
                $original = $ruta_base . $slug;
                $destino = $ruta_base . $slug . "_eliminado_" . time();
                if (is_dir($original)) rename($original, $destino);
            } else { $nuevas[] = $inst; }
        }
        $data_global['instituciones'] = $nuevas;
        saveGlobalData($data_global);
        header("Location: instituciones.php"); exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = htmlspecialchars($_POST['nombre']);
    $admin_user = htmlspecialchars($_POST['admin_user']);
    
    if (!empty($_POST['id_editar'])) {
        $slug = $_POST['id_editar'];
        foreach ($data_global['instituciones'] as &$inst) {
            if ($inst['slug'] === $slug) {
                $inst['nombre'] = $nombre;
                $inst['admin_user'] = $admin_user;
                $path = __DIR__ . "/../instancias/$slug/data/config.json";
                if (file_exists($path)) {
                    $conf = json_decode(file_get_contents($path), true);
                    $conf['admin_user'] = $admin_user;
                    if (!empty($_POST['admin_pass'])) $conf['admin_pass'] = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
                    file_put_contents($path, json_encode($conf, JSON_PRETTY_PRINT));
                }
            }
        }
    } else {
        $slug = strtolower(preg_replace('/[^a-z0-9]/', '', $_POST['slug']));
        $ruta = __DIR__ . "/../instancias/$slug";
        if (!is_dir($ruta)) {
            mkdir($ruta, 0755, true);
            mkdir($ruta . "/data", 0755, true);
            clonarPlantilla(__DIR__ . "/../base_template", $ruta);
            $conf = ["nombre_institucion" => $nombre, "admin_user" => $admin_user, "admin_pass" => password_hash($_POST['admin_pass'], PASSWORD_DEFAULT), "areas" => ["General"]];
            file_put_contents($ruta . "/data/config.json", json_encode($conf, JSON_PRETTY_PRINT));
            $data_global['instituciones'][] = ["nombre" => $nombre, "slug" => $slug, "admin_user" => $admin_user, "estado" => "activo", "fecha" => date('Y-m-d')];
        }
    }
    saveGlobalData($data_global);
    header("Location: instituciones.php?ok=1"); exit;
}

$edit_data = null;
if (isset($_GET['edit'])) {
    foreach ($data_global['instituciones'] as $i) { if ($i['slug'] === $_GET['edit']) $edit_data = $i; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Maestro | Instituciones</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4f46e5; --dark: #0f172a; --bg: #f1f5f9; --success: #10b981; --danger: #ef4444; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; display: flex; color: var(--dark); }
        .sidebar { width: 260px; background: var(--dark); color: white; height: 100vh; position: fixed; padding: 30px 20px; box-sizing: border-box; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 1.2rem; margin-bottom: 40px; color: white; display: flex; align-items: center; gap: 10px; }
        .sidebar a { color: #94a3b8; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 12px; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: white; }
        .sidebar a.active { background: var(--primary); color: white; }
        .sidebar .logout { margin-top: auto; color: #f87171; }
        .sidebar .logout:hover { background: rgba(239, 68, 68, 0.1); }
        
        .main { margin-left: 260px; padding: 40px; width: 100%; }
        .grid { display: grid; grid-template-columns: 1fr 380px; gap: 30px; }
        .card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 0.75rem; color: #64748b; text-transform: uppercase; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0; }
        td { padding: 15px 0; border-bottom: 1px solid #f1f5f9; }
        .health-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .h-ok { background: var(--success); box-shadow: 0 0 8px var(--success); }
        .h-err { background: var(--danger); box-shadow: 0 0 8px var(--danger); }
        .btn { padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; font-weight: 600; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; }
        .btn-edit { background: #eef2ff; color: var(--primary); }
        .btn-del { background: #fef2f2; color: var(--danger); margin-left: 5px; }
        input { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; margin: 8px 0 15px; box-sizing: border-box; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fa-solid fa-layer-group"></i> Panel Maestro</h2>
        <a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
        <a href="instituciones.php" class="active"><i class="fa-solid fa-building-columns"></i> Instituciones</a>
        <a href="?action=logout" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
    </div>
    <div class="main">
        <div class="grid">
            <div class="card">
                <h3>Red de Instituciones</h3>
                <table>
                    <thead>
                        <tr><th>Salud</th><th>Institución</th><th>Estado</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($data_global['instituciones'] as $i): 
                            $healthy = checkHealth($i['slug']); ?>
                        <tr>
                            <td><span class="health-dot <?= $healthy ? 'h-ok' : 'h-err' ?>"></span></td>
                            <td><strong><?= $i['nombre'] ?></strong><br><small style="color:#64748b;">slug: <?= $i['slug'] ?></small></td>
                            <td>
                                <a href="?action=toggle&slug=<?= $i['slug'] ?>" style="text-decoration:none; font-size:0.75rem; font-weight:bold; color: <?= ($i['estado'] ?? 'activo') == 'activo' ? 'var(--success)' : '#94a3b8' ?>;">
                                    <i class="fa-solid fa-circle"></i> <?= strtoupper($i['estado'] ?? 'activo') ?>
                                </a>
                            </td>
                            <td>
                                <a href="?edit=<?= $i['slug'] ?>" class="btn btn-edit"><i class="fa-solid fa-pen"></i></a>
                                <a href="?action=delete&slug=<?= $i['slug'] ?>" class="btn btn-del" onclick="return confirm('¿Eliminar?')"><i class="fa-solid fa-trash-can"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h3><i class="fa-solid <?= $edit_data ? 'fa-pen-to-square' : 'fa-plus-circle' ?>"></i> <?= $edit_data ? 'Editar' : 'Nueva' ?> Institución</h3>
                <form method="POST">
                    <input type="hidden" name="id_editar" value="<?= $edit_data['slug'] ?? '' ?>">
                    <label style="font-size:0.8rem; font-weight:600;">Nombre de Institución</label>
                    <input type="text" name="nombre" value="<?= $edit_data['nombre'] ?? '' ?>" placeholder="Ej: Municipalidad de..." required>
                    
                    <?php if(!$edit_data): ?>
                    <label style="font-size:0.8rem; font-weight:600;">Directorio (Slug)</label>
                    <input type="text" name="slug" placeholder="ej: mtemuco" required>
                    <?php endif; ?>

                    <label style="font-size:0.8rem; font-weight:600;">Usuario Administrador</label>
                    <input type="text" name="admin_user" value="<?= $edit_data['admin_user'] ?? '' ?>" required>
                    
                    <label style="font-size:0.8rem; font-weight:600;">Contraseña <?= $edit_data ? '(Opcional)' : '' ?></label>
                    <input type="password" name="admin_pass" <?= $edit_data ? '' : 'required' ?>>
                    
                    <button type="submit" style="width:100%; padding:12px; background:var(--primary); color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer;">
                        <?= $edit_data ? 'Actualizar' : 'Crear Instancia' ?>
                    </button>
                    <?php if($edit_data): ?>
                        <p style="text-align:center;"><a href="instituciones.php" style="font-size:0.8rem; color:#64748b;">Cancelar edición</a></p>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</body>
</html>