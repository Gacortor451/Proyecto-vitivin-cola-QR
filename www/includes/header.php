<?php
require_once __DIR__ . '/auth.php';
$rol = getRolActual();
$logueado = estaLogueado();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Trazabilidad Vitivinícola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/styles.css">
</head>

<body>
<header class="header">
    <div class="header-container">

        <!-- LOGO IZQUIERDA -->
        <div class="header-logo">
            <span class="logo-text">(Su logo)</span>
        </div>

        <!-- BOTONES DERECHA -->
        <div class="header-buttons">
            <?php if (!$logueado): ?>
                <a href="/login.php" class="btn-header">Iniciar sesión</a>
                <a href="/registro.php" class="btn-header btn-secundario">Registrarse</a>
            <?php else: ?>
                <span class="usuario-rol">Hola, <?php echo ucfirst($rol); ?></span>
                <a href="/logout.php" class="btn-header btn-logout">Salir</a>
            <?php endif; ?>
        </div>

    </div>
</header>

<main class="contenido-principal">
