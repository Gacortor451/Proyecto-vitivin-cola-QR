<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['admin']);

require_once __DIR__ . '/../config/database.php';

$id_incidencia = $_POST['id_incidencia'] ?? null;
$id_lote = $_POST['id_lote'] ?? null;

if (!$id_incidencia || !$id_lote) {
    die("Datos incompletos.");
}

$db = new Database();
$conn = $db->getConnection();

$errores = [];

// ============================
// VALIDAR CAMPOS NUMÉRICOS
// ============================

function validarNumero($valor, $campo, &$errores) {
    if ($valor === '' || $valor === null) {
        return null;
    }
    if (!is_numeric($valor)) {
        $errores[] = "El campo '$campo' debe ser un número válido.";
        return null;
    }
    return $valor;
}

$graduacion = validarNumero($_POST['graduacion_alcoholica'] ?? null, "Graduación alcohólica", $errores);
$acidez = validarNumero($_POST['acidez'] ?? null, "Acidez", $errores);
$ph = validarNumero($_POST['ph'] ?? null, "pH", $errores);
$sulfuroso = validarNumero($_POST['sulfuroso_total'] ?? null, "Sulfuroso total", $errores);

// ============================
// SI HAY ERRORES → MOSTRARLOS
// ============================

if (!empty($errores)) {
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/admin_topbar.php';

    echo '<div style="background:#ffe5e5; border-left:5px solid #ff4d4d; padding:12px; margin:20px; color:#b30000; font-weight:bold;">';
    echo "<strong>Se han encontrado errores:</strong><br>";
    foreach ($errores as $e) {
        echo "• " . htmlspecialchars($e) . "<br>";
    }
    echo '</div>';

    echo '<a href="javascript:history.back()" class="admin-btn-crear">Volver</a>';

    include __DIR__ . '/../includes/footer.php';
    exit;
}

// ============================
// 1. ACTUALIZAR SOLO CAMPOS EDITABLES
// ============================

$stmt = $conn->prepare("
    UPDATE lotes SET
        graduacion_alcoholica = :graduacion,
        acidez = :acidez,
        ph = :ph,
        sulfuroso_total = :sulfuroso,
        descripcion = :descripcion
    WHERE id = :id
");

$stmt->execute([
    ':graduacion' => $graduacion,
    ':acidez' => $acidez,
    ':ph' => $ph,
    ':sulfuroso' => $sulfuroso,
    ':descripcion' => $_POST['descripcion'] ?? '',
    ':id' => $id_lote
]);

// ============================
// 2. MARCAR INCIDENCIA COMO RESUELTA
// ============================

$stmt = $conn->prepare("
    UPDATE incidencias
    SET estado = 'Resuelto'
    WHERE id = :id
");

$stmt->execute([':id' => $id_incidencia]);

// ============================
// 3. REDIRIGIR
// ============================

header("Location: /admin/incidencias.php?ok=1");
exit;
