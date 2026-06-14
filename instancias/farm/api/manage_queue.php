<?php
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(["success" => false, "error" => "No autorizado"]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$data = getData();
$user_area = $_SESSION['area'] ?? 'all';

function normalizar($texto) {
    if (!$texto) return "";
    $buscar =  ['á','é','í','ó','ú','Á','É','Í','Ó','Ú'];
    $reemplazar = ['a','e','i','o','u','a','e','i','o','u'];
    return strtolower(trim(str_replace($buscar, $reemplazar, $texto)));
}

$areaS = normalizar($user_area);

if ($action === 'list') {
    $lista = [];
    foreach ($data['tickets'] as $t) {
        $areaT = normalizar($t['tramite']);
        if ($t['estado'] === 'esperando' && ($areaS === 'all' || $areaT === $areaS)) {
            $lista[] = $t;
        }
    }
    echo json_encode($lista);
    exit;
}

if ($action === 'peek_next') {
    $esperando = "Ninguno";
    $conteo_global_area = 0;

    foreach ($data['tickets'] as $t) {
        $areaT = normalizar($t['tramite']);
        if ($areaS === 'all' || $areaT === $areaS) {
            $conteo_global_area++; 
            if ($t['estado'] === 'esperando' && $esperando === "Ninguno") {
                $esperando = $t['id'];
            }
        }
    }

    $fecha = date("dm");
    $prefijo = ($areaS === 'all') ? 'GEN' : strtoupper(substr($areaS, 0, 3));
    $siguiente_disponible = $fecha . "-" . $prefijo . "-" . str_pad($conteo_global_area + 1, 3, "0", STR_PAD_LEFT);

    echo json_encode([
        "proximo_en_fila" => $esperando,
        "siguiente_disponible" => $siguiente_disponible
    ]);
    exit;
}

// ACCIÓN: VERIFICAR SI EL EJECUTIVO TIENE UNA ATENCIÓN EN CURSO
if ($action === 'current') {
    $atendiendo = null;
    foreach ($data['tickets'] as $t) {
        if ($t['estado'] === 'atendiendo' && $t['atendido_por'] === $_SESSION['user']) {
            $atendiendo = $t;
            break;
        }
    }
    echo json_encode($atendiendo ?: ["none" => true]);
    exit;
}

// ACCIÓN: LLAMAR SIGUIENTE TICKET (compatibilidad con 'next' y 'llamar')
if ($action === 'next' || $action === 'llamar') {
    $encontrado = null;
    $ticket_id = $_GET['id'] ?? null;
    
    if ($ticket_id) {
        // Buscar específicamente este ticket
        foreach ($data['tickets'] as &$ticket) {
            if ($ticket['id'] === $ticket_id) {
                $areaT = normalizar($ticket['tramite']);
                if ($ticket['estado'] === 'esperando' && ($areaS === 'all' || $areaT === $areaS)) {
                    $ticket['estado'] = 'atendiendo';
                    $ticket['atendido_por'] = $_SESSION['user'];
                    $ticket['hora_atencion'] = date("H:i:s");
                    $encontrado = $ticket;
                }
                break;
            }
        }
    } else {
        // Buscar el primer ticket en espera
        foreach ($data['tickets'] as &$ticket) {
            $areaT = normalizar($ticket['tramite']);
            if ($ticket['estado'] === 'esperando' && ($areaS === 'all' || $areaT === $areaS)) {
                $ticket['estado'] = 'atendiendo';
                $ticket['atendido_por'] = $_SESSION['user'];
                $ticket['hora_atencion'] = date("H:i:s");
                $encontrado = $ticket; 
                break;
            }
        }
    }
    
    if ($encontrado) {
        updateData($data);
        echo json_encode(["success" => true, "message" => "Ticket llamado", "ticket" => $encontrado]);
    } else {
        echo json_encode(["success" => false, "error" => "No hay ciudadanos esperando."]);
    }
    exit;
}

// ACCIÓN: FINALIZAR ATENCIÓN
if ($action === 'finalizar') {
    $ticket_id = $_POST['id'] ?? null;
    $observaciones = $_POST['observaciones'] ?? '';
    
    if ($ticket_id) {
        foreach ($data['tickets'] as &$ticket) {
            if ($ticket['id'] === $ticket_id && $ticket['atendido_por'] === $_SESSION['user']) {
                $ticket['estado'] = 'atendido';
                $ticket['observaciones'] = clean($observaciones);
                $ticket['hora_salida'] = date("H:i:s");
                updateData($data);
                echo json_encode(["success" => true, "message" => "Atención finalizada"]);
                exit;
            }
        }
    }
    echo json_encode(["success" => false, "message" => "Ticket no encontrado o no autorizado"]);
    exit;
}

// ACCIÓN: MARCAR COMO NO PRESENTADO
if ($action === 'no_presentado') {
    $ticket_id = $_POST['id'] ?? null;
    
    if ($ticket_id) {
        foreach ($data['tickets'] as &$ticket) {
            if ($ticket['id'] === $ticket_id && $ticket['atendido_por'] === $_SESSION['user']) {
                $ticket['estado'] = 'no_presentado';
                $ticket['hora_salida'] = date("H:i:s");
                updateData($data);
                echo json_encode(["success" => true, "message" => "Marcado como no presentado"]);
                exit;
            }
        }
    }
    echo json_encode(["success" => false, "message" => "Ticket no encontrado o no autorizado"]);
    exit;
}

// ACCIONES ANTIGUAS (compatibilidad)
if ($action === 'finish' || $action === 'cancel') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['id'])) {
        foreach ($data['tickets'] as &$ticket) {
            if ($ticket['id'] === $input['id']) {
                $ticket['estado'] = ($action === 'finish') ? 'atendido' : 'anulado';
                $ticket['observaciones'] = clean($input['obs'] ?? '');
                updateData($data);
                echo json_encode(["success" => true, "status" => "ok"]);
                exit;
            }
        }
    }
}

if ($action === 'reset_all') {
    $file = getDailyFile();
    file_put_contents($file, json_encode(["stats" => ["total"=>0, "atendidos"=>0], "tickets" => []], JSON_PRETTY_PRINT));
    echo json_encode(["success" => true, "status" => "ok"]);
    exit;
}

echo json_encode(["success" => false, "error" => "Acción no encontrada"]);