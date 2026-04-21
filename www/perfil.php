<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

// Si no está logueado, fuera
if (!estaLogueado()) {
    header("Location: /login.php");
    exit;
}

$idUsuario = $_SESSION['usuario'];

// Conexión BD
$db = new Database();
$conn = $db->getConnection();

// Obtener datos del usuario
$stmt = $conn->prepare("
    SELECT u.nombre, u.email, r.nombre AS rol
    FROM usuarios u
    JOIN roles r ON r.id = u.id_rol
    WHERE u.id = :id
");
$stmt->execute([':id' => $idUsuario]);
$usuario = $stmt->fetch();

if (!$usuario) {
    die("Error: usuario no encontrado.");
}

?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="perfil-contenedor">

    <h1 class="perfil-titulo">Mi perfil</h1>

    <div class="perfil-card">

        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($usuario['nombre']); ?></p>

        <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>

        <p><strong>Rol:</strong> <?php echo htmlspecialchars(ucfirst($usuario['rol'])); ?></p>

        <div class="perfil-acciones">
            <a href="/logout.php" class="btn-logout-perfil">Cerrar sesión</a>
        </div>

    </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
