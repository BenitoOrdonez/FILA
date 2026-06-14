<?php
require_once 'db.php';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_input = clean($_POST['user']);
    $pass_input = $_POST['pass'];

    $config_path = __DIR__ . '/../data/config.json';
    
    if (!file_exists($config_path)) {
        header("Location: ../login.php?error=config_missing");
        exit;
    }

    $config = json_decode(file_get_contents($config_path), true);
    
    // 1. VALIDACIÓN DEL ADMINISTRADOR (Campos raíz que usa tu instituciones.php)
    if ($user_input === $config['admin_user'] && password_verify($pass_input, $config['admin_pass'])) {
        $_SESSION['user'] = $config['admin_user'];
        $_SESSION['nombre_real'] = "Administrador";
        $_SESSION['role'] = 'admin';
        $_SESSION['area'] = 'all';
        
        header("Location: ../admin.php");
        exit;
    }

    // 2. VALIDACIÓN DE FUNCIONARIOS (Arreglo users)
    if (isset($config['users']) && is_array($config['users'])) {
        foreach ($config['users'] as $u) {
            if ($u['user'] === $user_input && password_verify($pass_input, $u['pass'])) {
                $_SESSION['user'] = $u['user'];
                $_SESSION['nombre_real'] = $u['nombre'] ?? $u['user'];
                $_SESSION['role'] = $u['role'] ?? 'staff';
                $_SESSION['area'] = $u['area'] ?? 'all';
                
                header("Location: ../staff.php");
                exit;
            }
        }
    }
    
    // Si nada coincide
    header("Location: ../login.php?error=1");
    exit;
}