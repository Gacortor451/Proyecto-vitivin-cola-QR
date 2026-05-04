<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['admin']);

require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Usuario no especificado.");
}

$db = new Database();
$conn = $db->getConnection();

// Obtener usuario
$stmt = $conn->prepare("
    SELECT u.id, u.nombre, u.email, u.id_rol, r.nombre AS rol
    FROM usuarios u
    JOIN roles r ON r.id = u.id_rol
    WHERE u.id = :id
");
$stmt->execute([':id' => $id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    die("Usuario no encontrado.");
}

$errores = [];
$exito = false;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nuevo_rol = intval($_POST['rol'] ?? 0);

    if ($nuevo_rol === 0) {
        $errores[] = "Debes seleccionar un rol válido.";
    } else {

        $stmt = $conn->prepare("
            UPDATE usuarios
            SET id_rol = :rol
            WHERE id = :id
        ");

        $stmt->execute([
            ':rol' => $nuevo_rol,
            ':id' => $id
        ]);

        $exito = true;
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_topbar.php';
?>

<!-- CONTENIDO -->
<div class="admin-contenido">

    <h1 class="admin-titulo">Editar rol del usuario</h1>

    <?php if ($exito): ?>
        <p class="admin-exito">Rol actualizado correctamente.</p>
        <a href="/admin/usuarios.php" class="admin-btn-volver">Volver al listado</a>

    <?php else: ?>

        <?php if (!empty($errores)): ?>
            <div class="admin-error">
                <?php foreach ($errores as $e): ?>
                    <p><?php echo htmlspecialchars($e); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="admin-form">

            <label>Nombre</label>
            <input type="text" value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>" disabled>

            <label>Email</label>
            <input type="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" disabled>

            <label>Rol actual</label>
            <input type="text" value="<?php echo htmlspecialchars($usuario['rol'] ?? ''); ?>" disabled>

            <label>Nuevo rol</label>
            <select name="rol" required>
                <option value="">Seleccionar rol</option>
                <option value="1" <?php if ($usuario['id_rol'] == 1) echo 'selected'; ?>>Administrador</option>
                <option value="2" <?php if ($usuario['id_rol'] == 2) echo 'selected'; ?>>Auditor</option>
                <option value="3" <?php if ($usuario['id_rol'] == 3) echo 'selected'; ?>>Empleado</option>
                <option value="4" <?php if ($usuario['id_rol'] == 4) echo 'selected'; ?>>Usuario</option>
            </select>

            <button type="submit" class="admin-btn-guardar">Guardar cambios</button>

        </form>

    <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
