<?php
// super_admin/login.php
require_once '../master_api/auth.php';

if (isset($_SESSION['super_token'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $_POST['user'] ?? '';
    $p = $_POST['pass'] ?? '';
    
    $path_json = __DIR__ . '/../global_data/instituciones.json';
    
    if (!file_exists($path_json)) {
        $error = "Error Crítico: Ejecuta rescue.php para instalar la base de datos.";
    } else {
        $data = getGlobalData();
        
        if (isset($data['super_admin']) && $u === $data['super_admin']['user'] && password_verify($p, $data['super_admin']['pass'])) {
            $_SESSION['super_token'] = bin2hex(random_bytes(32)); // Token seguro
            header("Location: index.php");
            exit;
        }
        $error = "Credenciales maestras inválidas.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SaaS Master Control | Acceso</title>
    <style>
        body { background: #0f172a; font-family: 'Segoe UI', sans-serif; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); padding: 40px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); width: 320px; color: white; text-align: center; }
        input { width: 100%; padding: 12px; margin: 10px 0; border-radius: 8px; border: 1px solid #334155; background: #1e293b; color: white; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #2563eb; border: none; color: white; border-radius: 8px; cursor: pointer; font-weight: bold; margin-top: 10px; }
        .error { background: #ef4444; color: white; padding: 10px; border-radius: 8px; font-size: 0.9rem; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="glass">
        <h2>Panel Superior</h2>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="POST">
            <input type="text" name="user" placeholder="Usuario Master" required>
            <input type="password" name="pass" placeholder="Contraseña" required>
            <button type="submit">ENTRAR AL NÚCLEO</button>
        </form>
    </div>
</body>
</html>