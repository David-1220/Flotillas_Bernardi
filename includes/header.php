<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('esAdmin')) {
    function esAdmin() {
        return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
    }
}

$isAdmin = esAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bernardi — Flotilla de Vehículos</title>
    <link rel="icon" type="image/png" href="uploads/bernardi/logo-b.png">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="app-header">
    <div class="header-container">
        <!-- Logo Bernardi -->
        <a href="index.php" class="header-brand">
            <img src="uploads/bernardi/logo-b.png" alt="Logo Bernardi" class="header-logo-img" />
            <span class="brand-title">Flotillas</span>
        </a>

        <!-- Botón Hamburguesa Móvil -->
        <button class="hamburger-btn" id="hamburgerBtn" aria-label="Abrir menú">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>

        <!-- Menú de Navegación -->
        <div class="header-nav" id="headerNav">
            <div class="nav-links">
                <a href="index.php" class="nav-link">Catálogo</a>
                <?php if ($isAdmin): ?>
                    <a href="agregar_vehiculo.php" class="btn-head outline">+ Agregar Vehículo</a>
                    <a href="agregar_usuario.php" class="btn-head primary">+ Agregar Usuario</a>
                <?php endif; ?>
            </div>

            <div class="user-section">
                <span class="user-display"><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?></span>
                <a href="logout.php" class="btn-logout">Cerrar sesión</a>
            </div>
        </div>
    </div>
</header>
