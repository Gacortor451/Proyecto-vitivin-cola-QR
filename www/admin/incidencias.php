<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Solo administradores
if (!estaLogueado() || getRolActual() !== 'admin') {
    header("Location: /login.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Incidencias pendientes
$stmt = $conn->query("
    SELECT i.*, l.codigo_lote, u.nombre AS creador
    FROM incidencias i
    JOIN lotes l ON l.id = i.id_lote
    JOIN usuarios u ON u.id = i.id_usuario_creador
    WHERE i.estado = 'Pendiente'
    ORDER BY i.fecha DESC
");
$pendientes = $stmt->fetchAll();

// Incidencias resueltas
$stmt = $conn->query("
    SELECT i.*, l.codigo_lote, u.nombre AS creador
    FROM incidencias i
    JOIN lotes l ON l.id = i.id_lote
    JOIN usuarios u ON u.id = i.id_usuario_creador
    WHERE i.estado = 'Resuelto'
    ORDER BY i.fecha DESC
");
$resueltas = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- NUEVA BARRA SUPERIOR -->
<div class="admin-topbar">
    <span><strong>Administrador</strong></span>

    <a href="/admin/admin.php">Dashboard</a>
    <a href="/admin/incidencias.php" class="activo">Incidencias</a>
    <a href="/admin/usuarios.php">Usuarios</a>

    <a href="/logout.php" class="salir">Salir</a>
</div>

<!-- CONTENIDO -->
<div class="admin-contenido">

    <h1 class="admin-titulo">Gestión de Incidencias</h1>

    <!-- INCIDENCIAS PENDIENTES -->
    <h2 class="admin-subtitulo">Incidencias pendientes</h2>

    <?php if (empty($pendientes)): ?>
        <p>No hay incidencias pendientes.</p>
    <?php else: ?>
        <div class="admin-lista-incidencias">
            <?php foreach ($pendientes as $inc): ?>
                <div class="incidencia-card-admin pendiente">

                    <div>
                        <strong>Lote:</strong> 
                        <?php echo htmlspecialchars($inc['codigo_lote'] ?? ''); ?><br>

                        <strong>Fecha:</strong> 
                        <?php echo htmlspecialchars($inc['fecha'] ?? ''); ?><br>

                        <strong>Creada por:</strong> 
                        <?php echo htmlspecialchars($inc['creador'] ?? ''); ?><br>

                        <strong>Descripción:</strong><br>
                        <?php echo nl2br(htmlspecialchars($inc['descripcion'] ?? '')); ?>
                    </div>

                    <a href="/admin/incidencia_resolver.php?id=<?php echo $inc['id']; ?>" 
                       class="admin-btn-resolver">
                       Resolver
                    </a>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- INCIDENCIAS RESUELTAS -->
    <h2 class="admin-subtitulo" style="margin-top: 2rem;">Incidencias resueltas</h2>

    <?php if (empty($resueltas)): ?>
        <p>No hay incidencias resueltas.</p>
    <?php else: ?>
        <div class="admin-lista-incidencias">
            <?php foreach ($resueltas as $inc): ?>
                <div class="incidencia-card-admin resuelta">

                    <div>
                        <strong>Lote:</strong> 
                        <?php echo htmlspecialchars($inc['codigo_lote'] ?? ''); ?><br>

                        <strong>Fecha:</strong> 
                        <?php echo htmlspecialchars($inc['fecha'] ?? ''); ?><br>

                        <strong>Creada por:</strong> 
                        <?php echo htmlspecialchars($inc['creador'] ?? ''); ?><br>

                        <strong>Descripción:</strong><br>
                        <?php echo nl2br(htmlspecialchars($inc['descripcion'] ?? '')); ?>
                    </div>

                    <span class="admin-badge-resuelto">Resuelto</span>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
