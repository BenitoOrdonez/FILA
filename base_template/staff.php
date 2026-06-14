<?php
date_default_timezone_set('America/Santiago');
require_once 'api/db.php';
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }

$_SESSION['nombre_real'] = $_SESSION['nombre_real'] ?? $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Ejecutivo - Sistema de Filas</title>
    <style>
        :root { --blue: #0056b3; --light-blue: #e3f2fd; --gray: #f8f9fa; --danger: #dc3545; --secondary: #6c757d; --success: #28a745; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #eef2f7; margin: 0; }
        .nav { background: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .nav-left h2 { margin: 0; font-size: 1.2rem; color: #333; }
        .nav-left small { color: #666; font-size: 0.9rem; }
        .nav-actions { display: flex; gap: 10px; align-items: center; }
        .btn-logout { background: var(--secondary); color: white; text-decoration: none; padding: 8px 15px; border-radius: 6px; font-weight: bold; font-size: 0.9rem; transition: 0.2s; }
        .btn-logout:hover { background: #5a6268; }
        
        .container { display: flex; flex-wrap: wrap; gap: 20px; padding: 20px; max-width: 1400px; margin: 0 auto; }
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); flex: 1; min-width: 320px; }
        .card h3 { margin-top: 0; color: #333; font-size: 1rem; }
        
        .totem-info { background: #fff3e070; border-left: 5px solid #ff9800; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .totem-info small { color: #e67e22; font-weight: bold; display: block; margin-bottom: 5px; }
        .totem-info strong { font-size: 1.8rem; color: #ff9800; }
        
        .call-btn { background: var(--blue); color: white; border: none; padding: 20px; width: 100%; border-radius: 10px; font-size: 1.3rem; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .call-btn:hover { background: #003d82; }
        .call-btn:active { transform: scale(0.98); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; font-size: 0.8rem; color: #888; border-bottom: 2px solid #eee; padding: 10px 5px; text-transform: uppercase; }
        td { padding: 12px 5px; border-bottom: 1px solid #f2f2f2; font-size: 0.95rem; }
        .badge { background: var(--light-blue); color: var(--blue); padding: 4px 8px; border-radius: 5px; font-weight: bold; font-size: 0.85rem; }
        
        .panel-activo { background: var(--blue); color: white; padding: 20px; border-radius: 10px; margin-bottom: 15px; border-left: 8px solid #003d82; }
        .panel-activo small { opacity: 0.8; font-weight: bold; display: block; }
        .panel-activo h2 { margin: 5px 0; font-size: 2.8rem; letter-spacing: -1px; }
        .ciudadano-box { background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px; margin-top: 10px; }
        .ciudadano-box span { font-size: 0.9rem; display: block; opacity: 0.9; }
        .ciudadano-box strong { font-size: 2.2rem; display: block; line-height: 1.2; text-transform: uppercase; }
        
        textarea { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ccc; box-sizing: border-box; font-family: inherit; margin-bottom: 15px; }
        textarea:focus { outline: none; border-color: var(--blue); }

        .action-buttons { display: flex; gap: 10px; }
        .btn-action { flex: 1; color: white; border: none; padding: 15px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 1rem; transition: 0.2s; }
        .btn-finish { background: var(--success); }
        .btn-finish:hover { background: #218838; }
        .btn-no-show { background: var(--danger); }
        .btn-no-show:hover { background: #c82333; }
        
        .theme-toggle { background: #666; padding: 5px 10px; border-radius: 5px; border: none; font-size: 1.15rem; cursor: pointer; transition: 0.3s; }
        .theme-toggle:hover { transform: scale(1.1); }
        
        @media (max-width: 768px) { 
            .container { padding: 10px; gap: 10px; } 
            .card { min-width: 100%; } 
            .action-buttons { flex-direction: column; }
        }
        
        body.dark-mode { background: #121212; color: #e0e0e0; }
        body.dark-mode .nav { background: #1e1e1e; border-bottom: 1px solid #333; }
        body.dark-mode .nav-left h2, body.dark-mode .nav-left small { color: #e0e0e0; }
        body.dark-mode .card { background: #1e1e1e; border: 1px solid #333; }
        body.dark-mode h3 { color: #e0e0e0; }
        body.dark-mode textarea { background: #2d2d2d; color: #fff; border: 1px solid #555; }
        body.dark-mode table { color: #e0e0e0; }
        body.dark-mode th { border-bottom-color: #444; color: #aaa; }
        body.dark-mode td { border-bottom-color: #333; }
        body.dark-mode .totem-info { background: #2d2d2d; border-left-color: #ff9800; }
        body.dark-mode .panel-activo { background: #003d82; }
    </style>
</head>
<body>

    <div class="nav">
        <div class="nav-left">
            <h2><?= htmlspecialchars($_SESSION['nombre_real']) ?></h2>
            <small>Área: <?= htmlspecialchars($_SESSION['area']) ?></small>
        </div>
        <div class="nav-actions">
            <a href="api/auth.php?action=logout" class="btn-logout">Cerrar Sesión</a>
            <button id="theme-toggle" class="theme-toggle" title="Alternar Modo Oscuro">🌙</button>
        </div>
    </div>

    <div class="container">
        <div class="card" style="flex: 1; min-width: 350px;">
            <h3>📋 Fila de Espera</h3>
            
            <div class="totem-info">
                <small>PRÓXIMO NÚMERO DISPONIBLE:</small>
                <strong id="next-totem">--</strong>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead><tr><th>ID Ticket</th><th>Nombre</th><th>Trámite</th></tr></thead>
                    <tbody id="lista-body"></tbody>
                </table>
            </div>
        </div>

        <div class="card" id="main-action-panel" style="flex: 1; min-width: 350px;">
            <h3>📢 Llamar Siguiente</h3>
            <p style="color: #666;">Haga clic en el botón para atender al primer ciudadano en la fila.</p>
            <button onclick="llamar()" class="call-btn">🔔 LLAMAR SIGUIENTE</button>
        </div>
    </div>

    <script>
        function renderPanelActivo(t) {
            const nombreCiudadano = t.nombre ? htmlEscape(t.nombre) : "Sin Nombre";
            const rutCiudadano = t.rut ? htmlEscape(t.rut) : "S/I";
            document.getElementById('main-action-panel').innerHTML = `
                <div class="panel-activo">
                    <small>👤 ATENDIENDO AHORA:</small>
                    <h2>${t.id}</h2>
                    <div class="ciudadano-box">
                        <span>CIUDADANO:</span>
                        <strong>${nombreCiudadano}</strong>
                        <small style="margin-top: 8px; opacity: 0.8;">RUT: ${rutCiudadano}</small>
                    </div>
                </div>
                <textarea id="obs" placeholder="Notas de la atención (opcional)..." style="height:80px;"></textarea>
                <div class="action-buttons">
                    <button onclick="finalizar('${t.id}')" class="btn-action btn-finish">✅ FINALIZAR</button>
                    <button onclick="noSePresentó('${t.id}')" class="btn-action btn-no-show">❌ NO SE PRESENTÓ</button>
                </div>
            `;
        }

        function renderPanelEspera() {
            if (!document.querySelector('.call-btn')) {
                document.getElementById('main-action-panel').innerHTML = `
                    <h3>📢 Llamar Siguiente</h3>
                    <p style="color: #666;">Haga clic en el botón para atender al primer ciudadano en la fila.</p>
                    <button onclick="llamar()" class="call-btn">🔔 LLAMAR SIGUIENTE</button>
                `;
            }
        }

        async function refresh() {
            try {
                const resP = await fetch('api/manage_queue.php?action=peek_next');
                const dataP = await resP.json();
                document.getElementById('next-totem').innerText = dataP.siguiente_disponible || '--';

                const resL = await fetch('api/manage_queue.php?action=list');
                const tickets = await resL.json();
                const tbody = document.getElementById('lista-body');
                
                if (!tickets || tickets.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#999; padding:20px;">No hay nadie en espera</td></tr>';
                } else {
                    tbody.innerHTML = '';
                    tickets.forEach(t => {
                        const nombre = htmlEscape(t.nombre || 'S/N');
                        const tramite = htmlEscape(t.tramite || 'General');
                        tbody.innerHTML += `<tr><td><span class="badge">${t.id}</span></td><td>${nombre}</td><td>${tramite}</td></tr>`;
                    });
                }

                const resCurrent = await fetch('api/manage_queue.php?action=current');
                const tCurrent = await resCurrent.json();
                
                if (tCurrent && tCurrent.id && !tCurrent.none) {
                    if (!document.getElementById('obs')) {
                        renderPanelActivo(tCurrent);
                    }
                } else {
                    renderPanelEspera();
                }
            } catch (e) { console.error('Error:', e); }
        }

        async function llamar() {
            try {
                const res = await fetch('api/manage_queue.php?action=llamar');
                const data = await res.json();
                if (!data.success) { alert('⚠️ ' + (data.error || data.message)); return; }
                renderPanelActivo(data.ticket);
                refresh();
            } catch (e) { alert('Error: ' + e.message); }
        }

        async function finalizar(id) {
            const obs = document.getElementById('obs').value;
            try {
                const formData = new FormData();
                formData.append('action', 'finalizar');
                formData.append('id', id);
                formData.append('observaciones', obs);
                const res = await fetch('api/manage_queue.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) { alert('✅ Atención finalizada'); location.reload(); }
                else { alert('❌ Error: ' + data.message); }
            } catch (e) { alert('Error: ' + e.message); }
        }

        async function noSePresentó(id) {
            if (!confirm('¿Marcar como no se presentó?')) return;
            try {
                const formData = new FormData();
                formData.append('action', 'no_presentado');
                formData.append('id', id);
                const res = await fetch('api/manage_queue.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) { alert('✅ ' + data.message); location.reload(); }
                else { alert('❌ ' + (data.message || 'Error')); }
            } catch (e) { alert('Error: ' + e.message); }
        }

        function htmlEscape(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        setInterval(refresh, 5000);
        refresh();
        
        const themeToggle = document.getElementById('theme-toggle');
        const currentTheme = localStorage.getItem('theme') || 'light';
        if (currentTheme === 'dark') {
            document.body.classList.add('dark-mode');
            themeToggle.innerText = '☀️';
        }
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const theme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
            localStorage.setItem('theme', theme);
            themeToggle.innerText = theme === 'dark' ? '☀️' : '🌙';
        });
    </script>
</body>
</html>