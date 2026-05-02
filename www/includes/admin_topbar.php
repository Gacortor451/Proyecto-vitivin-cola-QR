<?php
// Detectar la página actual para marcar el menú activo
$actual = basename($_SERVER['PHP_SELF']);
?>

<div class="admin-topbar">

    <a href="/admin/admin.php" 
       class="<?php echo ($actual === 'admin.php') ? 'activo' : ''; ?>">
       Resumen
    </a>

    <a href="/admin/incidencias.php" 
       class="<?php echo ($actual === 'incidencias.php') ? 'activo' : ''; ?>">
       Incidencias
    </a>

    <a href="/admin/usuarios.php" 
       class="<?php echo ($actual === 'usuarios.php') ? 'activo' : ''; ?>">
       Usuarios
    </a>

    <a href="/admin/lotes/index.php" 
       class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/lotes/') !== false) ? 'activo' : ''; ?>">
       Lotes
    </a>

</div>

