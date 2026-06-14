<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $stars = intval($_POST['stars'] ?? 0);
    $comentario = clean($_POST['obs_ciudadano'] ?? '');

    if (empty($id)) {
        die("Error: ID de ticket no proporcionado.");
    }

    $data = getData();
    $encontrado = false;

    foreach ($data['tickets'] as &$ticket) {
        if ($ticket['id'] === $id) {
            $ticket['calificacion'] = $stars;
            $ticket['comentario_ciudadano'] = $comentario;
            $encontrado = true;
            break;
        }
    }

    if ($encontrado) {
        updateData($data);
        // Redirigir de vuelta a status.php con el mismo hash
        $hash = $_POST['h'] ?? '';
        echo "<script>
                alert('¡Gracias por su calificación!');
                window.location.href = '../status.php?id=" . urlencode($id) . "&h=" . urlencode($hash) . "';
              </script>";
    } else {
        die("Error: Ticket no encontrado.");
    }
}