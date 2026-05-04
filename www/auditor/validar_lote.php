<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['auditor', 'admin']);

require_once __DIR__ . '/../config/database.php';

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /auditor/auditor.php");
    exit;
}

$id_lote = $_POST['id_lote'] ?? null;

if (!$id_lote) {
    die("ID de lote no recibido.");
}

$db = new Database();
$conn = $db->getConnection();

// Validar lote
$stmt = $conn->prepare("
    UPDATE lotes
    SET estado_auditoria = 'validado',
        fecha_validacion = NOW()
    WHERE id = :id
");

$stmt->execute([':id' => $id_lote]);

// Volver al panel del auditor
header("Location: /auditor/auditor.php");
exit;
