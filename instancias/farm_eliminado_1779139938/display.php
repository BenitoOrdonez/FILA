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
    </style>
</head>
<body>
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