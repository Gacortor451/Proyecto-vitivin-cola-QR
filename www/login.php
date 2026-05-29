<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$errores = [];

/**
 * 0) Recuperar redirect_after_login desde cookie si existe
 *    (logout.php la guarda cuando auditor/admin cierran sesión)
 */
if (empty($_SESSION['redirect_after_login']) && !empty($_COOKIE['redirect_after_login'])) {
    $_SESSION['redirect_after_login'] = $_COOKIE['redirect_after_login'];
    setcookie('redirect_after_login', '', time() - 3600, "/");
}

/**
 * 1) Guardamos la cookie del último lote (QR)
 */
if (!empty($_COOKIE['ultimo_lote'])) {
    $_SESSION['redirect_after_login'] = "/lote.php?id=" . $_COOKIE['ultimo_lote'];
    setcookie('ultimo_lote', '', time() - 3600, "/");
}

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
            $rol = strtolower($stmt->fetchColumn());

            $_SESSION['rol'] = $rol;

            /**
             * 2) Redirección del QR
             *    Empleado y usuario → lote
             *    Auditor y admin → NO lote (pero NO borran el ID)
             */
            if (!empty($_SESSION['redirect_after_login'])) {

                if (in_array($rol, ['empleado', 'usuario'])) {
                    $destino = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header("Location: " . $destino);
                    exit;
                }

                // Auditor y admin ignoran el QR, pero NO lo borran
            }

            /**
             * 3) Redirección por rol
             */
            switch ($rol) {

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
