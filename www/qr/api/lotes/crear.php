<?php

require_once "../../libs/qr/generarQR.php";
require_once "../../db/conexion.php";

// Recibir datos del lote
$nombre = $_POST['nombre'];
$descripcion = $_POST['descripcion'];

// Insertar lote
$sql = $pdo->prepare("INSERT INTO lotes (nombre, descripcion) VALUES (?, ?)");
$sql->execute([$nombre, $descripcion]);

$idLote = $pdo->lastInsertId();

// Generar QR
$rutaQR = generarQRlote($idLote);

// Guardar ruta del QR en la BD
$sql = $pdo->prepare("UPDATE lotes SET qr_path = ? WHERE id = ?");
$sql->execute([$rutaQR, $idLote]);

echo json_encode([
    "status" => "ok",
    "id" => $idLote,
    "qr" => $rutaQR
]);
