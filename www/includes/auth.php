<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Devuelve el rol actual del usuario usando el ID del rol.
 * 1 = admin
 * 2 = auditor
 * 3 = empleado
 * 4 = usuario
 */
function getRolActual() {

    $id = $_SESSION['rol_id'] ?? null;

    return match($id) {
        1 => 'admin',
        2 => 'auditor',
        3 => 'empleado',
        4 => 'usuario',
        default => 'visitante'
    };
}

/**
 * Devuelve true si el usuario está logueado.
 */
function estaLogueado() {
    return isset($_SESSION['usuario']);
}

/**
 * Obliga a estar logueado para acceder a una página.
 */
function requireLogin() {

    if (!estaLogueado()) {

        if (basename($_SERVER['PHP_SELF']) === 'login.php') {
            return;
        }

        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

        header("Location: /login.php");
        exit;
    }
}

/**
 * Obliga a tener uno de los roles permitidos.
 */
function requireRole($roles) {

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    $rolActual = getRolActual();

    if ($rolActual === 'visitante') {

        if (basename($_SERVER['PHP_SELF']) === 'login.php') {
            return;
        }

        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: /login.php");
        exit;
    }

    if (!in_array($rolActual, $roles)) {
        header("Location: /403.php");
        exit;
    }
}

/**
 * Redirige si el usuario tiene un rol concreto.
 */
function redirectIfRole($role, $url) {
    if (getRolActual() === strtolower($role)) {
        header("Location: $url");
        exit;
    }
}

/**
 * Página 404
 */
function error404() {
    http_response_code(404);
    include __DIR__ . '/../404.php';
    exit;
}

/**
 * Página 403
 */
function error403() {
    http_response_code(403);
    include __DIR__ . '/../403.php';
    exit;
}
