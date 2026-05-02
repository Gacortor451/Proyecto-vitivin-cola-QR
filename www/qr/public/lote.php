<?php
require_once "../db/conexion.php";

$id = $_GET['id'];

$sql = $pdo->prepare("SELECT * FROM lotes WHERE id = ?");
$sql->execute([$id]);
$lote = $sql->fetch();

if (!$lote) {
    echo "Lote no encontrado";
    exit;
}
?>

<h1>Lote <?= $lote['id'] ?></h1>
<p>Nombre: <?= $lote['nombre'] ?></p>
<p>Descripción: <?= $lote['descripcion'] ?></p>
