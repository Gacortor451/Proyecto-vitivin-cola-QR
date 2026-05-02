<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Solo admin
if (!estaLogueado() || getRolActual() !== 'admin') {
    header("Location: /login.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Obtener todos los lotes
$stmt = $conn->query("SELECT id, codigo_lote, qr_url FROM lotes ORDER BY id ASC");
$lotes = $stmt->fetchAll();
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<h1 class="personal-titulo">Panel de Administración — Lotes</h1>

<table class="tabla-lotes">
    <tr>
        <th>ID</th>
        <th>Código de lote</th>
        <th>QR</th>
        <th>Acciones</th>
    </tr>

    <?php foreach ($lotes as $l): ?>
        <tr>
            <td><?php echo $l['id']; ?></td>
            <td><?php echo htmlspecialchars($l['codigo_lote']); ?></td>

            <td>
                <?php if (!empty($l['qr_url'])): ?>
                    <img src="<?php echo $l['qr_url']; ?>" width="120">
                <?php else: ?>
                    <span>No generado</span>
                <?php endif; ?>
            </td>

            <td>
                <a href="/personal_editar_lote.php?id=<?php echo $l['id']; ?>">Editar</a>
                |
                <a href="/personal_lote.php?id=<?php echo $l['id']; ?>">Ver trazabilidad</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
