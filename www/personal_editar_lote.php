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

    // Graduación alcohólica (5% – 20%)
    if (isset($_POST['graduacion_alcoholica'])) {
        if ($error = validarNumeroRango($_POST['graduacion_alcoholica'], "Graduación alcohólica", 5, 20)) {
            $errores[] = $error;
        }
    }

    // Acidez (3 – 10 g/L)
    if (isset($_POST['acidez'])) {
        if ($error = validarNumeroRango($_POST['acidez'], "Acidez", 3, 10)) {
            $errores[] = $error;
        }
    }

    // pH (2.5 – 4.5)
    if (isset($_POST['ph'])) {
        if ($error = validarNumeroRango($_POST['ph'], "pH", 2.5, 4.5)) {
            $errores[] = $error;
        }
    }

    // Sulfuroso total (0 – 200 mg/L)
    if (isset($_POST['sulfuroso_total'])) {
        if ($error = validarNumeroRango($_POST['sulfuroso_total'], "Sulfuroso total", 0, 200)) {
            $errores[] = $error;
        }
    }

    // --- VALIDACIÓN DE FECHAS ---
    if (!empty($_POST['fecha_cosecha']) && !empty($_POST['fecha_produccion'])) {
        if ($_POST['fecha_produccion'] < $_POST['fecha_cosecha']) {
            $errores[] = "La fecha de producción no puede ser anterior a la fecha de cosecha.";
        }
    }

    // --- VALIDACIÓN DE DESCRIPCIÓN ---
    if (isset($_POST['descripcion']) && $_POST['descripcion'] !== '') {
        if (strlen(trim($_POST['descripcion'])) < 10) {
            $errores[] = "La descripción debe tener al menos 10 caracteres.";
        }
    }

    // --- VALIDACIÓN DE DESPLEGABLES ---
    $validar_variedades = ['Tempranillo','Syrah','Garnacha','Merlot','Cabernet Sauvignon','Verdejo','Albariño'];
    $validar_bodegas = ['Bodega Sierra del Sur','Viñedos del Alba','Bodega Campo Viejo'];
    $validar_productos = ['Vino Tinto Joven','Vino Roble','Vino Rosado','Vino Crianza','Vino Blanco'];

    if (isset($_POST['variedad_uva']) && $_POST['variedad_uva'] !== '' && !in_array($_POST['variedad_uva'], $validar_variedades)) {
        $errores[] = "La variedad seleccionada no es válida.";
    }

    if (isset($_POST['bodega']) && $_POST['bodega'] !== '' && !in_array($_POST['bodega'], $validar_bodegas)) {
        $errores[] = "La bodega seleccionada no es válida.";
    }

    if (isset($_POST['nombre_producto']) && $_POST['nombre_producto'] !== '' && !in_array($_POST['nombre_producto'], $validar_productos)) {
        $errores[] = "El producto seleccionado no es válido.";
    }

    // Si no hay errores → actualizar
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
// Listas para desplegables
$variedades = [
    'Tempranillo', 'Syrah', 'Garnacha', 'Merlot',
    'Cabernet Sauvignon', 'Verdejo', 'Albariño'
];

$bodegas = [
    'Bodega Sierra del Sur',
    'Viñedos del Alba',
    'Bodega Campo Viejo'
];

$productos = [
    'Vino Tinto Joven',
    'Vino Roble',
    'Vino Rosado',
    'Vino Crianza',
    'Vino Blanco'
];
?>

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

                <?php if ($campo === 'variedad_uva'): ?>

                    <select name="variedad_uva">
                        <option value="">Seleccionar variedad</option>
                        <?php foreach ($variedades as $v): ?>
                            <option value="<?php echo $v; ?>"><?php echo $v; ?></option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ($campo === 'bodega'): ?>

                    <select name="bodega">
                        <option value="">Seleccionar bodega</option>
                        <?php foreach ($bodegas as $b): ?>
                            <option value="<?php echo $b; ?>"><?php echo $b; ?></option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ($campo === 'nombre_producto'): ?>

                    <select name="nombre_producto">
                        <option value="">Seleccionar producto</option>
                        <?php foreach ($productos as $p): ?>
                            <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ($campo === 'descripcion'): ?>

                    <textarea name="descripcion" placeholder="Añadir descripción"></textarea>

                <?php elseif ($campo === 'fecha_cosecha' || $campo === 'fecha_produccion'): ?>

                    <input type="date" name="<?php echo $campo; ?>">

                <?php elseif (in_array($campo, ['graduacion_alcoholica', 'acidez', 'ph', 'sulfuroso_total'])): ?>

                    <input type="number"
                           name="<?php echo $campo; ?>"
                           step="0.01"
                           inputmode="decimal"
                           placeholder="Añadir información">

                <?php else: ?>

                    <input type="text" name="<?php echo $campo; ?>" placeholder="Añadir información">

                <?php endif; ?>

            <?php else: ?>

                <input type="text"
                       value="<?php echo htmlspecialchars($valor); ?>"
                       readonly
                       class="campo-bloqueado">

            <?php endif; ?>

        <?php endforeach; ?>

        <button type="submit" class="btn-guardar">Guardar información</button>

        <!-- ============================= -->
        <!-- AUTOCOMPLETADOS INTELIGENTES -->
        <!-- ============================= -->

        <!-- 1. Autocompletar variedad según bodega -->
        <script>
        document.addEventListener("DOMContentLoaded", function() {

            const mapaVariedades = {
                "Bodega Sierra del Sur": "Tempranillo",
                "Viñedos del Alba": "Syrah",
                "Bodega Campo Viejo": "Garnacha"
            };

            const selectBodega = document.querySelector('select[name="bodega"]');
            const selectVariedad = document.querySelector('select[name="variedad_uva"]');

            if (selectBodega && selectVariedad) {

                selectBodega.addEventListener("change", function() {
                    const bodega = this.value;

                    if (mapaVariedades[bodega]) {
                        selectVariedad.value = mapaVariedades[bodega];
                        selectVariedad.dispatchEvent(new Event("change"));
                    }
                });
            }
        });
        </script>

        <!-- 2. Autocompletar producto según variedad -->
        <script>
        document.addEventListener("DOMContentLoaded", function() {

            const mapaProductos = {
                "Tempranillo": "Vino Tinto Joven",
                "Syrah": "Vino Roble",
                "Garnacha": "Vino Rosado",
                "Merlot": "Vino Tinto Joven",
                "Cabernet Sauvignon": "Vino Crianza",
                "Verdejo": "Vino Blanco",
                "Albariño": "Vino Blanco"
            };

            const selectVariedad = document.querySelector('select[name="variedad_uva"]');
            const selectProducto = document.querySelector('select[name="nombre_producto"]');

            if (selectVariedad && selectProducto) {

                selectVariedad.addEventListener("change", function() {
                    const variedad = this.value;

                    if (mapaProductos[variedad]) {
                        selectProducto.value = mapaProductos[variedad];
                    }
                });
            }
        });
        </script>

        <!-- 3. Autocompletar fecha de producción según fecha de cosecha -->
        <script>
        document.addEventListener("DOMContentLoaded", function() {

            const inputCosecha = document.querySelector('input[name="fecha_cosecha"]');
            const inputProduccion = document.querySelector('input[name="fecha_produccion"]');

            if (inputCosecha && inputProduccion) {

                inputCosecha.addEventListener("change", function() {

                    if (inputProduccion.value !== "") return;

                    const fecha = new Date(this.value);

                    if (!isNaN(fecha.getTime())) {

                        fecha.setDate(fecha.getDate() + 15);

                        const año = fecha.getFullYear();
                        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
                        const dia = String(fecha.getDate()).padStart(2, '0');

                        inputProduccion.value = `${año}-${mes}-${dia}`;
                    }
                });
            }
        });
        </script>

        <!-- 4. Autocompletar parámetros analíticos según variedad -->
        <script>
        document.addEventListener("DOMContentLoaded", function() {

            const mapaAnaliticos = {
                "Tempranillo": { graduacion: 13.5, acidez: 5.5, ph: 3.6, sulfuroso: 80 },
                "Syrah": { graduacion: 14.0, acidez: 5.0, ph: 3.5, sulfuroso: 90 },
                "Garnacha": { graduacion: 13.0, acidez: 4.8, ph: 3.7, sulfuroso: 70 },
                "Merlot": { graduacion: 13.5, acidez: 5.2, ph: 3.6, sulfuroso: 85 },
                "Cabernet Sauvignon": { graduacion: 14.0, acidez: 5.8, ph: 3.5, sulfuroso: 95 },
                "Verdejo": { graduacion: 12.5, acidez: 6.0, ph: 3.2, sulfuroso: 100 },
                "Albariño": { graduacion: 12.0, acidez: 6.5, ph: 3.1, sulfuroso: 110 }
            };

            const selectVariedad = document.querySelector('select[name="variedad_uva"]');

            const inputGraduacion = document.querySelector('input[name="graduacion_alcoholica"]');
            const inputAcidez = document.querySelector('input[name="acidez"]');
            const inputPH = document.querySelector('input[name="ph"]');
            const inputSulfuroso = document.querySelector('input[name="sulfuroso_total"]');

            if (selectVariedad) {

                selectVariedad.addEventListener("change", function() {

                    const variedad = this.value;

                    if (mapaAnaliticos[variedad]) {

                        if (inputGraduacion && inputGraduacion.value === "") {
                            inputGraduacion.value = mapaAnaliticos[variedad].graduacion;
                        }

                        if (inputAcidez && inputAcidez.value === "") {
                            inputAcidez.value = mapaAnaliticos[variedad].acidez;
                        }

                        if (inputPH && inputPH.value === "") {
                            inputPH.value = mapaAnaliticos[variedad].ph;
                        }

                        if (inputSulfuroso && inputSulfuroso.value === "") {
                            inputSulfuroso.value = mapaAnaliticos[variedad].sulfuroso;
                        }
                    }
                });
            }
        });
        </script>

    </form>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
