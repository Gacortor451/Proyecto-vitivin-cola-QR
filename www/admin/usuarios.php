<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Solo administradores
if (!estaLogueado() || getRolActual() !== 'admin') {
    header("Location: /login.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Obtener usuarios
$stmt = $conn->query("
    SELECT u.id, u.nombre, u.email, r.nombre AS rol
    FROM usuarios u
    JOIN roles r ON r.id = u.id_rol
    ORDER BY u.id ASC
");
$usuarios = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_topbar.php';
?>

<!-- CONTENIDO -->
<div class="admin-contenido">

    <h1 class="admin-titulo">Gestión de Usuarios</h1>

    <a href="/admin/usuario_nuevo.php" class="admin-btn-crear">+ Crear usuario</a>

    <table class="admin-tabla">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?php echo $u['id']; ?></td>
                    <td><?php echo htmlspecialchars($u['nombre'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($u['rol'] ?? ''); ?></td>

                    <td>
                        <a href="/admin/usuario_editar.php?id=<?php echo $u['id']; ?>" 
                           class="admin-btn-tabla editar">Editar rol</a>

                        <a href="/admin/usuario_eliminar.php?id=<?php echo $u['id']; ?>" 
                           class="admin-btn-tabla eliminar"
                           onclick="return confirm('¿Seguro que deseas eliminar este usuario?');">
                           Eliminar
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
