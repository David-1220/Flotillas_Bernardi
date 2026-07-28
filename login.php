<?php

// MOSTRAR ERRORES REALES DE PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =============================================
//  login.php  — Autenticación de usuarios
// =============================================

require_once 'includes/config.php';
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Redirigir si ya hay sesión
if (!empty($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password =       $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Por favor, ingresa usuario y contraseña.';
    } else {
        try {
            $db   = getDB();
            $stmt = $db->prepare("SELECT id, username, password_hash, nombre, rol FROM usuarios WHERE username = :u LIMIT 1");
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['usuario_id']     = $user['id'];
                $_SESSION['user_id']        = $user['id'];
                $_SESSION['usuario_nombre'] = $user['nombre'] ?? $user['username'];
                
                // AQUI SE GUARDA EN LA SESION:
                $_SESSION['rol']            = $user['rol']; 

                $_SESSION['last_regeneration'] = time();

                $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect);
                exit();
            
          } else {
              $error = 'Usuario o contraseña incorrectos.';
          }
        } catch (PDOException $e) {
            $error = 'Error de conexión. Intenta más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Iniciar sesión — Flotillas</title>
  <link rel="stylesheet" href="css/style.css" />
  <style>
    body { justify-content: center; align-items: center; }
    .login-box {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 2.5rem;
      width: 100%;
      max-width: 380px;
      box-shadow: var(--shadow);
    }
    .login-logo { text-align:center; margin-bottom:.5rem; }
    .login-logo img {
      max-width: 200px; /* Cambia este valor para hacer la imagen más grande o pequeña */
      height: auto;
      display: inline-block;
    }
    .login-title { text-align:center; font-size:1.3rem; font-weight:700; margin-bottom:.3rem; }
    .login-sub { text-align:center; font-size:.85rem; color:var(--text-muted); margin-bottom:1.8rem; }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="login-logo">
      <img src="uploads/bernardi/logo-b.png" alt="Logo Bernardi" />
    </div>
    <div class="login-title">Flotillas Bernardi</div>
    <div class="login-sub">Sistema de gestión de Flotillas Bernardi</div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="form-group" style="margin-bottom:1rem;">
        <label for="username">Usuario</label>
        <input type="text" id="username" name="username"
               placeholder="tu_usuario" autocomplete="username" required
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" />
      </div>
      <div class="form-group" style="margin-bottom:1.5rem;">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password"
               placeholder="••••••••" autocomplete="current-password" required />
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
        Iniciar sesión
      </button>
    </form>
  </div>
</body>
</html>
