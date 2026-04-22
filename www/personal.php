<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

// Solo empleados o admin
if (!estaLogueado() || !in_array(getRolActual(), ['empleado', 'admin'])) {
    header("Location: /login.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Obtener todos los lotes
$stmt = $conn->query("SELECT id, codigo_lote FROM lotes ORDER BY id ASC");
$lotes = $stmt->fetchAll();
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="personal-contenedor">

    <h1 class="personal-titulo">Panel del Personal</h1>

    <p class="personal-subtitulo">
        Selecciona un lote para añadir información o completar datos.
    </p>

    <ul class="personal-lista-lotes">
        <?php foreach ($lotes as $l): ?>
            <li>
                <a class="personal-lote-link" href="/personal_lote.php?id=<?php echo $l['id']; ?>">
                    ➕ Añadir trazabilidad — <?php echo htmlspecialchars($l['codigo_lote']); ?>
                </a>

                <a class="personal-lote-link" href="/personal_editar_lote.php?id=<?php echo $l['id']; ?>">
                    📝 Completar información — <?php echo htmlspecialchars($l['codigo_lote']); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
