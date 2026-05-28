<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

// Si no está logueado → login
if (!estaLogueado()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: /login.php");
    exit;
}

// Si está logueado pero NO es empleado ni admin → 403
if (!in_array(getRolActual(), ['empleado', 'admin'])) {
    header("Location: /403.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// 1) Intentar obtener lote desde GET (QR o enlaces internos)
$id_lote = $_GET['id'] ?? null;

// 2) Si no viene por GET, intentar restaurarlo desde sesión
if (!$id_lote && isset($_SESSION['ultimo_lote'])) {
    $id_lote = $_SESSION['ultimo_lote'];
}

// 3) Si tampoco está en sesión, intentar restaurarlo desde cookie
if (!$id_lote && !empty($_COOKIE['ultimo_lote'])) {
    $id_lote = $_COOKIE['ultimo_lote'];
    $_SESSION['ultimo_lote'] = $id_lote; // restaurar en sesión
}

$lote = null;

// 4) Si tenemos un ID válido, cargar el lote
if ($id_lote) {
    $stmt = $conn->prepare("SELECT id, codigo_lote FROM lotes WHERE id = :id");
    $stmt->execute([':id' => $id_lote]);
    $lote = $stmt->fetch();

    // Si existe, guardar en sesión para mantenerlo siempre
    if ($lote) {
        $_SESSION['ultimo_lote'] = $id_lote;
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="personal-contenedor">

    <h1 class="personal-titulo">Panel del Personal</h1>

    <p class="personal-subtitulo">
        Escanea un QR para añadir trazabilidad o completar datos del lote.
    </p>

    <!-- Crear lote siempre disponible -->
    <a class="personal-lote-link" href="/personal_crear_lote.php">
        ➕ Crear nuevo lote
    </a>

    <hr>

    <?php if ($lote): ?>
        <h2 class="personal-subtitulo">Lote seleccionado</h2>

        <ul class="personal-lista-lotes">
            <li>
                <a class="personal-lote-link" href="/personal_lote.php?id=<?php echo $lote['id']; ?>">
                    ➕ Añadir trazabilidad — <?php echo htmlspecialchars($lote['codigo_lote']); ?>
                </a>

                <a class="personal-lote-link" href="/personal_editar_lote.php?id=<?php echo $lote['id']; ?>">
                    📝 Completar información — <?php echo htmlspecialchars($lote['codigo_lote']); ?>
                </a>
            </li>
        </ul>

    <?php else: ?>
        <p class="personal-subtitulo">
            No se ha seleccionado ningún lote todavía.
        </p>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
