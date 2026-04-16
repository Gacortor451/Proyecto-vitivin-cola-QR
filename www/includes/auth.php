<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getRolActual() {
    return $_SESSION['rol'] ?? 'visitante';
}

function estaLogueado() {
    return isset($_SESSION['usuario']);
}
