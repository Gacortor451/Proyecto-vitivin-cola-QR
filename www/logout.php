<?php
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 * 1) Guardar SIEMPRE el ID del lote en cookie si existe en sesión
 *    Esto evita que auditor/admin borren el ID del QR
 */
if (!empty($_SESSION['ultimo_lote'])) {
    setcookie('ultimo_lote', $_SESSION['ultimo_lote'], time() + 300, "/");
}

/*
 * 2) Guardar también redirect_after_login si existe
 *    (por si el usuario venía de un QR)
 */
if (!empty($_SESSION['redirect_after_login'])) {
    setcookie('redirect_after_login', $_SESSION['redirect_after_login'], time() + 300, "/");
}

/*
 * 3) Eliminar variables de sesión
 */
$_SESSION = [];

/*
 * 4) Destruir la sesión
 */
session_destroy();

/*
 * 5) Eliminar cookie de sesión
 */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

/*
 * 6) Redirigir al login
 */
header("Location: /login.php");
exit;
