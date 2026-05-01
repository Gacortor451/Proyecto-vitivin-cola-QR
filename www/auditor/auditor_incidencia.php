<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Solo auditores
if (!estaLogueado() || getRolActual() !== 'auditor') {
    header("Location: /login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /auditor/auditor.php");
    exit;
}

$id_lote = $_POST['id_lote'] ?? null;
$descripcion = trim($_POST['descripcion'] ?? '');

if (!$id_lote || $descripcion === '') {
    die("Datos incompletos para crear la incidencia.");
}

$db = new Database();
$conn = $db->getConnection();

// ID del auditor que crea la incidencia
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
