<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['admin']);

require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Usuario no especificado.");
}

$db = new Database();
$conn = $db->getConnection();

// Evitar que un admin se elimine a sí mismo
if ($id == ($_SESSION['usuario'] ?? null)) {
    die("No puedes eliminar tu propio usuario.");
}

// Verificar que el usuario existe
$stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $id]);

if ($stmt->fetchColumn() == 0) {
    die("El usuario no existe.");
}

// Eliminar usuario
$stmt = $conn->prepare("DELETE FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $id]);

// Redirigir
header("Location: /admin/usuarios.php?eliminado=1");
exit;
