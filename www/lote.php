<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$logueado = estaLogueado();
if (!$logueado) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
}

$rol = getRolActual();

// Obtener ID del lote desde la URL
$id_lote = $_GET['id'] ?? null;

if (!$id_lote) {
    die("Lote no especificado.");
}

// Conexión BD
$db = new Database();
$conn = $db->getConnection();

// =========================
// 1. OBTENER DATOS DEL LOTE
// =========================
$stmt = $conn->prepare("SELECT * FROM lotes WHERE id = :id");
$stmt->execute([':id' => $id_lote]);
$lote = $stmt->fetch();

if (!$lote) {
    die("Lote no encontrado.");
}

// =========================
// 2. LIKE (si está logueado)
// =========================
$yaLike = false;

if ($logueado) {
    $idUsuario = $_SESSION['usuario'];

    // ¿Ya dio like?
    $stmt = $conn->prepare("
        SELECT 1 FROM likes 
        WHERE id_lote = :lote AND id_usuario = :user
    ");
    $stmt->execute([
        ':lote' => $id_lote,
        ':user' => $idUsuario
    ]);

    $yaLike = (bool)$stmt->fetch();

    // Acción de like/unlike
    if (isset($_POST['like'])) {

        if ($yaLike) {
            // Quitar like
            $stmt = $conn->prepare("
                DELETE FROM likes 
                WHERE id_lote = :lote AND id_usuario = :user
            ");
        } else {
            // Dar like
            $stmt = $conn->prepare("
                INSERT INTO likes (id_usuario, id_lote)
                VALUES (:user, :lote)
            ");
        }

        $stmt->execute([
            ':lote' => $id_lote,
            ':user' => $idUsuario
        ]);

        header("Location: lote.php?id=" . $id_lote);
        exit;
    }
}

// =========================
// 3. OBTENER LIKES TOTALES
// =========================
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM likes WHERE id_lote = :id");
$stmt->execute([':id' => $id_lote]);
$likes = $stmt->fetch()['total'];

// =========================
// 4. COMENTARIOS (insertar)
// =========================
if ($logueado && isset($_POST['comentario'])) {

    $texto = trim($_POST['comentario']);

    if ($texto !== '') {
        $stmt = $conn->prepare("
            INSERT INTO comentarios (id_usuario, id_lote, texto)
            VALUES (:user, :lote, :texto)
        ");

        $stmt->execute([
            ':user' => $idUsuario,
            ':lote' => $id_lote,
            ':texto' => $texto
        ]);
    }

    header("Location: lote.php?id=" . $id_lote);
    exit;
}

// =========================
// 5. OBTENER COMENTARIOS
// =========================
$stmt = $conn->prepare("
    SELECT c.texto, c.fecha, u.nombre 
    FROM comentarios c
    JOIN usuarios u ON u.id = c.id_usuario
    WHERE c.id_lote = :id
    ORDER BY c.fecha DESC
");
$stmt->execute([':id' => $id_lote]);
$comentarios = $stmt->fetchAll();

// =========================
// 6. OBTENER TRAZABILIDAD
// =========================
$stmt = $conn->prepare("
    SELECT * FROM trazabilidad 
    WHERE id_lote = :id 
    ORDER BY fecha ASC
");
$stmt->execute([':id' => $id_lote]);
$trazabilidad = $stmt->fetchAll();
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="lote-contenedor">

    <!-- INFORMACIÓN DEL LOTE -->
    <section class="lote-info">
        <h1 class="lote-titulo">
            Lote: <?php echo htmlspecialchars($lote['codigo_lote']); ?>
        </h1>

        <p class="lote-descripcion">
            <?php echo nl2br(htmlspecialchars($lote['descripcion'] ?? '')); ?>
        </p>

        <div class="lote-datos">
            <p><strong>Variedad:</strong> <?php echo htmlspecialchars($lote['variedad'] ?? 'No especificado'); ?></p>
            <p><strong>Origen:</strong> <?php echo htmlspecialchars($lote['origen'] ?? 'No especificado'); ?></p>
            <p><strong>Estado:</strong> <?php echo htmlspecialchars($lote['estado'] ?? 'No especificado'); ?></p>
            <p><strong>Vendimia:</strong> <?php echo htmlspecialchars($lote['fecha_vendimia'] ?? 'No especificado'); ?></p>
        </div>

        <div class="lote-likes">
            ❤️ <?php echo $likes; ?> likes
        </div>

        <?php if ($logueado): ?>
            <form method="POST" class="like-form">
                <button type="submit" name="like" class="btn-like">
                    <?php echo $yaLike ? "💔 Quitar like" : "❤️ Me gusta"; ?>
                </button>
            </form>
        <?php else: ?>
            <p class="likes-info">(inicia sesión para dar like)</p>
        <?php endif; ?>
    </section>

    <!-- TRAZABILIDAD -->
    <section class="lote-trazabilidad">
        <h2>Trazabilidad del lote</h2>

        <?php if (empty($trazabilidad)): ?>
            <p>No hay eventos registrados.</p>
        <?php else: ?>
            <ul class="timeline">
                <?php foreach ($trazabilidad as $evento): ?>
                    <li>
                        <div class="timeline-etapa">
                            <h3><?php echo htmlspecialchars($evento['tipo_evento']); ?></h3>
                            <p><strong>Fecha:</strong> <?php echo $evento['fecha']; ?></p>
                            <?php if ($evento['descripcion']): ?>
                                <p><?php echo nl2br(htmlspecialchars($evento['descripcion'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <!-- COMENTARIOS -->
    <section class="lote-comentarios">
        <h2>Comentarios</h2>

        <?php foreach ($comentarios as $c): ?>
            <article class="comentario-card">
                <header class="comentario-header">
                    <span class="comentario-autor"><?php echo htmlspecialchars($c['nombre']); ?></span>
                    <span class="comentario-fecha"><?php echo $c['fecha']; ?></span>
                </header>
                <p class="comentario-texto">
                    <?php echo nl2br(htmlspecialchars($c['texto'])); ?>
                </p>
            </article>
        <?php endforeach; ?>

        <?php if ($logueado): ?>
            <form method="POST" class="comentario-form">
                <textarea name="comentario" placeholder="Escribe un comentario..." required></textarea>
                <button type="submit" class="btn-comentar">Publicar comentario</button>
            </form>
        <?php else: ?>
            <div class="mensaje-visitante">
                Para comentar debes <a href="/login.php">iniciar sesión</a>.
            </div>
        <?php endif; ?>
    </section>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
