<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['admin']);

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error403();
}

$id = $_POST['id'] ?? null;

if (!$id || !ctype_digit($id)) {
    error404();
}

$id = intval($id);

$db = new Database();
$conn = $db->getConnection();

// Evitar que un admin se elimine a sí mismo
if ($id == ($_SESSION['usuario'] ?? null)) {
    error403();
}

// Verificar que el usuario existe
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    error404();
}

// Eliminar sin restricciones
$stmt = $conn->prepare("DELETE FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $id]);

header("Location: /admin/usuarios.php?eliminado=1");
exit;
