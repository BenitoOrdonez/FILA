<?php
// super_admin/rescue.php

$dir_global = __DIR__ . '/../global_data';
$file_path = $dir_global . '/instituciones.json';

if (isset($_GET['ejecutar'])) {
    // 1. Crear carpeta si no existe
    if (!is_dir($dir_global)) {
        mkdir($dir_global, 0777, true);
    }
    
    // 2. Crear estructura JSON con la contraseña por defecto
    $data = [
        "super_admin" => [
            "user" => "admin_maestro",
            "pass" => password_hash('Pitrufquen2026', PASSWORD_DEFAULT)
        ],
        "instituciones" => []
    ];
    
    // 3. Guardar el archivo
    file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
    
    echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>";
    echo "<h2 style='color:#1e8e3e;'>¡Sistema Inicializado con Éxito!</h2>";
    echo "<p>La base de datos global ha sido creada.</p>";
    echo "<p>Usuario: <b>admin_maestro</b></p>";
    echo "<p>Contraseña: <b>Pitrufquen2026</b></p>";
    echo "<br><a href='login.php' style='padding:12px 25px; background:#2563eb; color:white; text-decoration:none; border-radius:8px; font-weight:bold;'>Ir al Login</a>";
    echo "</div>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Instalador SaaS</title>
</head>
<body style="background:#0f172a; color:white; font-family:sans-serif; display:flex; justify-content:center; align-items:center; height:100vh; margin:0;">
    <div style="background:#1e293b; padding:40px; border-radius:15px; text-align:center; border:1px solid #334155;">
        <h2>Herramienta de Inicialización</h2>
        <p style="color:#94a3b8; margin-bottom:30px;">Al hacer clic, se crearán las carpetas necesarias<br>y se establecerá la contraseña maestra.</p>
        <a href='?ejecutar=1' style='padding:12px 25px; background:#dc3545; color:white; text-decoration:none; border-radius:8px; font-weight:bold;'>Instalar / Restaurar Acceso</a>
    </div>
</body>
</html>