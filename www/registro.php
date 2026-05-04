<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

// Si ya está logueado, no debe registrarse
if (estaLogueado()) {

    switch (getRolActual()) {
        case 'admin':
            header("Location: /admin/admin.php");
            break;

        case 'auditor':
            header("Location: /auditor/auditor.php");
            break;

        case 'empleado':
            header("Location: /personal.php");
            break;

        case 'usuario':
            header("Location: /lote.php");
            break;

        default:
            header("Location: /index.php");
            break;
    }

    exit;
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password2 = trim($_POST['password2'] ?? '');

    // ============================
    // VALIDACIONES
    // ============================

    if ($nombre === '' || $email === '' || $password === '' || $password2 === '') {
        $errores[] = "Debes rellenar todos los campos.";
    }

    // Validación del nombre
    if (!preg_match('/^[\p{L} ]+$/u', $nombre)) {
        $errores[] = "El nombre solo puede contener letras y espacios.";
    }

    if (str_word_count($nombre) < 2) {
        $errores[] = "Debes introducir tu nombre completo.";
    }

    // Email válido
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido.";
    }

    // Contraseñas iguales
    if ($password !== $password2) {
        $errores[] = "Las contraseñas no coinciden.";
    }

    if (empty($errores)) {

        // Conexión BD
        $db = new Database();
        $conn = $db->getConnection();

        // Comprobar si el email ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);

        if ($stmt->fetch()) {
            $errores[] = "Ya existe un usuario registrado con ese correo.";
        } else {

            // Obtener ID del rol "usuario"
            $stmt = $conn->prepare("SELECT id FROM roles WHERE LOWER(nombre) = 'usuario' LIMIT 1");
            $stmt->execute();
            $idRolUsuario = $stmt->fetchColumn();

            if (!$idRolUsuario) {
                $errores[] = "Error interno: no existe el rol 'usuario'.";
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
                    ':rol' => $idRolUsuario
                ]);

                // Redirigir automáticamente al login
                header("Location: /login.php?registro=ok");
                exit;
            }
        }
    }
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="login-contenedor">

    <h1 class="login-titulo">Crear cuenta</h1>

    <?php if (!empty($errores)): ?>
        <div class="login-error">
            <?php foreach ($errores as $e): ?>
                <p><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" class="login-form">

        <label for="nombre">Nombre completo</label>
        <input type="text" name="nombre" id="nombre" required>

        <label for="email">Correo electrónico</label>
        <input type="email" name="email" id="email" required>

        <label for="password">Contraseña</label>
        <input type="password" name="password" id="password" required>

        <label for="password2">Repetir contraseña</label>
        <input type="password" name="password2" id="password2" required>

        <button type="submit" class="btn-login">Registrarse</button>

        <p class="login-registro">
            ¿Ya tienes cuenta? <a href="/login.php">Inicia sesión aquí</a>
        </p>
    </form>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
