<?php
// reparar.php
$config_path = 'data/config.json';

// Crear carpeta si no existe
if (!is_dir('data')) mkdir('data', 0777, true);

$config = [
    "areas" => ["Tránsito", "Vivienda", "Tesorería"],
    "users" => [
        [
            "user" => "admin",
            "pass" => password_hash("admin123", PASSWORD_BCRYPT),
            "role" => "admin",
            "area" => "all"
        ]
    ]
];

if (file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT))) {
    echo "✅ Archivo config.json restaurado. Usuario: admin | Clave: admin123";
} else {
    echo "❌ Error: No se pudo escribir en $config_path. Revisa los permisos de la carpeta.";
}
?>