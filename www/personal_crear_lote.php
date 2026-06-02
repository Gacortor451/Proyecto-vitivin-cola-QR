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

// Validación numérica avanzada
function validarNumeroRango($valor, $campo, $min, $max) {
    if ($valor === '' || $valor === null) return null;
    if (!is_numeric($valor)) return "El campo $campo debe ser un número válido.";
    if ($valor < $min || $valor > $max) return "El campo $campo debe estar entre $min y $max.";
    return null;
}

function generarCodigoLoteCorrelativo($conn, $variedad) {

    $año = date("Y");
    $abreviatura = strtoupper(substr($variedad, 0, 3));

    // Buscar el último lote de esa variedad en ese año
    $sql = "
        SELECT codigo_lote 
        FROM lotes 
        WHERE variedad_uva = :variedad
        AND codigo_lote LIKE :prefijo
        ORDER BY codigo_lote DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':variedad' => $variedad,
        ':prefijo' => "$año-$abreviatura-%"
    ]);

    $ultimo = $stmt->fetchColumn();

    if ($ultimo) {
        // Extraer el número final
        $partes = explode("-", $ultimo);
        $numero = intval($partes[2]) + 1;
    } else {
        $numero = 1;
    }

    $numeroFormateado = str_pad($numero, 2, "0", STR_PAD_LEFT);

    return "$año-$abreviatura-$numeroFormateado";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Leer variedad primero
    $variedad_uva = trim($_POST['variedad_uva']);

    // 2. Generar código de lote correlativo real
    $codigo_lote = generarCodigoLoteCorrelativo($conn, $variedad_uva);

    // 3. Resto de campos
    $fecha_cosecha = limpiarFecha($_POST['fecha_cosecha']);
    $bodega = trim($_POST['bodega']);
    $descripcion = trim($_POST['descripcion']);
    $nombre_producto = trim($_POST['nombre_producto']);
    $fecha_produccion = limpiarFecha($_POST['fecha_produccion']);

    $graduacion_alcoholica = $_POST['graduacion_alcoholica'];
    $acidez = $_POST['acidez'];
    $ph = $_POST['ph'];
    $sulfuroso_total = $_POST['sulfuroso_total'];

    // Validación básica
    if ($variedad_uva === '') $errores[] = "La variedad de uva es obligatoria.";
    if ($bodega === '') $errores[] = "La bodega es obligatoria.";

    // Validación de listas válidas
    $validar_variedades = ['Tempranillo','Syrah','Garnacha','Merlot','Cabernet Sauvignon','Verdejo','Albariño'];
    $validar_bodegas = ['Bodega Sierra del Sur','Viñedos del Alba','Bodega Campo Viejo'];
    $validar_productos = ['Vino Tinto Joven','Vino Roble','Vino Rosado','Vino Crianza','Vino Blanco'];

    if (!in_array($variedad_uva, $validar_variedades)) {
        $errores[] = "La variedad seleccionada no es válida.";
    }

    if (!in_array($bodega, $validar_bodegas)) {
        $errores[] = "La bodega seleccionada no es válida.";
    }

    if ($nombre_producto !== '' && !in_array($nombre_producto, $validar_productos)) {
        $errores[] = "El producto seleccionado no es válido.";
    }

    // Validación de fechas
    if ($fecha_cosecha && $fecha_produccion && $fecha_produccion < $fecha_cosecha) {
        $errores[] = "La fecha de producción no puede ser anterior a la fecha de cosecha.";
    }

    // Validación de descripción
    if ($descripcion !== '' && strlen($descripcion) < 10) {
        $errores[] = "La descripción debe tener al menos 10 caracteres.";
    }

    // Validaciones numéricas avanzadas
    if ($error = validarNumeroRango($graduacion_alcoholica, "Graduación alcohólica", 5, 20)) $errores[] = $error;
    if ($error = validarNumeroRango($acidez, "Acidez", 3, 10)) $errores[] = $error;
    if ($error = validarNumeroRango($ph, "pH", 2.5, 4.5)) $errores[] = $error;
    if ($error = validarNumeroRango($sulfuroso_total, "Sulfuroso total", 0, 200)) $errores[] = $error;

    // Limpiar números
    $graduacion_alcoholica = ($graduacion_alcoholica === '' ? null : $graduacion_alcoholica);
    $acidez = ($acidez === '' ? null : $acidez);
    $ph = ($ph === '' ? null : $ph);
    $sulfuroso_total = ($sulfuroso_total === '' ? null : $sulfuroso_total);

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

            if ($e->getCode() === '23505') {
                $errores[] = "El código de lote $codigo_lote ya existe. Introduce uno diferente.";
            } else {
                throw $e;
            }
        }
    }
}

?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="personal-editar-contenedor">

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

        <label>Código del lote</label>
        <input type="text" name="codigo_lote" id="codigo_lote" required readonly style="background:#eee; cursor:not-allowed;">

        <label>Variedad de uva</label>
        <select name="variedad_uva" required>
            <option value="">Seleccionar variedad</option>
            <?php foreach ($variedades as $v): ?>
                <option value="<?php echo $v; ?>"><?php echo $v; ?></option>
            <?php endforeach; ?>
        </select>

        <label>Fecha de cosecha</label>
        <input type="date" name="fecha_cosecha">

        <label>Bodega</label>
        <select name="bodega" required>
            <option value="">Seleccionar bodega</option>
            <?php foreach ($bodegas as $b): ?>
                <option value="<?php echo $b; ?>"><?php echo $b; ?></option>
            <?php endforeach; ?>
        </select>

        <label>Descripción</label>
        <textarea name="descripcion"></textarea>

        <label>Nombre del producto</label>
        <select name="nombre_producto">
            <option value="">Seleccionar producto</option>
            <?php foreach ($productos as $p): ?>
                <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
            <?php endforeach; ?>
        </select>

        <label>Fecha de producción</label>
        <input type="date" name="fecha_produccion">

        <label>Graduación alcohólica</label>
        <input type="number" step="0.01" inputmode="decimal" name="graduacion_alcoholica">

        <label>Acidez</label>
        <input type="number" step="0.01" inputmode="decimal" name="acidez">

        <label>pH</label>
        <input type="number" step="0.01" inputmode="decimal" name="ph">

        <label>Sulfuroso total</label>
        <input type="number" step="0.01" inputmode="decimal" name="sulfuroso_total">

        <button type="submit" class="btn-guardar">Crear lote</button>

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

        <!-- 1B. Autocompletar bodega según variedad -->
        <script>
        document.addEventListener("DOMContentLoaded", function() {

            const mapaBodegas = {
                "Tempranillo": "Bodega Sierra del Sur",
                "Syrah": "Viñedos del Alba",
                "Garnacha": "Bodega Campo Viejo"
            };

            const selectVariedad = document.querySelector('select[name="variedad_uva"]');
            const selectBodega = document.querySelector('select[name="bodega"]');

            if (selectVariedad && selectBodega) {

                selectVariedad.addEventListener("change", function() {
                    const variedad = this.value;

                    if (mapaBodegas[variedad]) {
                        selectBodega.value = mapaBodegas[variedad];
                        selectBodega.dispatchEvent(new Event("change"));
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

        <!-- 5. Autocompletar parámetros analíticos según el nombre del producto -->
        <script>
        document.addEventListener("DOMContentLoaded", function() {

            const mapaAnaliticaProducto = {
                "Vino Tinto Joven": { graduacion: 13.5, acidez: 5.5, ph: 3.6, sulfuroso: 80 },
                "Vino Roble": { graduacion: 14.0, acidez: 5.0, ph: 3.5, sulfuroso: 85 },
                "Vino Rosado": { graduacion: 12.5, acidez: 5.8, ph: 3.3, sulfuroso: 75 },
                "Vino Crianza": { graduacion: 14.2, acidez: 5.6, ph: 3.4, sulfuroso: 90 },
                "Vino Blanco": { graduacion: 12.0, acidez: 6.2, ph: 3.1, sulfuroso: 95 }
            };

            const selectProducto = document.querySelector('select[name="nombre_producto"]');

            const inputGraduacion = document.querySelector('input[name="graduacion_alcoholica"]');
            const inputAcidez = document.querySelector('input[name="acidez"]');
            const inputPH = document.querySelector('input[name="ph"]');
            const inputSulfuroso = document.querySelector('input[name="sulfuroso_total"]');

            if (selectProducto) {

                selectProducto.addEventListener("change", function() {

                    const producto = this.value;

                    if (mapaAnaliticaProducto[producto]) {

                        const datos = mapaAnaliticaProducto[producto];

                        inputGraduacion.value = datos.graduacion;
                        inputAcidez.value = datos.acidez;
                        inputPH.value = datos.ph;
                        inputSulfuroso.value = datos.sulfuroso;
                    }
                });
            }
        });
        </script>

        <!-- 6. Mostrar el código generado automáticamente -->
        <script>
        document.addEventListener("DOMContentLoaded", function() {

            const inputCodigo = document.getElementById("codigo_lote");
            const selectVariedad = document.querySelector('select[name="variedad_uva"]');

            selectVariedad.addEventListener("change", function() {
                // El código real se genera en PHP al enviar el formulario
                // Aquí solo mostramos un placeholder visual
                const año = new Date().getFullYear();
                const abreviatura = this.value.substring(0, 3).toUpperCase();
                inputCodigo.value = `${año}-${abreviatura}-??`;
            });

        });     
        </script>

    </form>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
