<?php

require_once __DIR__ . '/../phpqrcode/qrlib.php';

function generarQRlote($idLote, $urlBase = "https://192.168.56.105/lote.php?id=") {

    // Ruta física donde se guarda el archivo
    $dir = __DIR__ . "/../../qr/lotes/";

    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    // URL que contendrá el QR
    $urlQR = $urlBase . $idLote;

    // Ruta física del archivo
    $file = $dir . "qr_lote_" . $idLote . ".png";

    // Generar QR en el archivo físico
    QRcode::png($urlQR, $file, QR_ECLEVEL_L, 6, 4);

    // Ruta pública accesible desde el navegador
    $urlPublica = "/qr/lotes/qr_lote_" . $idLote . ".png";

    return $urlPublica;
}
