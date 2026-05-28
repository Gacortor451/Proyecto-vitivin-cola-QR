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

$id_lote = $_GET['id'] ?? null;

if (!$id_lote) {
    die("Lote no especificado.");
}

$db = new Database();
$conn = $db->getConnection();

// Obtener datos del lote
$stmt = $conn->prepare("SELECT * FROM lotes WHERE id = :id");
$stmt->execute([':id' => $id_lote]);
$lote = $stmt->fetch();

if (!$lote) {
    die("Lote no encontrado.");
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $errores = [];

    function validarNumero($valor, $campo) {
        if ($valor === '' || $valor === null) return null;
        if (!is_numeric($valor)) return "El campo $campo debe ser un número válido.";
        return null;
    }

    // Validar SOLO si el campo existe en POST
    if (isset($_POST['graduacion_alcoholica'])) {
        if ($error = validarNumero($_POST['graduacion_alcoholica'], "Graduación alcohólica")) $errores[] = $error;
    }

    if (isset($_POST['acidez'])) {
        if ($error = validarNumero($_POST['acidez'], "Acidez")) $errores[] = $error;
    }

    if (isset($_POST['ph'])) {
        if ($error = validarNumero($_POST['ph'], "pH")) $errores[] = $error;
    }

    if (isset($_POST['sulfuroso_total'])) {
        if ($error = validarNumero($_POST['sulfuroso_total'], "Sulfuroso total")) $errores[] = $error;
    }

    if (empty($errores)) {

        $campos = [
            'variedad_uva',
            'fecha_cosecha',
            'bodega',
            'descripcion',
            'nombre_producto',
            'fecha_produccion',
            'graduacion_alcoholica',
            'acidez',
            'ph',
            'sulfuroso_total'
        ];

        $updates = [];
        $params = [':id' => $id_lote];

        foreach ($campos as $campo) {

            // Solo actualizar si el campo era editable
            if ($lote[$campo] === null || $lote[$campo] === '' || $lote[$campo] === 'No especificado') {

                // Y solo si el usuario lo envió
                if (isset($_POST[$campo]) && $_POST[$campo] !== '') {
                    $updates[] = "$campo = :$campo";
                    $params[":$campo"] = $_POST[$campo];
                }
            }
        }

        if (!empty($updates)) {
            $sql = "UPDATE lotes SET " . implode(", ", $updates) . ", fecha_actualizacion = NOW() WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
        }

        // Mantener el ID del lote
        $_SESSION['ultimo_lote'] = $id_lote;

        header("Location: /personal.php");
        exit;
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

    <h1 class="personal-titulo">
        Completar información del lote: <?php echo htmlspecialchars($lote['codigo_lote']); ?>
    </h1>

    <?php if (!empty($errores)): ?>
        <div style="background:#ffe5e5; border-left:5px solid #ff4d4d; padding:12px; margin-bottom:20px; color:#b30000; font-weight:bold;>
            <strong>Se han encontrado errores:</strong><br>
            <?php foreach ($errores as $e): ?>
                • <?php echo htmlspecialchars($e); ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-editar-lote">

        <?php
        $campos = [
            'variedad_uva' => 'Variedad de uva',
            'fecha_cosecha' => 'Fecha de cosecha',
            'bodega' => 'Bodega',
            'descripcion' => 'Descripción',
            'nombre_producto' => 'Nombre del producto',
            'fecha_produccion' => 'Fecha de producción',
            'graduacion_alcoholica' => 'Graduación alcohólica',
            'acidez' => 'Acidez',
            'ph' => 'pH',
            'sulfuroso_total' => 'Sulfuroso total'
        ];

        foreach ($campos as $campo => $label):
            $valor = $lote[$campo];
            $editable = ($valor === null || $valor === '' || $valor === 'No especificado');
        ?>

            <label><?php echo $label; ?></label>

            <?php if ($editable): ?>

                <?php if ($campo === 'fecha_cosecha' || $campo === 'fecha_produccion'): ?>
                    <input type="date" name="<?php echo $campo; ?>">
                <?php else: ?>
                    <input type="text" name="<?php echo $campo; ?>" placeholder="Añadir información">
                <?php endif; ?>

            <?php else: ?>

                <input type="text" value="<?php echo htmlspecialchars($valor); ?>" disabled>

            <?php endif; ?>

        <?php endforeach; ?>

        <button type="submit" class="btn-guardar">Guardar información</button>

    </form>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
