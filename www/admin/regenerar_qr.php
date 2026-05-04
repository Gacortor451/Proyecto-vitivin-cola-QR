<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['admin']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/generarQR.php';


$db = new Database();
$conn = $db->getConnection();

// Obtener lotes sin QR
$stmt = $conn->query("SELECT id FROM lotes WHERE qr_url IS NULL OR qr_url = ''");
$lotes = $stmt->fetchAll();

foreach ($lotes as $l) {
    $id = $l['id'];

    // Generar QR
    $urlPublica = generarQRlote($id);

    // Guardar en BD
    $update = $conn->prepare("UPDATE lotes SET qr_url = :url WHERE id = :id");
    $update->execute([
        ':url' => $urlPublica,
        ':id'  => $id
    ]);

    echo "QR generado para lote $id → $urlPublica<br>";
}

echo "<br>Proceso completado.";
