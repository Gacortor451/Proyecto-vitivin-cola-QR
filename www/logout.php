<?php
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 * Guardar ID del lote en cookie si existe en sesión
 * Esto permite recordar el lote aunque el usuario ya no esté en lote.php
 */
if (!empty($_SESSION['ultimo_lote'])) {
    setcookie('ultimo_lote', $_SESSION['ultimo_lote'], time() + 300, "/");
}

/*
 * Eliminar variables de sesión
 */
$_SESSION = [];

/*
 * Destruir la sesión
 */
session_destroy();

/*
 * Eliminar cookie de sesión
 */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

/*
 * Redirigir al login
 */
header("Location: /login.php");
exit;
