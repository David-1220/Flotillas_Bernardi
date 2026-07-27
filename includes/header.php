
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

<header class="app-header" style="background-color: var(--bg-card, #121e2d); border-bottom: 1px solid var(--border-color, #1e2d3d); padding: 0.8rem 1.5rem;">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        
        <!-- Logotipo / Nombre del Sistema -->
        <a href="index.php" style="display: flex; align-items: center; gap: 8px; text-decoration: none; color: #ffffff; font-size: 1.2rem; font-weight: 700;">
            🚗 Bernardi <span style="color: #2563eb;"></span>
        </a>

        <!-- Menú de Navegación -->
        <nav style="display: flex; align-items: center; gap: 0.8rem; flex-wrap: wrap;">
            <!-- Enlace al catálogo -->
            <a href="index.php" class="nav-link" style="color: var(--text-muted, #94a3b8); text-decoration: none; font-size: 0.9rem; font-weight: 500; margin-right: 0.5rem;">
                📋 Catálogo
            </a>

            <!-- BOTÓN: Agregar Vehículo -->
            <?php if ($isAdmin): ?>
                <a href="vehiculo_alta.php" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 5px;">
                    🚗 + Agregar Vehículo
                </a>
            <?php else: ?>
                <span class="btn btn-outline btn-sm btn-disabled" title="Requiere permisos de Administrador">
                    🚗 + Agregar Vehículo
                </span>
            <?php endif; ?>

            <!-- BOTÓN: Agregar Usuario -->
            <?php if ($isAdmin): ?>
                <a href="usuarios.php" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 5px;">
                    👤 + Agregar Usuario
                </a>
            <?php else: ?>
                <span class="btn btn-primary btn-sm btn-disabled" title="Requiere permisos de Administrador">
                    👤 + Agregar Usuario
                </span>
            <?php endif; ?>

            <!-- Cerrar Sesión -->
            <div class="user-area">
              👤 <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?>
              <a href="logout.php">Cerrar sesión</a>
            </div>
        </nav>

    </div>
</header>