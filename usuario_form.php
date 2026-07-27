<?php
// =============================================
//  usuario_form.php — Crear / Modificar Usuario
// =============================================

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth_check.php';

if (!esAdmin()) {
    header('Location: index.php');
    exit();
}

$db = getDB();
$error = '';
$id = $_GET['id'] ?? null;
$es_edicion = !empty($id);

$usuario = [
    'nombre'   => '',
    'username' => '',
    'email'    => '',
    'rol'      => 'chofer'
];

// Cargar datos si estamos en modo edición
if ($es_edicion) {
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $uBD = $stmt->fetch();

    if ($uBD) {
        $usuario = $uBD;
    } else {
        header('Location: usuarios.php');
        exit();
    }
}

// Procesar Formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol      = $_POST['rol'] ?? 'chofer';

    if (empty($nombre) || empty($username)) {
        $error = 'El nombre completo y el nombre de usuario son obligatorios.';
    } else {
        try {
            // Verificar que el username no esté en uso por otro usuario
            $stmtUserCheck = $db->prepare("SELECT id FROM usuarios WHERE username = :username AND id != :id LIMIT 1");
            $stmtUserCheck->execute([':username' => $username, ':id' => $id ?? 0]);

            if ($stmtUserCheck->fetch()) {
                $error = 'El nombre de usuario "' . htmlspecialchars($username) . '" ya pertenece a otra cuenta.';
            } else {
                if ($es_edicion) {
                    // ACTUALIZACIÓN
                    if (!empty($password)) {
                        // Si escribió nueva contraseña, la actualizamos
                        $passHash = password_hash($password, PASSWORD_DEFAULT);
                        $sql = "UPDATE usuarios SET nombre = :nombre, username = :username, email = :email, password_hash = :pass, rol = :rol WHERE id = :id";
                        $params = [':nombre' => $nombre, ':username' => $username, ':email' => $email, ':pass' => $passHash, ':rol' => $rol, ':id' => $id];
                    } else {
                        // Mantener la contraseña actual
                        $sql = "UPDATE usuarios SET nombre = :nombre, username = :username, email = :email, rol = :rol WHERE id = :id";
                        $params = [':nombre' => $nombre, ':username' => $username, ':email' => $email, ':rol' => $rol, ':id' => $id];
                    }
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);

                    header("Location: usuarios.php?msg=usuario_actualizado");
                    exit();
                } else {
                    // CREACIÓN
                    if (empty($password)) {
                        $error = 'Debes asignar una contraseña al nuevo usuario.';
                    } else {
                        $passHash = password_hash($password, PASSWORD_DEFAULT);
                        $sql = "INSERT INTO usuarios (nombre, username, email, password_hash, rol) VALUES (:nombre, :username, :email, :pass, :rol)";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            ':nombre'   => $nombre,
                            ':username' => $username,
                            ':email'    => $email,
                            ':pass'     => $passHash,
                            ':rol'      => $rol
                        ]);

                        header("Location: usuarios.php?msg=usuario_creado");
                        exit();
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Error en la base de datos: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<main class="page-wrapper">
  <div style="margin-bottom: 1rem;">
    <a href="usuarios.php" class="btn btn-outline btn-sm">← Volver a Usuarios</a>
  </div>

  <div class="card" style="max-width: 500px; margin: 0 auto;">
    <div class="page-header" style="margin-bottom: 1.2rem;">
      <h1>👤 <?= $es_edicion ? 'Editar Usuario' : 'Nuevo Usuario' ?></h1>
      <p><?= $es_edicion ? 'Actualiza los datos o permisos de la cuenta.' : 'Registra un nuevo usuario para el sistema.' ?></p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="form-grid" style="grid-template-columns: 1fr;">
      <div class="form-group">
        <label for="nombre">Nombre Completo *</label>
        <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? $usuario['nombre']) ?>" required />
      </div>

      <div class="form-group">
        <label for="username">Nombre de Usuario (Login) *</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? $usuario['username']) ?>" required />
      </div>

      <div class="form-group">
        <label for="email">Correo Electrónico</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $usuario['email']) ?>" />
      </div>

      <div class="form-group">
        <label for="password">Contraseña <?= $es_edicion ? '(Dejar en blanco para no cambiar)' : '*' ?></label>
        <input type="password" id="password" name="password" placeholder="••••••••" <?= $es_edicion ? '' : 'required' ?> />
      </div>

      <div class="form-group">
        <label for="rol">Rol de Sistema *</label>
        <select id="rol" name="rol" required>
          <option value="chofer" <?= (($usuario['rol'] ?? '') === 'chofer') ? 'selected' : '' ?>>Chofer / Usuario Estándar</option>
          <option value="admin" <?= (($usuario['rol'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrador</option>
        </select>
      </div>

      <div class="form-group btn-actions" style="margin-top: 1rem;">
        <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">
          💾 <?= $es_edicion ? 'Guardar Cambios' : 'Crear Usuario' ?>
        </button>
        <a href="usuarios.php" class="btn btn-outline">Cancelar</a>
      </div>
    </form>
  </div>
</main>

<?php include 'includes/footer.php'; ?>