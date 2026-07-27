<?php
// =============================================
//  usuarios.php — Gestión General de Usuarios
// =============================================

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth_check.php';

if (!esAdmin()) {
    header('Location: index.php');
    exit();
}

$db = getDB();
$stmt = $db->query("SELECT id, nombre, username, email, rol FROM usuarios ORDER BY id ASC");
$usuarios = $stmt->fetchAll();

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

include 'includes/header.php';
?>

<main class="page-wrapper">
  
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
      <h1 style="font-size: 1.8rem; margin-bottom: 0.2rem;">👥 Gestión de Usuarios</h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">Administra los accesos y roles del sistema.</p>
    </div>
    <a href="usuario_form.php" class="btn btn-primary">👤 + Nuevo Usuario</a>
  </div>

  <?php if ($msg === 'usuario_creado'): ?>
    <div class="alert alert-success">¡Usuario creado exitosamente!</div>
  <?php elseif ($msg === 'usuario_actualizado'): ?>
    <div class="alert alert-success">¡Datos de usuario actualizados correctamente!</div>
  <?php elseif ($msg === 'usuario_eliminado'): ?>
    <div class="alert alert-success">Usuario eliminado de la base de datos.</div>
  <?php elseif ($error === 'no_auto_eliminar'): ?>
    <div class="alert alert-danger">⚠️ No puedes eliminar tu propia cuenta mientras estás logueado.</div>
  <?php endif; ?>

  <div class="card">
    <div class="table-wrap">
      <table style="width: 100%;">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre Completo</th>
            <th>Usuario</th>
            <th>Correo</th>
            <th>Rol</th>
            <th style="text-align: right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $u): ?>
            <tr>
              <td>#<?= $u['id'] ?></td>
              <td><b><?= htmlspecialchars($u['nombre']) ?></b></td>
              <td><code><?= htmlspecialchars($u['username']) ?></code></td>
              <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
              <td>
                <span class="badge-page" style="background: <?= $u['rol'] === 'admin' ? '#2563eb' : '#334155' ?>; color: #fff;">
                  <?= strtoupper($u['rol']) ?>
                </span>
              </td>
              <td style="text-align: right;">
                <a href="usuario_form.php?id=<?= $u['id'] ?>" class="btn btn-outline btn-sm" style="padding: 3px 8px;">✏️ Editar</a>
                
                <?php if ($u['id'] != ($_SESSION['usuario_id'] ?? $_SESSION['user_id'])): ?>
                  <a href="usuario_eliminar.php?id=<?= $u['id'] ?>" 
                     onclick="return confirm('¿Estás seguro de eliminar a <?= htmlspecialchars($u['username']) ?>?');" 
                     class="btn btn-outline btn-sm" 
                     style="padding: 3px 8px; color: #ef4444; border-color: #ef4444;">
                     🗑️ Eliminar
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>

<?php include 'includes/footer.php'; ?>