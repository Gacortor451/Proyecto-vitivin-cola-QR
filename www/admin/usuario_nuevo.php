<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['admin']);

require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$errores = [];
$exito = false;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $rol = intval($_POST['rol'] ?? 0);

    if ($nombre === '' || $email === '' || $password === '' || $rol === 0) {
        $errores[] = "Todos los campos son obligatorios.";
    } else {

        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
        $stmt->execute([':email' => $email]);

        if ($stmt->fetchColumn() > 0) {
            $errores[] = "El correo ya está registrado.";
        } else {

            // Insertar usuario
            $stmt = $conn->prepare("
                INSERT INTO usuarios (nombre, email, password, id_rol)
                VALUES (:nombre, :email, :password, :rol)
            ");

            $stmt->execute([
                ':nombre' => $nombre,
                ':email' => $email,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':rol' => $rol
            ]);

            $exito = true;
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_topbar.php';
?>

<!-- CONTENIDO -->
<div class="admin-contenido">

    <h1 class="admin-titulo">Crear nuevo usuario</h1>

    <?php if ($exito): ?>
        <p class="admin-exito">Usuario creado correctamente.</p>
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
            <input type="text" name="nombre" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Contraseña</label>
            <input type="password" name="password" required>

            <label>Rol</label>
            <select name="rol" required>
                <option value="">Seleccionar rol</option>
                <option value="1">Administrador</option>
                <option value="2">Auditor</option>
                <option value="3">Empleado</option>
                <option value="4">Usuario</option>
            </select>

            <button type="submit" class="admin-btn-guardar">Crear usuario</button>

        </form>

    <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
