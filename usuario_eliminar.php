<?php
// =============================================
//  usuario_eliminar.php — Eliminar Usuario
// =============================================

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth_check.php';

if (!esAdmin()) {
    header('Location: index.php');
    exit();
}

$id = $_GET['id'] ?? null;
$usuario_actual_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;

// Protección: No permitir que un admin se elimine a sí mismo
if ($id && $id != $usuario_actual_id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header("Location: usuarios.php?msg=usuario_eliminado");
    exit();
} else {
    header("Location: usuarios.php?error=no_auto_eliminar");
    exit();
}