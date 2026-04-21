<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Devuelve el rol actual del usuario.
 * Si no está logueado → visitante
 */
function getRolActual() {
    return $_SESSION['rol'] ?? 'visitante';
}

/**
 * Devuelve true si el usuario está logueado.
 */
function estaLogueado() {
    return isset($_SESSION['usuario']);
}

/**
 * Obliga a estar logueado para acceder a una página.
 * Si no lo está:
 *   - Guarda la URL original
 *   - Redirige a login
 */
function requireLogin() {
    if (!estaLogueado()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: /login.php");
        exit;
    }
}

/**
 * Obliga a tener un rol específico.
 * Ejemplo: requireRole('admin');
 */
function requireRole($rolNecesario) {
    $rolActual = strtolower(getRolActual());
    $rolNecesario = strtolower($rolNecesario);

    if ($rolActual !== $rolNecesario) {
        // Guardar URL original para volver después del login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: /login.php");
        exit;
    }
}
