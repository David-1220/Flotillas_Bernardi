<?php
// =============================================
//  includes/auth_check.php — Verifica sesión activa
//  Incluir al inicio de CADA página protegida
// =============================================

if (session_status() === PHP_SESSION_NONE) {
    // 1. Configurar parámetros de la cookie ANTES de iniciar sesión
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false, // Cambiar a true si usas HTTPS en el VPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// 2. Estandarizar alias para compatibilidad (soporta user_id o usuario_id)
if (!isset($_SESSION['usuario_id']) && isset($_SESSION['user_id'])) {
    $_SESSION['usuario_id'] = $_SESSION['user_id'];
}
if (!isset($_SESSION['user_id']) && isset($_SESSION['usuario_id'])) {
    $_SESSION['user_id'] = $_SESSION['usuario_id'];
}

// 3. Validar si existe sesión activa
if (empty($_SESSION['usuario_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: /Flotillas_Bernardi/login.php');
    exit();
}

// 4. Control de inactividad (15 minutos = 900 segundos)
$inactividad = 900;
if (isset($_SESSION['ultimo_acceso'])) {
    $tiempo_transcurrido = time() - $_SESSION['ultimo_acceso'];
    if ($tiempo_transcurrido > $inactividad) {
        session_unset();
        session_destroy();
        header("Location: /Flotillas_Bernardi/login.php?msg=sesion_expirada");
        exit();
    }
}
$_SESSION['ultimo_acceso'] = time();

// 5. Regenerar ID de sesión periódicamente (cada 30 min)
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// 6. Función Helper para verificar si es Admin
if (!function_exists('esAdmin')) {
    function esAdmin() {
        $rol = $_SESSION['rol'] ?? ($_SESSION['user_role'] ?? '');
        return $rol === 'admin';
    }
}
