<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['admin']);

require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// ============================
// ESTADÍSTICAS
// ============================

// Incidencias pendientes
$stmt = $conn->query("SELECT COUNT(*) FROM incidencias WHERE estado = 'Pendiente'");
$inc_pendientes = $stmt->fetchColumn();

// Incidencias resueltas
$stmt = $conn->query("SELECT COUNT(*) FROM incidencias WHERE estado = 'Resuelto'");
$inc_resueltas = $stmt->fetchColumn();

// Total usuarios
$stmt = $conn->query("SELECT COUNT(*) FROM usuarios");
$total_usuarios = $stmt->fetchColumn();

// Usuarios por rol
$stmt = $conn->query("
    SELECT r.id, r.nombre, COUNT(*) AS total
    FROM usuarios u
    JOIN roles r ON r.id = u.id_rol
    GROUP BY r.id, r.nombre
    ORDER BY r.id ASC
");
$usuarios_por_rol = $stmt->fetchAll();

// Total lotes
$stmt = $conn->query("SELECT COUNT(*) FROM lotes");
$total_lotes = $stmt->fetchColumn();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_topbar.php';
?>

<!-- CONTENIDO -->
<div class="admin-contenido">

    <h1 class="admin-titulo">Resumen del sistema</h1>

    <div class="admin-grid">

        <div class="admin-card">
            <h3>Incidencias pendientes</h3>
            <p class="admin-numero"><?php echo $inc_pendientes; ?></p>
        </div>

        <div class="admin-card">
            <h3>Incidencias resueltas</h3>
            <p class="admin-numero"><?php echo $inc_resueltas; ?></p>
        </div>

        <div class="admin-card">
            <h3>Total de usuarios</h3>
            <p class="admin-numero"><?php echo $total_usuarios; ?></p>
        </div>

        <div class="admin-card">
            <h3>Total de lotes</h3>
            <p class="admin-numero"><?php echo $total_lotes; ?></p>
        </div>

    </div>

    <h2 class="admin-subtitulo">Usuarios por rol</h2>

    <div class="admin-grid-roles">
        <?php foreach ($usuarios_por_rol as $r): ?>
            <div class="admin-card-rol">
                <strong><?php echo htmlspecialchars($r['nombre']); ?></strong>
                <span><?php echo $r['total']; ?></span>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
