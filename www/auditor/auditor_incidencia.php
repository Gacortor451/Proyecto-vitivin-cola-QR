<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['auditor', 'admin']);

require_once __DIR__ . '/../config/database.php';

// El auditor NO debe guardar ultimo_lote
unset($_SESSION['ultimo_lote']);

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /auditor/auditor.php");
    exit;
}

// Asegurar que id_lote SIEMPRE sea un entero válido
$id_lote = isset($_POST['id_lote']) ? intval($_POST['id_lote']) : 0;
$descripcion = trim($_POST['descripcion'] ?? '');

// Validación segura
if ($id_lote <= 0 || $descripcion === '') {
    error404(); // Datos inválidos → 404
}

$db = new Database();
$conn = $db->getConnection();

// Verificar que el lote existe antes de insertar
$stmt = $conn->prepare("SELECT id FROM lotes WHERE id = :id");
$stmt->execute([':id' => $id_lote]);
if (!$stmt->fetch()) {
    error404(); // Lote inexistente → 404
}

// ID del auditor/admin que crea la incidencia
$id_usuario_creador = $_SESSION['usuario'];

// Insertar incidencia
$stmt = $conn->prepare("
    INSERT INTO incidencias (id_lote, id_usuario_creador, descripcion, estado, fecha)
    VALUES (:lote, :creador, :descripcion, 'Pendiente', NOW())
");

$stmt->execute([
    ':lote' => $id_lote,
    ':creador' => $id_usuario_creador,
    ':descripcion' => $descripcion
]);

// Redirigir de vuelta a la vista del lote
header("Location: /auditor/auditor_lote.php?id=" . $id_lote);
exit;
