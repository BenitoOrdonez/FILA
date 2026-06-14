<?php
session_name('filas_' . preg_replace('/[^a-zA-Z0-9_]/', '_', basename(__DIR__)));
session_start();
if (isset($_SESSION['role'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? "admin.php" : "staff.php"));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso Gestión de Filas</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #0056b3; font-family: sans-serif; }
        .login-card { background: white; padding: 40px; border-radius: 12px; width: 100%; max-width: 350px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .error-msg { background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 style="text-align: center; margin-bottom: 30px;">Iniciar Sesión</h2>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="error-msg">Credenciales incorrectas o error de sistema.</div>
        <?php endif; ?>

        <form action="api/auth.php" method="POST">
            <label style="display:block; margin-bottom:5px; font-weight:bold;">Usuario</label>
            <input type="text" name="user" required style="width:100%; padding:12px; margin-bottom:20px; border:1px solid #ddd; border-radius:6px; box-sizing: border-box;">

            <label style="display:block; margin-bottom:5px; font-weight:bold;">Contraseña</label>
            <input type="password" name="pass" required style="width:100%; padding:12px; margin-bottom:25px; border:1px solid #ddd; border-radius:6px; box-sizing: border-box;">

            <button type="submit" style="width:100%; padding:14px; background:#2563eb; color:white; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Entrar al Sistema</button>
        </form>
    </div>
</body>
</html>