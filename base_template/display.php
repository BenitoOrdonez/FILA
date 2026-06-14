<?php
date_default_timezone_set('America/Santiago');
session_start();

$config_path = 'data/config.json';
$config = file_exists($config_path) ? json_decode(file_get_contents($config_path), true) : [];
if (!is_array($config)) $config = [];

$display_user = $config['display_user'] ?? 'display';
$display_pass = $config['display_pass'] ?? '';

$authenticated = isset($_SESSION['display_authenticated']) && $_SESSION['display_authenticated'] === true;

$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user = $_POST['usuario'] ?? '';
    $pass = $_POST['contrasena'] ?? '';
    
    if ($user === $display_user && password_verify($pass, $display_pass)) {
        $_SESSION['display_authenticated'] = true;
        header('Location: display.php');
        exit;
    } else {
        $login_error = 'Usuario o contraseña incorrectos';
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['display_authenticated']);
    session_destroy();
    header('Location: display.php');
    exit;
}

if (!$authenticated):
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso - Tótem de Turnos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            font-family: sans-serif;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            width: 100%;
            max-width: 400px;
            color: #1e293b;
        }
        .login-container h1 {
            margin-bottom: 30px;
            text-align: center;
            font-size: 1.8rem;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #2563eb;
        }
        .error-message {
            color: #dc2626;
            font-size: 0.9rem;
            margin-bottom: 15px;
            padding: 10px;
            background: #fee2e2;
            border-radius: 6px;
            border-left: 4px solid #dc2626;
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .login-btn:hover {
            transform: translateY(-2px);
        }
        .login-btn:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>🎫 Tótem de Turnos</h1>
        <?php if ($login_error): ?>
            <div class="error-message"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="usuario">Usuario:</label>
                <input type="text" id="usuario" name="usuario" required autofocus>
            </div>
            <div class="form-group">
                <label for="contrasena">Contraseña:</label>
                <input type="password" id="contrasena" name="contrasena" required>
            </div>
            <button type="submit" name="login" class="login-btn">Iniciar Sesión</button>
        </form>
    </div>
</body>
</html>
<?php
else:
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tótem de Turnos</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body { background: #1e293b; color: white; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; font-family: sans-serif; }
        .qr-container { background: white; padding: 30px; border-radius: 25px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        #timer { font-size: 3rem; font-weight: bold; color: #38bdf8; margin-top: 20px; }
        .logout-btn { position: absolute; top: 20px; right: 20px; padding: 10px 20px; background: #ef4444; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .logout-btn:hover { background: #dc2626; }
    </style>
</head>
<body>
    <a href="display.php?logout=1" class="logout-btn">Cerrar Sesión</a>
    <h1>Escanee para obtener su turno</h1>
    <div class="qr-container" id="qrcode"></div>
    <div id="timer">60</div>

    <script>
        let timeLeft = 60;
        function renderQR() {
            const loc = window.location;
            const path = loc.pathname.substring(0, loc.pathname.lastIndexOf('/') + 1);
            const target = loc.origin + path + "index.php";
            const token = btoa(new Date().toISOString().substring(0, 16)).replace(/=/g, "");
            
            document.getElementById("qrcode").innerHTML = "";
            new QRCode(document.getElementById("qrcode"), {
                text: `${target}?t=${token}`,
                width: 350,
                height: 350,
                colorDark : "#0f172a",
                correctLevel : QRCode.CorrectLevel.H
            });
            timeLeft = 60;
        }

        setInterval(() => {
            timeLeft--;
            document.getElementById("timer").innerText = timeLeft;
            if(timeLeft <= 0) renderQR();
        }, 1000);

        renderQR();
    </script>
</body>
</html>
<?php endif; ?>