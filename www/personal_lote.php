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

// Obtener datos del lote
$stmt = $conn->prepare("SELECT * FROM lotes WHERE id = :id");
$stmt->execute([':id' => $id_lote]);
$lote = $stmt->fetch();

if (!$lote) {
    error404();
}

// Procesar formulario
$errores = [];

// Validación numérica
function validarNumero($valor, $campo) {
    if ($valor === '' || $valor === null) return null;
    if (!ctype_digit($valor)) return "El campo $campo debe ser un número válido.";
    return null;
}

// Validar existencia de claves foráneas
function existe($conn, $tabla, $id) {
    $stmt = $conn->prepare("SELECT id FROM $tabla WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() !== false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tipo = trim($_POST['tipo_evento']);
    $fecha = trim($_POST['fecha']);
    $descripcion = trim($_POST['descripcion']);

    $barrica = $_POST['id_barrica'] ?: null;
    $deposito = $_POST['id_deposito'] ?: null;
    $partida = $_POST['id_partida'] ?: null;

    // Validación básica
    if ($tipo === '' || $fecha === '') {
        $errores[] = "El tipo de evento y la fecha son obligatorios.";
    }

    // Validación numérica real
    if ($error = validarNumero($barrica, "ID Barrica")) $errores[] = $error;
    if ($error = validarNumero($deposito, "ID Depósito")) $errores[] = $error;
    if ($error = validarNumero($partida, "ID Partida")) $errores[] = $error;

    // Validar claves foráneas
    if ($barrica !== null && !existe($conn, 'barricas', $barrica)) {
        $errores[] = "La barrica con ID $barrica no existe.";
    }

    if ($deposito !== null && !existe($conn, 'depositos', $deposito)) {
        $errores[] = "El depósito con ID $deposito no existe.";
    }

    if ($partida !== null && !existe($conn, 'partidas', $partida)) {
        $errores[] = "La partida con ID $partida no existe.";
    }

    // Si no hay errores → insertar
    if (empty($errores)) {

        $stmt = $conn->prepare("
            INSERT INTO trazabilidad 
            (id_lote, tipo_evento, fecha, descripcion, id_barrica, id_deposito, id_partida)
            VALUES 
            (:lote, :tipo, :fecha, :descripcion, :barrica, :deposito, :partida)
        ");

        $stmt->execute([
            ':lote' => $id_lote,
            ':tipo' => $tipo,
            ':fecha' => $fecha,
            ':descripcion' => $descripcion,
            ':barrica' => $barrica,
            ':deposito' => $deposito,
            ':partida' => $partida
        ]);

        header("Location: /personal.php");
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
            <input type="text" name="tipo_evento" required>

            <label>Fecha</label>
            <input type="date" name="fecha" required>

            <label>Descripción (opcional)</label>
            <textarea name="descripcion" class="textarea-control"></textarea>

            <label>ID Barrica (opcional)</label>
            <input type="number" inputmode="decimal" name="id_barrica">

            <label>ID Depósito (opcional)</label>
            <input type="number" inputmode="decimal" name="id_deposito">

            <label>ID Partida (opcional)</label>
            <input type="number" inputmode="decimal" name="id_partida">

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

                        <?php if ($t['id_partida']): ?>
                            <p><strong>Partida:</strong> <?php echo $t['id_partida']; ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
