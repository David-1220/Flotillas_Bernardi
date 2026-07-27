<?php
// =============================================
//  auth_check.php  — Verifica sesión activa
//  Incluir al inicio de CADA página protegida
// =============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['usuario_id'])) {
    // Guardar la URL intentada para redirigir después del login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: /proyecto_autos/login.php');
    exit();
}

// Regenerar ID de sesión periódicamente (cada 30 min)
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Si no existe la sesión, redirige de inmediato a la página de login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /proyecto_autos/login.php");
    exit(); // Detiene la ejecución para que no se renderice nada más
}

// Función para verificar si el usuario es Admin
function esAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

session_set_cookie_params([
    'lifetime' => 0           // Expira al cerrar el navegador/pestaña
    //'path'     => '/',          // Disponible en todo el sitio
    //'domain'   => '',           // Dominio o IP actual
    //'secure'   => false,        // Cambiar a 'true' si usas HTTPS (Certbot)
    //'httponly' => true,         // Protege contra ataques XSS (no accesible por JavaScript)
    //'samesite' => 'Lax'         // Protección contra CSRF
]);