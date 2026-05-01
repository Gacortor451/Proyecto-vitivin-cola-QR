<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Solo administradores
if (!estaLogueado() || getRolActual() !== 'admin') {
    header("Location: /login.php");
    exit;
}

$id_incidencia = $_POST['id_incidencia'] ?? null;
$id_lote = $_POST['id_lote'] ?? null;

if (!$id_incidencia || !$id_lote) {
    die("Datos incompletos.");
}

$db = new Database();
$conn = $db->getConnection();

// ============================
// 1. ACTUALIZAR DATOS DEL LOTE
// ============================

$stmt = $conn->prepare("
    UPDATE lotes SET
        codigo_lote = :codigo_lote,
        variedad_uva = :variedad_uva,
        fecha_cosecha = :fecha_cosecha,
        bodega = :bodega,
        nombre_producto = :nombre_producto,
        fecha_produccion = :fecha_produccion,
        graduacion_alcoholica = :graduacion_alcoholica,
        acidez = :acidez,
        ph = :ph,
        sulfuroso_total = :sulfuroso_total,
        descripcion = :descripcion
    WHERE id = :id
");

$stmt->execute([
    ':codigo_lote' => $_POST['codigo_lote'] ?? '',
    ':variedad_uva' => $_POST['variedad_uva'] ?? '',
    ':fecha_cosecha' => $_POST['fecha_cosecha'] ?? null,
    ':bodega' => $_POST['bodega'] ?? '',
    ':nombre_producto' => $_POST['nombre_producto'] ?? '',
    ':fecha_produccion' => $_POST['fecha_produccion'] ?? null,
    ':graduacion_alcoholica' => $_POST['graduacion_alcoholica'] ?? '',
    ':acidez' => $_POST['acidez'] ?? '',
    ':ph' => $_POST['ph'] ?? '',
    ':sulfuroso_total' => $_POST['sulfuroso_total'] ?? '',
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
