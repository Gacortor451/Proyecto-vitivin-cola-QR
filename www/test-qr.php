<?php
require_once __DIR__ . '/libs/phpqrcode/qrlib.php';

// URL que quieres codificar
$url = "https://192.168.56.105";

// Carpeta donde se guardará el QR
$dir = __DIR__ . "/qr/";

// Crear carpeta si no existe
if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
}

// Ruta completa del archivo
$file = $dir . "test.png";

// Generar el QR
QRcode::png($url, $file, QR_ECLEVEL_L, 6, 4);

echo "QR generado en: " . $file;
