<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Solo administradores
if (!estaLogueado() || getRolActual() !== 'admin') {
    header("Location: /login.php");
    exit;
}

$id_incidencia = $_GET['id'] ?? null;

if (!$id_incidencia) {
    die("Incidencia no especificada.");
}

$db = new Database();
$conn = $db->getConnection();

// ============================
// 1. OBTENER INCIDENCIA + LOTE
// ============================
$stmt = $conn->prepare("
    SELECT i.*, l.*
    FROM incidencias i
    JOIN lotes l ON l.id = i.id_lote
    WHERE i.id = :id
");
$stmt->execute([':id' => $id_incidencia]);
$data = $stmt->fetch();

if (!$data) {
    die("Incidencia no encontrada.");
}

// Separar datos
$incidencia = [
    'id' => $data['id'],
    'descripcion' => $data['descripcion'],
    'fecha' => $data['fecha'],
    'estado' => $data['estado'],
    'id_lote' => $data['id_lote']
];

// Datos del lote
$lote = $data;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_topbar.php';
?>

<!-- CONTENIDO -->
<div class="admin-contenido">

    <h1 class="admin-titulo">Resolver incidencia</h1>

    <!-- INFORMACIÓN DE LA INCIDENCIA -->
    <section class="admin-card">
        <h2 class="admin-subtitulo">Información de la incidencia</h2>

        <p><strong>Fecha:</strong> 
            <?php echo htmlspecialchars($incidencia['fecha'] ?? ''); ?>
        </p>

        <p><strong>Descripción:</strong><br>
            <?php echo nl2br(htmlspecialchars($incidencia['descripcion'] ?? '')); ?>
        </p>
    </section>

    <!-- FORMULARIO DE EDICIÓN DEL LOTE -->
    <section class="admin-card">
        <h2 class="admin-subtitulo">Editar datos del lote</h2>

        <form action="/admin/incidencia_update.php" method="POST" class="admin-form">

            <input type="hidden" name="id_incidencia" value="<?php echo htmlspecialchars($incidencia['id'] ?? ''); ?>">
            <input type="hidden" name="id_lote" value="<?php echo htmlspecialchars($incidencia['id_lote'] ?? ''); ?>">

            <label>Código del lote</label>
            <input type="text" name="codigo_lote" value="<?php echo htmlspecialchars($lote['codigo_lote'] ?? ''); ?>" required>

            <label>Variedad de uva</label>
            <input type="text" name="variedad_uva" value="<?php echo htmlspecialchars($lote['variedad_uva'] ?? ''); ?>">

            <label>Fecha de cosecha</label>
            <input type="date" name="fecha_cosecha" value="<?php echo htmlspecialchars($lote['fecha_cosecha'] ?? ''); ?>">

            <label>Bodega</label>
            <input type="text" name="bodega" value="<?php echo htmlspecialchars($lote['bodega'] ?? ''); ?>">

            <label>Nombre del producto</label>
            <input type="text" name="nombre_producto" value="<?php echo htmlspecialchars($lote['nombre_producto'] ?? ''); ?>">

            <label>Fecha de producción</label>
            <input type="date" name="fecha_produccion" value="<?php echo htmlspecialchars($lote['fecha_produccion'] ?? ''); ?>">

            <label>Graduación alcohólica</label>
            <input type="text" name="graduacion_alcoholica" value="<?php echo htmlspecialchars($lote['graduacion_alcoholica'] ?? ''); ?>">

            <label>Acidez</label>
            <input type="text" name="acidez" value="<?php echo htmlspecialchars($lote['acidez'] ?? ''); ?>">

            <label>pH</label>
            <input type="text" name="ph" value="<?php echo htmlspecialchars($lote['ph'] ?? ''); ?>">

            <label>Sulfuroso total</label>
            <input type="text" name="sulfuroso_total" value="<?php echo htmlspecialchars($lote['sulfuroso_total'] ?? ''); ?>">

            <label>Descripción</label>
            <textarea name="descripcion" class="textarea-control"><?php echo htmlspecialchars($lote['descripcion'] ?? ''); ?></textarea>

            <button type="submit" class="admin-btn-guardar">
                Guardar cambios y marcar como resuelta
            </button>

        </form>
    </section>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
