<?php
session_start();
$_SESSION['rol'] = 'visitante';
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<section style="padding: 2rem;">
    <h1>Prueba del Header Responsive</h1>
    <p>Reduce el tamaño de la ventana o abre esta página desde tu móvil para ver el menú hamburguesa.</p>

    <p>Puedes cambiar el rol en la parte superior del archivo <code>test-header.php</code> para ver cómo cambia el footer.</p>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
