<?php
date_default_timezone_set('America/Santiago');
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$config_path = '../data/config.json';
$config = file_exists($config_path) ? json_decode(file_get_contents($config_path), true) : [];
if (!is_array($config)) $config = [];

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        $new_user = $_POST['display_user'] ?? '';
        $new_pass = $_POST['display_pass'] ?? '';
        $confirm_pass = $_POST['confirm_pass'] ?? '';
        
        if (empty($new_user)) {
            echo json_encode(['success' => false, 'message' => 'El usuario no puede estar vacío']);
            exit;
        }
        
        if (empty($new_pass)) {
            echo json_encode(['success' => false, 'message' => 'La contraseña no puede estar vacía']);
            exit;
        }
        
        if ($new_pass !== $confirm_pass) {
            echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
            exit;
        }
        
        $config['display_user'] = $new_user;
        $config['display_pass'] = password_hash($new_pass, PASSWORD_BCRYPT);
        
        if (file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode([
                'success' => true,
                'message' => 'Credenciales de pantalla actualizadas correctamente',
                'display_user' => $config['display_user']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar los cambios']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
