// assets/js/citizen.js
async function updateCitizenStatus(ticketId) {
    try {
        const response = await fetch(`api/get_queue.php?id=${ticketId}`);
        const data = await response.json();

        const posEl = document.getElementById('posicion');
        const timeEl = document.getElementById('tiempo_est');
        const feedbackSec = document.getElementById('feedback-section');
        const statusSec = document.getElementById('status-info');

        if (data.estado === 'llamado') {
            posEl.innerHTML = "<span class='blink'>¡ES SU TURNO!</span>";
            timeEl.innerText = "Diríjase al módulo de atención";
            // Podrías activar un sonido aquí
        } else if (data.estado === 'finalizado') {
            statusSec.style.display = 'none';
            feedbackSec.style.display = 'block';
        } else {
            posEl.innerText = data.posicion;
            timeEl.innerText = data.tiempo_espera + " min aprox.";
        }
    } catch (e) {
        console.error("Error consultando estado");
    }
}

function startPolling(id) {
    updateCitizenStatus(id);
    setInterval(() => updateCitizenStatus(id), 10000); // Cada 10 segundos
}