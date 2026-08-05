<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validación rápida por si la función no está definida en includes previas
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
    
    <!-- Favicon para la pestaña -->
    <link rel="icon" type="image/png" href="uploads/bernardi/logo-b.png">
    
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Estilos para estado deshabilitado en botones */
        .btn-disabled {
            background-color: #334155 !important;
            color: #64748b !important;
            border-color: #334155 !important;
            cursor: not-allowed !important;
            opacity: 0.6;
            pointer-events: none;
            text-decoration: none;
        }

        .header-logo-img {
            height: 45px;
            width: auto;
            display: block;
            object-fit: contain;
        }
    </style>
</head>
<body>

<header class="navbar-main">
    <div class="navbar-container">
        <!-- Brand Logo con Imagen -->
        <a href="index.php" class="brand-logo">
            <img src="uploads/bernardi/logo-b.png" alt="Logo Bernardi" class="header-logo-img" />
            <span class="logo-sub">Flotillas</span>
        </a>

        <!-- Botón Hamburguesa (Solo visible en Celular) -->
        <button class="hamburger-btn" id="hamburgerBtn" aria-label="Menu">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>

        <!-- Navegación -->
        <nav class="navbar-menu" id="navbarMenu">
            <div class="nav-links">
                <a href="index.php" class="nav-item">Catálogo</a>
                
                <?php if ($isAdmin): ?>
                    <a href="agregar_vehiculo.php" class="nav-btn nav-btn-outline">+ Agregar Vehículo</a>
                    <a href="agregar_usuario.php" class="nav-btn nav-btn-blue">+ Agregar Usuario</a>
                <?php else: ?>
                    <a href="#" class="nav-btn nav-btn-outline btn-disabled">+ Agregar Vehículo</a>
                    <a href="#" class="nav-btn nav-btn-blue btn-disabled">+ Agregar Usuario</a>
                <?php endif; ?>
            </div>

            <div class="nav-user">
                <span class="user-name"><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?></span>
                <a href="logout.php" class="logout-link">Cerrar sesión</a>
            </div>
        </nav>
    </div>
</header>
