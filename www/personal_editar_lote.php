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
    error403();
}

// Validar ID del lote
$id_lote = $_GET['id'] ?? null;

if (!$id_lote || !ctype_digit($id_lote)) {
    error404();
}

$id_lote = intval($id_lote);

$db = new Database();
$conn = $db->getConnection();

// Obtener datos del lote
$stmt = $conn->prepare("SELECT * FROM lotes WHERE id = :id");
$stmt->execute([':id' => $id_lote]);
$lote = $stmt->fetch();

if (!$lote) {
    error404();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $errores = [];

    // --- VALIDACIONES NUMÉRICAS AVANZADAS ---
    function validarNumeroRango($valor, $campo, $min, $max) {
        if ($valor === '' || $valor === null) return null;
        if (!is_numeric($valor)) return "El campo $campo debe ser un número válido.";
        if ($valor < $min || $valor > $max) return "El campo $campo debe estar entre $min y $max.";
        return null;
    }

    // Validaciones numéricas
    if ($error = validarNumeroRango($_POST['graduacion_alcoholica'] ?? null, "Graduación alcohólica", 5, 20)) $errores[] = $error;
    if ($error = validarNumeroRango($_POST['acidez'] ?? null, "Acidez", 3, 10)) $errores[] = $error;
    if ($error = validarNumeroRango($_POST['ph'] ?? null, "pH", 2.5, 4.5)) $errores[] = $error;
    if ($error = validarNumeroRango($_POST['sulfuroso_total'] ?? null, "Sulfuroso total", 0, 200)) $errores[] = $error;

    // --- VALIDACIÓN DE FECHAS ---
    if (!empty($_POST['fecha_cosecha']) && !empty($_POST['fecha_produccion'])) {
        if ($_POST['fecha_produccion'] < $_POST['fecha_cosecha']) {
            $errores[] = "La fecha de producción no puede ser anterior a la fecha de cosecha.";
        }
    }

    // --- VALIDACIÓN DE DESCRIPCIÓN ---
    if (!empty($_POST['descripcion']) && strlen(trim($_POST['descripcion'])) < 10) {
        $errores[] = "La descripción debe tener al menos 10 caracteres.";
    }

    // --- VALIDACIÓN DE DESPLEGABLES ---
    $validar_variedades = ['Tempranillo','Syrah','Garnacha','Merlot','Cabernet Sauvignon','Verdejo','Albariño'];
    $validar_bodegas = ['Bodega Sierra del Sur','Viñedos del Alba','Bodega Campo Viejo'];
    $validar_productos = ['Vino Tinto Joven','Vino Roble','Vino Rosado','Vino Crianza','Vino Blanco'];

    if (!empty($_POST['variedad_uva']) && !in_array($_POST['variedad_uva'], $validar_variedades)) {
        $errores[] = "La variedad seleccionada no es válida.";
    }

    if (!empty($_POST['bodega']) && !in_array($_POST['bodega'], $validar_bodegas)) {
        $errores[] = "La bodega seleccionada no es válida.";
    }

    if (!empty($_POST['nombre_producto']) && !in_array($_POST['nombre_producto'], $validar_productos)) {
        $errores[] = "El producto seleccionado no es válido.";
    }

    // Si no hay errores → actualizar
    if (empty($errores)) {

        $campos = [
            'bodega',
            'variedad_uva',
            'fecha_cosecha',
            'nombre_producto',
            'fecha_produccion',
            'graduacion_alcoholica',
            'acidez',
            'ph',
            'sulfuroso_total',
            'descripcion'
        ];

        $updates = [];
        $params = [':id' => $id_lote];

        foreach ($campos as $campo) {

            // Solo actualizar si el campo estaba vacío originalmente
            if ($lote[$campo] === null || $lote[$campo] === '' || $lote[$campo] === 'No especificado') {

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

        $_SESSION['ultimo_lote'] = $id_lote;

        header("Location: /personal.php");
        exit;
    }
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<style>
.campo-bloqueado {
    background: #f5f5f5;
    border: 1px solid #ccc;
    color: #555;
}
</style>

<div class="personal-editar-contenedor">

    <a href="/personal.php"
       style="display:inline-block; margin-bottom:15px; padding:8px 12px; background:#ddd; border-radius:4px; text-decoration:none; color:#333;">
        ← Volver al panel
    </a>

    <h1 class="personal-titulo">
        Completar información del lote: <?php echo htmlspecialchars($lote['codigo_lote']); ?>
    </h1>

    <?php if (!empty($errores)): ?>
        <div style="background:#ffe5e5; border-left:5px solid #ff4d4d; padding:12px; margin-bottom:20px; color:#b30000; font-weight:bold;">
            <strong>Se han encontrado errores:</strong><br>
            <?php foreach ($errores as $e): ?>
                • <?php echo htmlspecialchars($e); ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php
$variedades = ['Tempranillo','Syrah','Garnacha','Merlot','Cabernet Sauvignon','Verdejo','Albariño'];
$bodegas = ['Bodega Sierra del Sur','Viñedos del Alba','Bodega Campo Viejo'];
$productos = ['Vino Tinto Joven','Vino Roble','Vino Rosado','Vino Crianza','Vino Blanco'];
?>

<form method="POST" class="form-editar-lote">

<?php
$campos = [
    'bodega' => 'Bodega',
    'variedad_uva' => 'Variedad de uva',
    'fecha_cosecha' => 'Fecha de cosecha',
    'nombre_producto' => 'Nombre del producto',
    'fecha_produccion' => 'Fecha de producción',
    'graduacion_alcoholica' => 'Graduación alcohólica',
    'acidez' => 'Acidez',
    'ph' => 'pH',
    'sulfuroso_total' => 'Sulfuroso total',
    'descripcion' => 'Descripción'
];

foreach ($campos as $campo => $label):

    $valor_original = $lote[$campo];
    $valor_usuario = $_POST[$campo] ?? null;

    // Solo editable si estaba vacío originalmente
    $editable = ($valor_original === null || $valor_original === '' || $valor_original === 'No especificado');

    // Valor a mostrar
    $valor_mostrar = $editable ? ($valor_usuario ?? '') : $valor_original;
?>

    <label><?php echo $label; ?></label>

    <?php if ($editable): ?>

        <?php if ($campo === 'bodega'): ?>
            <select name="bodega">
                <option value="">Seleccionar bodega</option>
                <?php foreach ($bodegas as $b): ?>
                    <option value="<?php echo $b; ?>" <?php echo ($valor_mostrar === $b ? 'selected' : ''); ?>>
                        <?php echo $b; ?>
                    </option>
                <?php endforeach; ?>
            </select>

        <?php elseif ($campo === 'variedad_uva'): ?>
            <select name="variedad_uva">
                <option value="">Seleccionar variedad</option>
                <?php foreach ($variedades as $v): ?>
                    <option value="<?php echo $v; ?>" <?php echo ($valor_mostrar === $v ? 'selected' : ''); ?>>
                        <?php echo $v; ?>
                    </option>
                <?php endforeach; ?>
            </select>

        <?php elseif ($campo === 'nombre_producto'): ?>
            <select name="nombre_producto">
                <option value="">Seleccionar producto</option>
                <?php foreach ($productos as $p): ?>
                    <option value="<?php echo $p; ?>" <?php echo ($valor_mostrar === $p ? 'selected' : ''); ?>>
                        <?php echo $p; ?>
                    </option>
                <?php endforeach; ?>
            </select>

        <?php elseif ($campo === 'descripcion'): ?>
            <textarea name="descripcion" minlength="10"><?php echo htmlspecialchars($valor_mostrar); ?></textarea>

        <?php elseif ($campo === 'fecha_cosecha'): ?>
            <input type="date" name="fecha_cosecha"
                   max="<?php echo date('Y-m-d'); ?>"
                   value="<?php echo htmlspecialchars($valor_mostrar); ?>">

        <?php elseif ($campo === 'fecha_produccion'): ?>
            <input type="date" name="fecha_produccion"
                   value="<?php echo htmlspecialchars($valor_mostrar); ?>">

        <?php elseif ($campo === 'graduacion_alcoholica'): ?>
            <input type="number" name="graduacion_alcoholica" step="0.01" min="5" max="20"
                   value="<?php echo htmlspecialchars($valor_mostrar); ?>">

        <?php elseif ($campo === 'acidez'): ?>
            <input type="number" name="acidez" step="0.01" min="3" max="10"
                   value="<?php echo htmlspecialchars($valor_mostrar); ?>">

        <?php elseif ($campo === 'ph'): ?>
            <input type="number" name="ph" step="0.01" min="2.5" max="4.5"
                   value="<?php echo htmlspecialchars($valor_mostrar); ?>">

        <?php elseif ($campo === 'sulfuroso_total'): ?>
            <input type="number" name="sulfuroso_total" step="0.01" min="0" max="200"
                   value="<?php echo htmlspecialchars($valor_mostrar); ?>">

        <?php endif; ?>

    <?php else: ?>

        <input type="text"
               value="<?php echo htmlspecialchars($valor_mostrar); ?>"
               readonly
               class="campo-bloqueado">

    <?php endif; ?>

<?php endforeach; ?>

<button type="submit" class="btn-guardar">Guardar información</button>

</form>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
