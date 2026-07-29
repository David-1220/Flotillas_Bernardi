<?php
// ===================================================
//   uso_registrar.php — Registro de Bitácora de Uso
// ===================================================

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth_check.php';

$db = getDB();
$usuario_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// --- ACCIÓN: REGISTRAR SALIDA (CHECK-OUT) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'salida') {
    $vehiculo_id   = (int)$_POST['vehiculo_id'];
    $km_salida     = (int)$_POST['km_salida'];
    $motivo_salida = trim($_POST['motivo_salida']);

    // 1. Insertar registro en la bitácora
    $stmt = $db->prepare("
        INSERT INTO bitacora_uso (vehiculo_id, usuario_id, km_salida, motivo_salida, estado) 
        VALUES (:vehiculo_id, :usuario_id, :km_salida, :motivo, 'en_transito')
    ");
    $stmt->execute([
        'vehiculo_id' => $vehiculo_id,
        'usuario_id'  => $usuario_id,
        'km_salida'   => $km_salida,
        'motivo'      => $motivo_salida
    ]);

    // 2. Marcar vehículo en uso
    $stmtAuto = $db->prepare("UPDATE vehiculos SET en_uso_por = :usuario_id WHERE id = :id");
    $stmtAuto->execute(['usuario_id' => $usuario_id, 'id' => $vehiculo_id]);

    header("Location: vehiculo_detalle.php?id=" . $vehiculo_id);
    exit;
}

// --- ACCIÓN: REGISTRAR DEVOLUCIÓN (CHECK-IN) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'entrada') {
    $bitacora_id   = (int)$_POST['bitacora_id'];
    $vehiculo_id   = (int)$_POST['vehiculo_id'];
    $km_entrada    = (int)$_POST['km_entrada'];
    $observaciones = trim($_POST['observaciones_entrada']);

    // 1. Cerrar bitácora
    $stmt = $db->prepare("
        UPDATE bitacora_uso 
        SET fecha_entrada = NOW(), km_entrada = :km_entrada, observaciones_entrada = :obs, estado = 'finalizado'
        WHERE id = :id AND usuario_id = :usuario_id
    ");
    $stmt->execute([
        'km_entrada' => $km_entrada,
        'obs'        => $observaciones,
        'id'         => $bitacora_id,
        'usuario_id' => $usuario_id
    ]);

    // 2. Liberar vehículo y actualizar su kilometraje
    $stmtAuto = $db->prepare("
        UPDATE vehiculos 
        SET en_uso_por = NULL, kilometraje_actual = :km 
        WHERE id = :id
    ");
    $stmtAuto->execute(['km' => $km_entrada, 'id' => $vehiculo_id]);

    header("Location: vehiculo_detalle.php?id=" . $vehiculo_id);
    exit;
}
?>