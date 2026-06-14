<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

// Validar sesión sin hacer redirect
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "No autenticado"]);
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Acceso denegado. Solo administrador."]);
    exit;
}

$config_path = dirname(__DIR__) . "/data/config.json";
$response = ["success" => false, "message" => "", "users" => []];

try {
    // Verificar que el archivo existe
    if (!file_exists($config_path)) {
        throw new Exception("config.json no encontrado en " . $config_path);
    }

    $config_content = file_get_contents($config_path);
    if ($config_content === false) {
        throw new Exception("No se puede leer config.json");
    }

    $config = json_decode($config_content, true);
    if ($config === null) {
        throw new Exception("config.json no es un JSON válido");
    }

    if (!isset($config['users'])) {
        $config['users'] = [];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $user = trim($_POST['user'] ?? '');
            $nombre = trim($_POST['nombre'] ?? '');
            $area = $_POST['area'] ?? '';
            $pass = $_POST['pass'] ?? '';

            if (empty($user) || empty($nombre) || empty($area) || empty($pass)) {
                throw new Exception("Todos los campos son requeridos");
            }

            // Verificar que no existe el usuario
            foreach ($config['users'] as $u) {
                if ($u['user'] === $user) {
                    throw new Exception("El usuario '$user' ya existe");
                }
            }

            $nuevo_usuario = [
                "user" => $user,
                "nombre" => $nombre,
                "area" => $area,
                "pass" => password_hash($pass, PASSWORD_DEFAULT),
                "role" => "staff"
            ];
            $config['users'][] = $nuevo_usuario;
            $response["message"] = "Ejecutivo '$nombre' creado exitosamente";
        }
        elseif ($action === 'edit') {
            $user = trim($_POST['user'] ?? '');
            $nombre = trim($_POST['nombre'] ?? '');
            $area = $_POST['area'] ?? '';
            $pass = $_POST['pass'] ?? '';

            if (empty($user) || empty($nombre) || empty($area)) {
                throw new Exception("Todos los campos son requeridos");
            }

            $found = false;
            foreach ($config['users'] as &$u) {
                if ($u['user'] === $user) {
                    $u['nombre'] = $nombre;
                    $u['area'] = $area;
                    if (!empty($pass)) {
                        $u['pass'] = password_hash($pass, PASSWORD_DEFAULT);
                    }
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new Exception("Usuario '$user' no encontrado");
            }
            $response["message"] = "Ejecutivo '$nombre' actualizado exitosamente";
        }
        elseif ($action === 'delete') {
            $user = trim($_POST['user'] ?? '');
            if (empty($user)) {
                throw new Exception("Usuario requerido para eliminar");
            }

            $found = false;
            $deleted_nombre = '';
            foreach ($config['users'] as $u) {
                if ($u['user'] === $user) {
                    $deleted_nombre = $u['nombre'];
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new Exception("Usuario '$user' no encontrado");
            }

            $config['users'] = array_filter($config['users'], function($u) use ($user) {
                return $u['user'] !== $user;
            });
            $config['users'] = array_values($config['users']);
            $response["message"] = "Ejecutivo '$deleted_nombre' eliminado exitosamente";
        }

        // Guardar el JSON
        $json_encoded = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json_encoded === false) {
            throw new Exception("No se puede codificar JSON");
        }

        $written = file_put_contents($config_path, $json_encoded);
        if ($written === false) {
            throw new Exception("No se puede escribir en config.json. Verifica los permisos del directorio data/");
        }

        writeLog("Acción en ejecutivos: $action - Usuario: $user");
        $response["success"] = true;
    }

    // Devolver la lista actual de usuarios
    $response["users"] = $config['users'];
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $response["message"] = $e->getMessage();
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    http_response_code(400);
}