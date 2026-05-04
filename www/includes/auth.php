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
    return strtolower(trim($_SESSION['rol'] ?? 'visitante'));
}

/**
 * Devuelve true si el usuario está logueado.
 */
function estaLogueado() {
    return isset($_SESSION['usuario']);
}

/**
 * Obliga a estar logueado para acceder a una página.
 * Evita bucles si ya estamos en login.php.
 */
function requireLogin() {

    if (!estaLogueado()) {

        // Evitar bucle infinito si ya estamos en login.php
        if (basename($_SERVER['PHP_SELF']) === 'login.php') {
            return;
        }

        // Guardar la URL para redirección inteligente
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

        header("Location: /login.php");
        exit;
    }
}

/**
 * Obliga a tener uno de los roles permitidos.
 * Evita bucles si ya estamos en login.php.
 */
function requireRole($roles) {

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    $rolActual = getRolActual();

    // Si no está logueado → login
    if ($rolActual === 'visitante') {

        // Evitar bucle infinito si ya estamos en login.php
        if (basename($_SERVER['PHP_SELF']) === 'login.php') {
            return;
        }

        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: /login.php");
        exit;
    }

    // Si el rol no está permitido → acceso denegado
    if (!in_array($rolActual, $roles)) {
        header("Location: /403.php");
        exit;
    }
}

/**
 * Si el usuario tiene un rol concreto, redirige.
 */
function redirectIfRole($role, $url) {
    if (getRolActual() === strtolower($role)) {
        header("Location: $url");
        exit;
    }
}
