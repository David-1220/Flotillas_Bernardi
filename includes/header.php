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
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="uploads/bernardi/logo-b.png">
    
    <!-- Hoja de estilos global -->
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">

<style>
        /* RESET & REGLAS DIRECTAS PARA EVITAR PROBLEMAS DE CACHÉ */
        .app-header {
            background-color: #0b1329 !important;
            border-bottom: 1px solid #1e293b !important;
            padding: 12px 24px !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 1000 !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        .header-container {
            max-width: 1200px !important;
            margin: 0 auto !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            position: relative !important;
        }

        /* LOGO CON TAMAÑO CONTROLADO FIRMEMENTE */
        .header-brand {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            text-decoration: none !important;
        }

        .header-logo-img {
            height: 40px !important;
            max-height: 40px !important;
            width: auto !important;
            max-width: 180px !important;
            display: block !important;
            object-fit: contain !important;
        }

        .brand-title {
            color: #ffffff !important;
            font-size: 1.15rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.5px !important;
        }

        /* NAVEGACIÓN EN ESCRITORIO */
        .header-nav {
            display: flex !important;
            align-items: center !important;
            gap: 20px !important;
        }

        .nav-links {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
        }

        .nav-link {
            color: #8ba0b4 !important;
            text-decoration: none !important;
            font-size: 0.9rem !important;
            font-weight: 500 !important;
            padding: 6px 12px !important;
            border-radius: 6px !important;
            transition: all 0.2s ease !important;
        }

        .nav-link:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.05) !important;
        }

        /* BOTONES DE LA CABECERA */
        .btn-head {
            text-decoration: none !important;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            padding: 8px 14px !important;
            border-radius: 6px !important;
            transition: all 0.2s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .btn-head.outline {
            color: #e2e8f0 !important;
            border: 1px solid #253545 !important;
            background: transparent !important;
        }

        .btn-head.outline:hover {
            border-color: #1a73e8 !important;
            color: #1a73e8 !important;
        }

        .btn-head.primary {
            background: #1a73e8 !important;
            color: #ffffff !important;
        }

        .btn-head.primary:hover {
            background: #1558b0 !important;
        }

        /* SECCIÓN USUARIO */
        .user-section {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            border-left: 1px solid #253545 !important;
            padding-left: 18px !important;
        }

        .user-display {
            color: #f1f5f9 !important;
            font-size: 0.88rem !important;
            font-weight: 500 !important;
        }

        .btn-logout {
            color: #f87171 !important;
            font-size: 0.82rem !important;
            font-weight: 600 !important;
            text-decoration: none !important;
            padding: 5px 10px !important;
            border: 1px solid rgba(248, 113, 113, 0.3) !important;
            border-radius: 6px !important;
            transition: all 0.2s ease !important;
        }

        .btn-logout:hover {
            background: #f87171 !important;
            color: #ffffff !important;
        }

        /* BOTÓN HAMBURGUESA (MÓVIL) */
        .hamburger-btn {
            display: none !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            width: 26px !important;
            height: 18px !important;
            background: transparent !important;
            border: none !important;
            cursor: pointer !important;
            padding: 0 !important;
        }

        .hamburger-btn .bar {
            width: 100% !important;
            height: 2px !important;
            background-color: #ffffff !important;
            border-radius: 2px !important;
        }

        /* ADAPTACIÓN MÓVIL CORREGIDA (PANTALLAS <= 768px) */
        @media (max-width: 768px) {
            .hamburger-btn {
                display: flex !important;
            }

            .header-nav {
                display: none; /* Se quita el !important para que .active pueda sobreescribirlo */
                flex-direction: column !important;
                align-items: stretch !important;
                position: absolute !important;
                top: 100% !important;
                left: -24px !important;
                right: -24px !important;
                background-color: #0b1329 !important;
                border-bottom: 2px solid #1e293b !important;
                padding: 20px 24px !important;
                gap: 16px !important;
                box-shadow: 0 10px 25px rgba(0,0,0,0.8) !important;
                z-index: 9999 !important;
            }

            /* La regla con .active toma prioridad al abrirse */
            .header-nav.active {
                display: flex !important;
            }

            .nav-links {
                flex-direction: column !important;
                width: 100% !important;
                gap: 10px !important;
            }

            .btn-head, .nav-link {
                width: 100% !important;
                text-align: center !important;
                box-sizing: border-box !important;
            }

            .user-section {
                border-left: none !important;
                border-top: 1px solid #253545 !important;
                padding-left: 0 !important;
                padding-top: 15px !important;
                flex-direction: column !important;
                gap: 10px !important;
                text-align: center !important;
            }
        }
    </style>
</head>
<body>

<header class="app-header">
    <div class="header-container">
        <!-- Logo -->
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

        <!-- Navegación -->
        <div class="header-nav" id="headerNav">
            <div class="nav-links">
                <a href="index.php" class="nav-link">Catálogo</a>
                <?php if ($isAdmin): ?>
                    <a href="vehiculo_alta.php" class="btn-head outline">+ Agregar Vehículo</a>
                    <a href="usuarios.php" class="btn-head primary">+ Agregar Usuario</a>
                <?php endif; ?>
            </div>

            <div class="user-section">
                <span class="user-display"><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?></span>
                <a href="logout.php" class="btn-logout">Cerrar sesión</a>
            </div>
        </div>
    </div>
</header>
