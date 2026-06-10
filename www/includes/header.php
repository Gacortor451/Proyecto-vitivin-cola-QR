<?php
require_once __DIR__ . '/auth.php';

$logueado = estaLogueado();

// Detectar ID del lote en cualquier página
$id_lote = isset($_GET['id']) ? intval($_GET['id']) : null;
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
            <a href="/index.php">
                <img src="/img/logo.png" alt="Logo" class="logo-img">
            </a>
        </div>

        <!-- BOTONES DERECHA -->
        <div class="header-buttons">
            <?php if (!$logueado): ?>
                <a href="/login.php" class="btn-header">Iniciar sesión</a>
                <a href="/registro.php" class="btn-header btn-secundario">Registrarse</a>
            <?php else: ?>

                <?php if ($id_lote): ?>
                    <!-- Logout con ID del lote -->
                    <a href="/logout.php?id=<?php echo $id_lote; ?>" class="btn-header btn-logout">Salir</a>
                <?php else: ?>
                    <!-- Logout normal -->
                    <a href="/logout.php" class="btn-header btn-logout">Salir</a>
                <?php endif; ?>

            <?php endif; ?>
        </div>

    </div>
</header>

<main class="contenido-principal">
