<?php
require_once 'db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') die("Acceso denegado");

$config_path = __DIR__ . '/../data/config.json';
$config = json_decode(file_get_contents($config_path), true);
$action = $_GET['action'] ?? '';

// --- AGREGAR FUNCIONARIO ---
if ($action === 'add_user') {
    $nuevo_usuario = [
        "nombre" => clean($_POST['nombre']),
        "user"   => clean($_POST['user']),
        "pass"   => password_hash($_POST['pass'], PASSWORD_BCRYPT),
        "role"   => "staff",
        "area"   => $_POST['area']
    ];

    $config['users'][] = $nuevo_usuario;
    file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT));
    header("Location: ../admin.php?success=user_added");
    exit;
}

// --- EDITAR FUNCIONARIO ---
if ($action === 'edit_user_full') {
    $old_user = $_POST['old_user'];
    $new_name = clean($_POST['name']);
    $new_area = $_POST['area'];

    foreach ($config['users'] as &$u) {
        if ($u['user'] === $old_user) {
            $u['nombre'] = $new_name;
            $u['area'] = $new_area;
            if (!empty($_POST['new_pass'])) { 
                $u['pass'] = password_hash($_POST['new_pass'], PASSWORD_BCRYPT); 
            }
            break;
        }
    }
    file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT));
    header("Location: ../admin.php?success=user_edited");
    exit;
}

// --- GESTIÓN DE ÁREAS ---
if ($action === 'add_area') {
    $area = trim(clean($_POST['new_area']));
    if (!in_array($area, $config['areas'])) {
        $config['areas'][] = $area;
        file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT));
        writeLog("Agregada área: $area");
        header("Location: ../admin.php?tab=areas&msg=area_added");
    } else {
        header("Location: ../admin.php?tab=areas&msg=area_exists");
    }
    exit;
}

if ($action === 'del_area') {
    $area = $_GET['name'];
    $config['areas'] = array_filter($config['areas'], function($a) use ($area) {
        return $a !== $area;
    });
    file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT));
    writeLog("Eliminada área: $area");
    header("Location: ../admin.php?tab=areas&msg=area_deleted");
    exit;
}