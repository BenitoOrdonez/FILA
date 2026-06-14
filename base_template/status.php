<?php
require_once 'api/db.php';
$id = $_GET['id'] ?? '';
$hash = $_GET['h'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de su Turno</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { background: white; width: 90%; max-width: 450px; padding: 40px; border-radius: 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); text-align: center; border-top: 8px solid #2563eb; }
        .ticket-id { display: flex; flex-direction: column; align-items: center; margin: 15px 0; }
        .ticket-id-prefix { font-size: 1.4rem; font-weight: 600; color: #475569; letter-spacing: 0; }
        .ticket-id-number { font-size: 5.5rem; font-weight: 900; color: #1e40af; letter-spacing: -2px; line-height: 1; }
        .pos-value { font-size: 4rem; font-weight: 700; color: #64748b; }
        .alert-next { background: #fffbeb; border: 2px solid #f59e0b; color: #b45309; padding: 20px; border-radius: 15px; font-weight: 800; font-size: 1.2rem; animation: pulse 1.5s infinite; margin-top: 20px; }
        
        /* Formulario de calificación */
        #feedback-form { display: none; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-top: 20px; }
        .feedback-title { text-align: center; color: #1e40af; font-size: 1.5rem; font-weight: 700; margin-bottom: 20px; }
        .star-rating { display: flex; justify-content: center; gap: 10px; margin: 20px 0; }
        .star-rating .star { font-size: 2.5rem; cursor: pointer; color: #d1d5db; transition: transform 0.2s ease, color 0.2s ease; }
        .star-rating .star.selected { color: #ffc107; transform: scale(1.1); }
        .star-rating .star:hover { transform: scale(1.2); }
        .rating-text { text-align: center; margin: 10px 0; font-weight: 600; color: #374151; }
        .feedback-textarea { width: 100%; padding: 15px; border: 2px solid #e5e7eb; border-radius: 10px; font-family: inherit; font-size: 1rem; resize: vertical; min-height: 80px; margin: 15px 0; }
        .feedback-textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .feedback-btn { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; border: none; padding: 15px 30px; border-radius: 10px; font-size: 1.1rem; font-weight: 600; cursor: pointer; width: 100%; transition: transform 0.2s ease; }
        .feedback-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); }
        .thank-you { text-align: center; color: #059669; font-size: 1.3rem; font-weight: 700; margin: 20px 0; }
        
        /* Pantalla de llamado Verde Vibrante */
        #called-screen { 
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white; flex-direction: column; 
            justify-content: center; align-items: center; z-index: 10000;
            font-family: 'Segoe UI', sans-serif;
        }
        .called-content {
            text-align: center;
            animation: scaleIn 0.5s ease-out;
        }
        .vibrate-text { 
            font-size: 3.5rem; 
            font-weight: 800; 
            text-shadow: 0 4px 15px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            letter-spacing: 1px;
            animation: pulse-text 2s ease-in-out infinite;
        }
        .called-subtitle {
            font-size: 1.8rem; 
            margin: 20px 0; 
            font-weight: 600;
            opacity: 0.95;
            letter-spacing: 0.5px;
        }
        .called-number-container {
            background: rgba(255,255,255,0.15);
            border-radius: 30px;
            padding: 40px;
            margin: 30px 0;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255,255,255,0.2);
        }
        .called-number { 
            font-size: 8rem; 
            font-weight: 900; 
            color: white;
            line-height: 1;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .called-instruction {
            font-size: 1.5rem;
            margin-top: 30px;
            font-weight: 500;
            opacity: 0.9;
            animation: fadeInUp 1s ease-out;
        }
        @keyframes scaleIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        @keyframes pulse-text {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        @keyframes fadeInUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.03); } 100% { transform: scale(1); } }
    </style>
</head>
<body>

    <div id="main-ui" class="card">
        <h2 id="inst-name" style="color:#1e293b; margin-top:0;">Cargando...</h2>
        <p style="color:#64748b; margin-bottom:0;">Su número de atención</p>
        <div class="ticket-id" id="display-id">
            <span class="ticket-id-prefix" id="display-prefix">--</span>
            <span class="ticket-id-number" id="display-number">--</span>
        </div>
        
        <div id="queue-info">
            <p style="margin-bottom:5px;" id="queue-label">Personas adelante:</p>
            <div class="pos-value" id="display-pos">--</div>
            <div id="prep-msg" class="alert-next" style="display:none;">Prepárese usted es el siguiente</div>
            <div id="no-show-msg" class="alert-next" style="display:none; background: #fef2f2; border-color: #f87171; color: #991b1b;">
                Usted no se presentó, puede volver a tomar número cuando quiera.
            </div>
        </div>
        
        <!-- Formulario de calificación -->
        <div id="feedback-form">
            <div class="feedback-title">¡Gracias por su visita!</div>
            <p style="text-align: center; color: #6b7280; margin-bottom: 20px;">Por favor, califique su experiencia</p>
            <div class="star-rating" id="star-rating">
                <span class="star" data-value="1">★</span>
                <span class="star" data-value="2">★</span>
                <span class="star" data-value="3">★</span>
                <span class="star" data-value="4">★</span>
                <span class="star" data-value="5">★</span>
            </div>
            <div class="rating-text" id="rating-text">Seleccione una calificación</div>
            <textarea class="feedback-textarea" id="feedback-comment" placeholder="Comentarios adicionales (opcional)..."></textarea>
            <button class="feedback-btn" onclick="submitFeedback()">Enviar Calificación</button>
        </div>
        
        <div id="thank-you-msg" class="thank-you" style="display:none;">
            ¡Gracias por su calificación! 🎉
        </div>
    </div>

    <div id="called-screen">
        <div class="called-content">
            <div class="vibrate-text">✓ Es su Turno</div>
            <div class="called-number-container">
                <div class="called-number" id="called-id">--</div>
            </div>
            <p class="called-subtitle">Acérquese</p>
            <p class="called-instruction">Diríjase al módulo de atención</p>
        </div>
    </div>

    <script>
        let hasVibrated = false;
        let selectedRating = 0;
        
        async function updateStatus() {
            try {
                const res = await fetch(`api/get_queue.php?id=<?= $id ?>&h=<?= $hash ?>`);
                const data = await res.json();
                
                console.log('Status data:', data);
                
                if (data.error) {
                    document.getElementById('main-ui').innerHTML = '<h2 style="color:red;">Error: ' + data.error + '</h2>';
                    return;
                }
                
                document.getElementById('inst-name').innerText = data.institucion;
                const ticketSplit = splitTicketId(data.id);
                document.getElementById('display-prefix').innerText = ticketSplit.prefix;
                document.getElementById('display-number').innerText = ticketSplit.number;
                document.getElementById('called-id').innerText = ticketSplit.number;

                // Resetear displays por defecto
                document.getElementById('main-ui').style.display = 'block';
                document.getElementById('called-screen').style.display = 'none';
                document.getElementById('feedback-form').style.display = 'none';
                document.getElementById('thank-you-msg').style.display = 'none';
                document.getElementById('queue-info').style.display = 'block';
                document.getElementById('no-show-msg').style.display = 'none';

                // Mostrar formulario de calificación si el ticket está atendido y no tiene calificación
                if (data.estado === 'atendido' && (data.calificacion === null || data.calificacion === undefined)) {
                    document.getElementById('main-ui').style.display = 'block';
                    document.getElementById('queue-info').style.display = 'none';
                    document.getElementById('feedback-form').style.display = 'block';
                    console.log('Mostrando formulario de calificación');
                    return;
                }
                
                // Mostrar mensaje de agradecimiento si ya calificó
                if (data.estado === 'atendido' && data.calificacion !== null && data.calificacion !== undefined) {
                    document.getElementById('main-ui').style.display = 'block';
                    document.getElementById('queue-info').style.display = 'none';
                    document.getElementById('thank-you-msg').style.display = 'block';
                    console.log('Mostrando mensaje de agradecimiento');
                    return;
                }

                // Mostrar aviso de no presentación cuando el ticket fue marcado como no presentado
                if (data.estado === 'no_presentado') {
                    document.getElementById('main-ui').style.display = 'block';
                    document.getElementById('called-screen').style.display = 'none';
                    document.getElementById('queue-info').style.display = 'block';
                    document.getElementById('queue-label').style.display = 'none';
                    document.getElementById('display-pos').style.display = 'none';
                    document.getElementById('prep-msg').style.display = 'none';
                    document.getElementById('no-show-msg').style.display = 'block';
                    return;
                }

                if (data.estado === 'atendiendo') {
                    // El cliente está siendo atendido - mostrar pantalla verde
                    console.log('Mostrando pantalla llamado');
                    document.getElementById('main-ui').style.display = 'none';
                    document.getElementById('called-screen').style.display = 'flex';
                    if (!hasVibrated && navigator.vibrate) {
                        navigator.vibrate([400, 200, 400, 200, 400]);
                        hasVibrated = true;
                    }
                    return;
                }

                // Lógica para tickets esperando en cola
                if (data.posicion === 1) {
                    // El cliente es el siguiente
                    console.log('Cliente es el siguiente');
                    document.getElementById('main-ui').style.display = 'block';
                    document.getElementById('called-screen').style.display = 'none';
                    document.getElementById('queue-info').style.display = 'block';
                    document.getElementById('queue-label').style.display = 'none';
                    document.getElementById('display-pos').style.display = 'none';
                    document.getElementById('prep-msg').style.display = 'block';
                } else if (data.posicion > 1) {
                    // Hay personas adelante
                    console.log('Personas adelante: ' + (data.posicion - 1));
                    document.getElementById('main-ui').style.display = 'block';
                    document.getElementById('called-screen').style.display = 'none';
                    document.getElementById('queue-info').style.display = 'block';
                    document.getElementById('queue-label').style.display = 'block';
                    document.getElementById('queue-label').innerText = 'Personas adelante:';
                    document.getElementById('display-pos').style.display = 'block';
                    document.getElementById('display-pos').innerText = data.posicion - 1;
                    document.getElementById('prep-msg').style.display = 'none';
                } else {
                    // Caso por defecto - ocultar mensajes de preparación
                    document.getElementById('prep-msg').style.display = 'none';
                }
            } catch (e) { 
                console.log("Error de actualización: " + e.message);
            }
        }
        
        function splitTicketId(id) {
            if (!id) return { prefix: '--', number: '--' };
            const parts = id.split('-');
            if (parts.length === 1) {
                return { prefix: '', number: id };
            }
            const number = parts.pop();
            const prefix = parts.join('-');
            return { prefix: prefix || '', number: number || id };
        }
        
        // Función para seleccionar calificación con estrellas
        function setRating(value) {
            selectedRating = value;
            document.querySelectorAll('#star-rating .star').forEach(star => {
                const starValue = parseInt(star.getAttribute('data-value'), 10);
                star.classList.toggle('selected', starValue <= value);
            });
            
            const ratingTexts = ['', 'Muy mala', 'Mala', 'Regular', 'Buena', 'Excelente'];
            document.getElementById('rating-text').innerText = ratingTexts[value] || 'Seleccione una calificación';
        }
        
        // Inicializar eventos de estrellas
        document.querySelectorAll('#star-rating .star').forEach(star => {
            star.addEventListener('click', () => {
                const value = parseInt(star.getAttribute('data-value'), 10);
                setRating(value);
            });
        });
        
        // Función para enviar calificación
        async function submitFeedback() {
            if (selectedRating === 0) {
                alert('Por favor seleccione una calificación');
                return;
            }
            
            const comment = document.getElementById('feedback-comment').value;
            
            try {
                const formData = new FormData();
                formData.append('id', '<?= $id ?>');
                formData.append('h', '<?= $hash ?>');
                formData.append('stars', selectedRating);
                formData.append('obs_ciudadano', comment);
                
                const response = await fetch('api/submit_feedback.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    document.getElementById('feedback-form').style.display = 'none';
                    document.getElementById('thank-you-msg').style.display = 'block';
                } else {
                    alert('Error al enviar la calificación');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al enviar la calificación');
            }
        }
        
        setInterval(updateStatus, 3000);
        updateStatus();
    </script>
</body>
</html>