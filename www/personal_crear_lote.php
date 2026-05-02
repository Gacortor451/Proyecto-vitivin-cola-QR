<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/libs/qr/generarQR.php';

// Solo empleados o admin
if (!estaLogueado() || !in_array(getRolActual(), ['empleado', 'admin'])) {
    header("Location: /login.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $codigo_lote = trim($_POST['codigo_lote']);
    $variedad_uva = trim($_POST['variedad_uva']);
    $fecha_cosecha = $_POST['fecha_cosecha'];
    $bodega = trim($_POST['bodega']);
    $descripcion = trim($_POST['descripcion']);
    $nombre_producto = trim($_POST['nombre_producto']);
    $fecha_produccion = $_POST['fecha_produccion'];
    $graduacion_alcoholica = $_POST['graduacion_alcoholica'];
    $acidez = $_POST['acidez'];
    $ph = $_POST['ph'];
    $sulfuroso_total = $_POST['sulfuroso_total'];

    // Insertar lote en PostgreSQL
    $sql = "
        INSERT INTO lotes 
        (codigo_lote, variedad_uva, fecha_cosecha, bodega, descripcion, 
         nombre_producto, fecha_produccion, graduacion_alcoholica, acidez, ph, sulfuroso_total, 
         fecha_creacion, fecha_actualizacion)
        VALUES 
        (:codigo_lote, :variedad_uva, :fecha_cosecha, :bodega, :descripcion,
         :nombre_producto, :fecha_produccion, :graduacion_alcoholica, :acidez, :ph, :sulfuroso_total,
         NOW(), NOW())
        RETURNING id
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':codigo_lote' => $codigo_lote,
        ':variedad_uva' => $variedad_uva,
        ':fecha_cosecha' => $fecha_cosecha,
        ':bodega' => $bodega,
        ':descripcion' => $descripcion,
        ':nombre_producto' => $nombre_producto,
        ':fecha_produccion' => $fecha_produccion,
        ':graduacion_alcoholica' => $graduacion_alcoholica,
        ':acidez' => $acidez,
        ':ph' => $ph,
        ':sulfuroso_total' => $sulfuroso_total
    ]);

    $idLote = $stmt->fetchColumn();

    // Generar QR automáticamente
    $rutaQR = generarQRlote($idLote);

    // Guardar ruta del QR en la BD
    $stmt = $conn->prepare("UPDATE lotes SET qr_url = :qr WHERE id = :id");
    $stmt->execute([
        ':qr' => $rutaQR,
        ':id' => $idLote
    ]);

    header("Location: /personal.php?creado=1");
    exit;
}

?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="personal-editar-contenedor">

    <h1 class="personal-titulo">Crear nuevo lote</h1>

    <form method="POST" class="form-editar-lote">

        <label>Código del lote</label>
        <input type="text" name="codigo_lote" required>

        <label>Variedad de uva</label>
        <input type="text" name="variedad_uva" required>

        <label>Fecha de cosecha</label>
        <input type="date" name="fecha_cosecha" required>

        <label>Bodega</label>
        <input type="text" name="bodega" required>

        <label>Descripción</label>
        <textarea name="descripcion"></textarea>

        <label>Nombre del producto</label>
        <input type="text" name="nombre_producto">

        <label>Fecha de producción</label>
        <input type="date" name="fecha_produccion">

        <label>Graduación alcohólica</label>
        <input type="number" step="0.01" name="graduacion_alcoholica">

        <label>Acidez</label>
        <input type="number" step="0.01" name="acidez">

        <label>pH</label>
        <input type="number" step="0.01" name="ph">

        <label>Sulfuroso total</label>
        <input type="number" step="0.01" name="sulfuroso_total">

        <button type="submit" class="btn-guardar">Crear lote</button>

    </form>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
