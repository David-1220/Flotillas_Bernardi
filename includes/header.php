
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
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Estilos para estado deshabilitado en botones */
        .btn-disabled {
            background-color: #334155 !important;
            color: #64748b !important;
            border-color: #334155 !important;
            cursor: not-allowed !important;
            opacity: 0.6;
            pointer-events: none; /* Evita que se le dé clic */
            text-decoration: none;
        }
    </style>
</head>
<body>
    <!-- Estilos del logo dentro del header -->
<style>
  .header-logo-img {
    height: 70px; /* Ajusta la altura del logo para que quede alineado con el menú */
    width: auto;
    display: block;
    object-fit: contain;
  }
  .app-header a {
    transition: opacity 0.2s ease;
  }
  .app-header a:hover {
    opacity: 0.85;
  }
</style>

<header class="app-header" style="background-color: var(--bg-card, #121e2d); border-bottom: 1px solid var(--border-color, #1e2d3d); padding: 0.8rem 1.5rem;">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        
        <!-- Logotipo / Nombre del Sistema con Imagen -->
        <a href="index.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: #ffffff; font-size: 1.2rem; font-weight: 700;">
            <img src="uploads/bernardi/logo-b.png" alt="Logo Bernardi" class="header-logo-img" />
            <span>Flotillas</span>
        </a>

        <!-- Menú de Navegación -->
        <header class="navbar-main">
            <div class="navbar-container">
                <!-- Logo y Marca -->
                <a href="index.php" class="navbar-brand">
                    <span class="brand-logo">Bernardi</span>
                    <span class="brand-sub">Flotillas</span>
                </a>
        
                <!-- Botón Hamburguesa (solo visible en celular) -->
                <button class="hamburger-btn" id="hamburgerBtn" aria-label="Abrir menú">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </button>
        
                <!-- Contenido del Menú -->
                <div class="navbar-menu" id="navbarMenu">
                    <div class="nav-links">
                        <a href="index.php" class="btn-nav-link">Catálogo</a>
                        <a href="agregar_vehiculo.php" class="btn-nav-outline">+ Agregar Vehículo</a>
                        <a href="agregar_usuario.php" class="btn-nav-blue">+ Agregar Usuario</a>
                    </div>
        
                    <div class="nav-user">
                        <span class="user-name">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?>
                        </span>
                        <a href="logout.php" class="logout-link">Cerrar sesión</a>
                    </div>
                </div>
            </div>
        </header>

    </div>
</header>
