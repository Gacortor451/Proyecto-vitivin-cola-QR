<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errores[] = "Debes rellenar todos los campos.";
    } else {
        // Conexión BD
        $db = new Database();
        $conn = $db->getConnection();

        // Buscar usuario por email
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password'])) {

            // Guardar sesión
            $_SESSION['usuario'] = $usuario['id'];

            // Obtener rol
            $stmt = $conn->prepare("SELECT nombre FROM roles WHERE id = :id");
            $stmt->execute([':id' => $usuario['id_rol']]);
            $rol = $stmt->fetchColumn();

            $_SESSION['rol'] = strtolower($rol);

            // Redirigir
           if (!empty($_SESSION['redirect_after_login'])) {
            $destino = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            
           header("Location: " . $destino);
           exit;
}

// Redirección inteligente
if (!empty($_SESSION['redirect_after_login'])) {
    $destino = $_SESSION['redirect_after_login'];
    unset($_SESSION['redirect_after_login']);
    header("Location: " . $destino);
    exit;
}

// Redirección según rol
switch ($_SESSION['rol']) {
    case 'empleado':
        header("Location: /personal.php");
        break;

    case 'admin':
        header("Location: /admin.php");
        break;

    default:
        header("Location: /index.php");
        break;
}

exit;



        } else {
            $errores[] = "Credenciales incorrectas.";
        }
    }
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="login-contenedor">

    <h1 class="login-titulo">Iniciar sesión</h1>

    <?php if (!empty($errores)): ?>
        <div class="login-error">
            <?php foreach ($errores as $e): ?>
                <p><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" class="login-form">

        <label for="email">Correo electrónico</label>
        <input type="email" name="email" id="email" required>

        <label for="password">Contraseña</label>
        <input type="password" name="password" id="password" required>

        <button type="submit" class="btn-login">Entrar</button>

        <p class="login-registro">
            ¿No tienes cuenta? <a href="/registro.php">Regístrate aquí</a>
        </p>
    </form>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
