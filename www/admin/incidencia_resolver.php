<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['admin']);

require_once __DIR__ . '/../config/database.php';

// Función segura para imprimir valores sin warnings
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$id_incidencia = $_GET['id'] ?? null;

// Validación del ID
if (!$id_incidencia || !ctype_digit($id_incidencia)) {
    error404();
}

$id_incidencia = intval($id_incidencia);

$db = new Database();
$conn = $db->getConnection();

// ============================
// 1. OBTENER INCIDENCIA + LOTE
// ============================
$stmt = $conn->prepare("
    SELECT 
        i.id AS incidencia_id,
        i.descripcion AS descripcion_incidencia,
        i.fecha AS fecha_incidencia,
        i.estado AS estado_incidencia,
        i.id_lote,

        l.id AS lote_id,
        l.codigo_lote,
        l.variedad_uva,
        l.fecha_cosecha,
        l.bodega,
        l.nombre_producto,
        l.fecha_produccion,
        l.graduacion_alcoholica,
        l.acidez,
        l.ph,
        l.sulfuroso_total,
        l.descripcion AS descripcion_lote
    FROM incidencias i
    JOIN lotes l ON l.id = i.id_lote
    WHERE i.id = :id
");
$stmt->execute([':id' => $id_incidencia]);
$data = $stmt->fetch();

// Si no existe → 404
if (!$data) {
    error404();
}

// ============================
// 2. SEPARAR DATOS
// ============================

// Datos de la incidencia
$incidencia = [
    'id' => $data['incidencia_id'],
    'descripcion' => $data['descripcion_incidencia'],
    'fecha' => date("d/m/Y H:i", strtotime($data['fecha_incidencia'])),
    'estado' => $data['estado_incidencia'],
    'id_lote' => $data['id_lote']
];

// Datos del lote
$lote = [
    'id' => $data['lote_id'],
    'codigo_lote' => $data['codigo_lote'],
    'variedad_uva' => $data['variedad_uva'],
    'fecha_cosecha' => $data['fecha_cosecha'],
    'bodega' => $data['bodega'],
    'nombre_producto' => $data['nombre_producto'],
    'fecha_produccion' => $data['fecha_produccion'],
    'graduacion_alcoholica' => $data['graduacion_alcoholica'],
    'acidez' => $data['acidez'],
    'ph' => $data['ph'],
    'sulfuroso_total' => $data['sulfuroso_total'],
    'descripcion' => $data['descripcion_lote']
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_topbar.php';
?>

<div class="admin-contenido">

    <h1 class="admin-titulo">Resolver incidencia</h1>

    <!-- INFORMACIÓN DE LA INCIDENCIA -->
    <section class="admin-card">
        <h2 class="admin-subtitulo">Información de la incidencia</h2>

        <p><strong>Fecha:</strong> 
            <?php echo h($incidencia['fecha']); ?>
        </p>

        <p><strong>Descripción de la incidencia:</strong><br>
            <?php echo nl2br(h($incidencia['descripcion'])); ?>
        </p>
    </section>

    <!-- FORMULARIO DE EDICIÓN DEL LOTE -->
    <section class="admin-card">
        <h2 class="admin-subtitulo">Editar datos del lote</h2>

        <form action="/admin/incidencia_update.php" method="POST" class="admin-form">

            <input type="hidden" name="id_incidencia" value="<?php echo h($incidencia['id']); ?>">
            <input type="hidden" name="id_lote" value="<?php echo h($incidencia['id_lote']); ?>">

            <label>Código del lote</label>
            <input type="text" name="codigo_lote"
                   value="<?php echo h($lote['codigo_lote']); ?>"
                   readonly style="background:#eee; cursor:not-allowed;">

            <label>Variedad de uva</label>
            <input type="text" name="variedad_uva"
                   value="<?php echo h($lote['variedad_uva']); ?>"
                   readonly style="background:#eee; cursor:not-allowed;">

            <label>Fecha de cosecha</label>
            <input type="date" name="fecha_cosecha"
                   value="<?php echo h($lote['fecha_cosecha']); ?>"
                   readonly style="background:#eee; cursor:not-allowed;">

            <label>Bodega</label>
            <input type="text" name="bodega"
                   value="<?php echo h($lote['bodega']); ?>"
                   readonly style="background:#eee; cursor:not-allowed;">

            <label>Nombre del producto</label>
            <input type="text" name="nombre_producto"
                   value="<?php echo h($lote['nombre_producto']); ?>"
                   readonly style="background:#eee; cursor:not-allowed;">

            <label>Fecha de producción</label>
            <input type="date" name="fecha_produccion"
                   value="<?php echo h($lote['fecha_produccion']); ?>"
                   readonly style="background:#eee; cursor:not-allowed;">

            <label>Graduación alcohólica</label>
            <input type="number" step="0.01" name="graduacion_alcoholica"
                   value="<?php echo h($lote['graduacion_alcoholica']); ?>">

            <label>Acidez</label>
            <input type="number" step="0.01" name="acidez"
                   value="<?php echo h($lote['acidez']); ?>">

            <label>pH</label>
            <input type="number" step="0.01" name="ph"
                   value="<?php echo h($lote['ph']); ?>">

            <label>Sulfuroso total</label>
            <input type="number" step="0.01" name="sulfuroso_total"
                   value="<?php echo h($lote['sulfuroso_total']); ?>">

            <label>Descripción del lote</label>
            <textarea name="descripcion" class="textarea-control"><?php echo h($lote['descripcion']); ?></textarea>

            <button type="submit" class="admin-btn-guardar">
                Guardar cambios y marcar como resuelta
            </button>

        </form>
    </section>

</div>

<!-- Validación en tiempo real (igual que en empleado) -->
<script>
document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('input', () => {
        input.value = input.value.replace(/[^0-9.,]/g, '');
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
