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

$id_lote = $_GET['id'] ?? null;

if (!$id_lote || !ctype_digit($id_lote)) {
    error404();
}

$db = new Database();
$conn = $db->getConnection();

// Obtener datos del lote (incluyendo estado actual)
$stmt = $conn->prepare("SELECT * FROM lotes WHERE id = :id");
$stmt->execute([':id' => $id_lote]);
$lote = $stmt->fetch();

if (!$lote) {
    error404();
}

// ===============================
// CARGAR DESPLEGABLES DESDE BD
// ===============================

$lista_barricas = $conn->query("SELECT id, codigo FROM barricas ORDER BY id ASC")->fetchAll();
$lista_depositos = $conn->query("SELECT id, codigo FROM depositos ORDER BY id ASC")->fetchAll();

// ===============================
// OBTENER ÚLTIMOS RECIPIENTES USADOS POR EL LOTE
// ===============================

$ultimo_barrica = $conn->query("
    SELECT id_barrica 
    FROM trazabilidad 
    WHERE id_lote = $id_lote AND id_barrica IS NOT NULL 
    ORDER BY fecha DESC LIMIT 1
")->fetchColumn();

$ultimo_deposito = $conn->query("
    SELECT id_deposito 
    FROM trazabilidad 
    WHERE id_lote = $id_lote AND id_deposito IS NOT NULL 
    ORDER BY fecha DESC LIMIT 1
")->fetchColumn();

// ===============================
// VALIDACIONES
// ===============================

$errores = [];

function validarNumero($valor, $campo) {
    if ($valor === '' || $valor === null) return null;
    if (!ctype_digit($valor)) return "El campo $campo debe ser un número válido.";
    return null;
}

function existe($conn, $tabla, $id) {
    $stmt = $conn->prepare("SELECT id FROM $tabla WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() !== false;
}

$tipos_evento_validos = [
    "Entrada en barrica",
    "Salida de barrica",
    "Trasiego",
    "Movimiento",
    "Filtrado",
    "Clarificación",
    "Análisis",
    "Embotellado"
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tipo = trim($_POST['tipo_evento']);
    $fecha = trim($_POST['fecha']);
    $descripcion = trim($_POST['descripcion']);

    $barrica = $_POST['id_barrica'] ?: null;
    $deposito = $_POST['id_deposito'] ?: null;

    // Validación básica
    if ($tipo === '' || $fecha === '') {
        $errores[] = "El tipo de evento y la fecha son obligatorios.";
    }

    // Validar tipo de evento
    if (!in_array($tipo, $tipos_evento_validos)) {
        $errores[] = "El tipo de evento seleccionado no es válido.";
    }

    // Validar fecha no futura
    if ($fecha > date("Y-m-d")) {
        $errores[] = "La fecha no puede ser futura.";
    }

    // Validación numérica real
    if ($error = validarNumero($barrica, "ID Barrica")) $errores[] = $error;
    if ($error = validarNumero($deposito, "ID Depósito")) $errores[] = $error;

    // Validar claves foráneas
    if ($barrica !== null && !existe($conn, 'barricas', $barrica)) {
        $errores[] = "La barrica con ID $barrica no existe.";
    }

    if ($deposito !== null && !existe($conn, 'depositos', $deposito)) {
        $errores[] = "El depósito con ID $deposito no existe.";
    }

    // Validar descripción mínima
    if ($descripcion !== '' && strlen($descripcion) < 5) {
        $errores[] = "La descripción debe tener al menos 5 caracteres.";
    }

    // ===============================
    // COHERENCIA FÍSICA (NIVEL 2)
    // ===============================

    $estado_actual = $lote['estado_actual'] ?? 'ninguno';
    $id_recipiente_actual = $lote['id_recipiente_actual'] ?? null;

    // Eventos que requieren barrica
    $eventosBarrica = [
        "Entrada en barrica",
        "Salida de barrica",
        "Trasiego"
    ];

    // Eventos que requieren depósito
    $eventosDeposito = [
        "Movimiento",
        "Filtrado",
        "Clarificación"
    ];

    // 1) Entrada en barrica
    if ($tipo === "Entrada en barrica") {
        if ($estado_actual === 'barrica') {
            $errores[] = "El lote ya está en barrica. No puedes registrar otra entrada en barrica sin una salida previa.";
        }
        if ($barrica === null) {
            $errores[] = "Debes seleccionar una barrica para la entrada en barrica.";
        }
    }

    // 2) Salida de barrica
    if ($tipo === "Salida de barrica") {
        if ($estado_actual !== 'barrica') {
            $errores[] = "No puedes registrar una salida de barrica si el lote no está actualmente en barrica.";
        }
        if ($deposito === null) {
            $errores[] = "Debes seleccionar un depósito de destino para la salida de barrica.";
        }
    }

    // 3) Trasiego
    if ($tipo === "Trasiego") {
        if ($estado_actual === 'ninguno') {
            $errores[] = "No puedes registrar un trasiego si el lote no está en ningún recipiente.";
        }
        // Si está en barrica → trasiego entre barricas
        if ($estado_actual === 'barrica') {
            if ($barrica === null) {
                $errores[] = "Debes seleccionar una barrica de destino para el trasiego.";
            }
        }
        // Si está en depósito → trasiego entre depósitos
        if ($estado_actual === 'deposito') {
            if ($deposito === null) {
                $errores[] = "Debes seleccionar un depósito de destino para el trasiego.";
            }
        }
    }

    // 4) Eventos de depósito (Movimiento, Filtrado, Clarificación)
    if (in_array($tipo, $eventosDeposito)) {
        if ($estado_actual === 'barrica') {
            $errores[] = "No puedes registrar '$tipo' mientras el lote está en barrica. Debe salir de barrica primero.";
        }
        if ($deposito === null) {
            $errores[] = "Debes seleccionar un depósito para el evento '$tipo'.";
        }
    }

    // 5) Embotellado (opcionalmente podrías exigir que esté en depósito)
    if ($tipo === "Embotellado") {
        if ($estado_actual === 'barrica') {
            $errores[] = "No puedes embotellar directamente desde barrica. El lote debe pasar a depósito antes.";
        }
    }

    // Si no hay errores → insertar
    if (empty($errores)) {

        // Insertar evento
        $stmt = $conn->prepare("
            INSERT INTO trazabilidad 
            (id_lote, tipo_evento, fecha, descripcion, id_barrica, id_deposito)
            VALUES 
            (:lote, :tipo, :fecha, :descripcion, :barrica, :deposito)
        ");

        $stmt->execute([
            ':lote' => $id_lote,
            ':tipo' => $tipo,
            ':fecha' => $fecha,
            ':descripcion' => $descripcion,
            ':barrica' => $barrica,
            ':deposito' => $deposito
        ]);

        // ===============================
        // ACTUALIZAR ESTADO DEL LOTE
        // ===============================

        $nuevo_estado = $estado_actual;
        $nuevo_recipiente = $id_recipiente_actual;

        if ($tipo === "Entrada en barrica") {
            $nuevo_estado = 'barrica';
            $nuevo_recipiente = $barrica;
        }

        if ($tipo === "Salida de barrica") {
            $nuevo_estado = 'deposito';
            $nuevo_recipiente = $deposito;
        }

        if ($tipo === "Movimiento") {
            $nuevo_estado = 'deposito';
            $nuevo_recipiente = $deposito;
        }

        if ($tipo === "Trasiego") {
            if ($estado_actual === 'barrica') {
                $nuevo_estado = 'barrica';
                $nuevo_recipiente = $barrica;
            } elseif ($estado_actual === 'deposito') {
                $nuevo_estado = 'deposito';
                $nuevo_recipiente = $deposito;
            }
        }

        // Análisis y Embotellado no cambian estado_actual ni id_recipiente_actual

        $stmt = $conn->prepare("
            UPDATE lotes
            SET estado_actual = :estado, id_recipiente_actual = :recipiente, fecha_actualizacion = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':estado' => $nuevo_estado,
            ':recipiente' => $nuevo_recipiente,
            ':id' => $id_lote
        ]);

        header("Location: /personal_lote.php?id=$id_lote&ok=1");
        exit;
    }
}

// Obtener trazabilidad existente
$stmt = $conn->prepare("
    SELECT * FROM trazabilidad
    WHERE id_lote = :id
    ORDER BY fecha ASC
");
$stmt->execute([':id' => $id_lote]);
$trazabilidad = $stmt->fetchAll();
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="personal-lote-contenedor">

    <a href="/personal.php"
       style="display:inline-block; margin-bottom:15px; padding:8px 12px; background:#ddd; border-radius:4px; text-decoration:none; color:#333;">
        ← Volver al panel
    </a>

    <h1 class="personal-titulo">
        Añadir información al lote: <?php echo htmlspecialchars($lote['codigo_lote']); ?>
    </h1>

    <?php
    $estado = $lote['estado_actual'];
    $recipiente = $lote['id_recipiente_actual'];

    $icono = "⚪";
    $clase = "estado-ninguno";
    $texto = "Sin recipiente asignado";

    if ($estado === "barrica") {
        $icono = "🟤";
        $clase = "estado-barrica";
        $texto = "En barrica (ID $recipiente)";
    }

    if ($estado === "deposito") {
        $icono = "🔵";
        $clase = "estado-deposito";
        $texto = "En depósito (ID $recipiente)";
    }
    ?>

    <div class="estado-actual <?php echo $clase; ?>">
        <span class="icono"><?php echo $icono; ?></span>
        <span class="texto"><?php echo htmlspecialchars($texto); ?></span>
    </div>

    <section class="personal-formulario">

        <h2>Añadir evento de trazabilidad</h2>

        <?php if (!empty($errores)): ?>
            <div style="background:#ffe5e5; border-left:5px solid #ff4d4d; padding:12px; margin-bottom:20px; color:#b30000; font-weight:bold;">
                Se han encontrado errores:<br>
                <?php foreach ($errores as $e): ?>
                    • <?php echo htmlspecialchars($e); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-evento">

            <label>Tipo de evento</label>
            <select name="tipo_evento" id="tipo_evento" required>
                <option value="">Seleccionar evento</option>
                <?php foreach ($tipos_evento_validos as $t): ?>
                    <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                <?php endforeach; ?>
            </select>

            <label>Fecha</label>
            <input type="date" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>

            <label>Descripción (opcional)</label>
            <textarea name="descripcion" class="textarea-control"></textarea>

            <label>Barrica (opcional)</label>
            <select name="id_barrica" id="id_barrica">
                <option value="">Ninguna</option>
                <?php foreach ($lista_barricas as $b): ?>
                    <option value="<?php echo $b['id']; ?>">
                        <?php echo $b['id'] . " - " . ($b['codigo'] ?? "Barrica"); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Depósito (opcional)</label>
            <select name="id_deposito" id="id_deposito">
                <option value="">Ninguno</option>
                <?php foreach ($lista_depositos as $d): ?>
                    <option value="<?php echo $d['id']; ?>">
                        <?php echo $d['id'] . " - " . ($d['codigo'] ?? "Depósito"); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-guardar">Guardar evento</button>

        </form>

    </section>

    <section class="personal-trazabilidad">
        <h2>Eventos existentes</h2>

        <?php if (empty($trazabilidad)): ?>
            <p>No hay eventos registrados.</p>
        <?php else: ?>
            <ul class="timeline">
                <?php foreach ($trazabilidad as $t): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($t['tipo_evento']); ?></strong>
                        <br>
                        Fecha: <?php echo date("d/m/Y", strtotime($t['fecha'])); ?>

                        <?php if ($t['descripcion']): ?>
                            <p><?php echo nl2br(htmlspecialchars($t['descripcion'])); ?></p>
                        <?php endif; ?>

                        <?php if ($t['id_barrica']): ?>
                            <p><strong>Barrica:</strong> <?php echo $t['id_barrica']; ?></p>
                        <?php endif; ?>

                        <?php if ($t['id_deposito']): ?>
                            <p><strong>Depósito:</strong> <?php echo $t['id_deposito']; ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {

    const ultimoBarrica = <?php echo $ultimo_barrica ? $ultimo_barrica : 'null'; ?>;
    const ultimoDeposito = <?php echo $ultimo_deposito ? $ultimo_deposito : 'null'; ?>;

    const selectTipo = document.getElementById("tipo_evento");
    const selectBarrica = document.getElementById("id_barrica");
    const selectDeposito = document.getElementById("id_deposito");

    function sugerirRecipiente() {

        const tipo = selectTipo.value;

        // Reset sugerencias
        selectBarrica.value = "";
        selectDeposito.value = "";

        const eventosBarrica = [
            "Entrada en barrica",
            "Salida de barrica",
            "Trasiego"
        ];

        const eventosDeposito = [
            "Movimiento",
            "Filtrado",
            "Clarificación"
        ];

        if (eventosBarrica.includes(tipo)) {
            if (ultimoBarrica !== null) {
                selectBarrica.value = ultimoBarrica;
            }
        }

        if (eventosDeposito.includes(tipo)) {
            if (ultimoDeposito !== null) {
                selectDeposito.value = ultimoDeposito;
            }
        }
    }

    selectTipo.addEventListener("change", sugerirRecipiente);
});
</script>
