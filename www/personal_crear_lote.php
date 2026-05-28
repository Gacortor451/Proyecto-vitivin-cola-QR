<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/generarQR.php';

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

$errores = [];

// Función para limpiar fechas vacías
function limpiarFecha($valor) {
    return ($valor === '' || $valor === null) ? null : $valor;
}

// Función para limpiar números vacíos
function limpiarNumero($valor) {
    return ($valor === '' || $valor === null) ? null : $valor;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $codigo_lote = trim($_POST['codigo_lote']);
    $variedad_uva = trim($_POST['variedad_uva']);
    $fecha_cosecha = limpiarFecha($_POST['fecha_cosecha']);
    $bodega = trim($_POST['bodega']);
    $descripcion = trim($_POST['descripcion']);
    $nombre_producto = trim($_POST['nombre_producto']);
    $fecha_produccion = limpiarFecha($_POST['fecha_produccion']);
    $graduacion_alcoholica = limpiarNumero($_POST['graduacion_alcoholica']);
    $acidez = limpiarNumero($_POST['acidez']);
    $ph = limpiarNumero($_POST['ph']);
    $sulfuroso_total = limpiarNumero($_POST['sulfuroso_total']);

    // Validación básica
    if ($codigo_lote === '') $errores[] = "El código del lote es obligatorio.";
    if ($variedad_uva === '') $errores[] = "La variedad de uva es obligatoria.";
    if ($bodega === '') $errores[] = "La bodega es obligatoria.";

    if (empty($errores)) {

        try {

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

            $stmt = $conn->prepare("UPDATE lotes SET qr_url = :qr WHERE id = :id");
            $stmt->execute([
                ':qr' => $rutaQR,
                ':id' => $idLote
            ]);

            $_SESSION['ultimo_lote'] = $idLote;

            header("Location: /personal.php?creado=1");
            exit;

        } catch (PDOException $e) {

            // Código de error UNIQUE VIOLATION en PostgreSQL
            if ($e->getCode() === '23505') {
                $errores[] = "El código de lote $codigo_lote ya existe. Introduce uno diferente.";
            } else {
                throw $e; // Re-lanzar si es otro error
            }
        }
    }
}


?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="personal-editar-contenedor">

    <!-- BOTÓN VOLVER AL PANEL -->
    <a href="/personal.php"
       style="display:inline-block; margin-bottom:15px; padding:8px 12px; background:#ddd; border-radius:4px; text-decoration:none; color:#333;">
        ← Volver al panel
    </a>

    <h1 class="personal-titulo">Crear nuevo lote</h1>

    <?php if (!empty($errores)): ?>
        <div style="background:#ffe5e5; border-left:5px solid #ff4d4d; padding:12px; margin-bottom:20px; color:#b30000; font-weight:bold;">
            Se han encontrado errores:<br>
            <?php foreach ($errores as $e): ?>
                • <?php echo htmlspecialchars($e); ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-editar-lote">

        <label>Código del lote</label>
        <input type="text" name="codigo_lote" required>

        <label>Variedad de uva</label>
        <input type="text" name="variedad_uva" required>

        <label>Fecha de cosecha</label>
        <input type="date" name="fecha_cosecha">

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
