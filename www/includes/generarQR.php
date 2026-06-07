<?php

require_once __DIR__ . '/../libs/phpqrcode/qrlib.php';

function generarQRlote($idLote) {

    // URL FIJA para los QR (Host-Only)
    $baseUrl = "http://192.168.56.105:8080/lote.php?id=";

    // Carpeta donde se guardan los QR
    $dir = $_SERVER['DOCUMENT_ROOT'] . "/qr/lotes/";

    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    // URL final del QR
    $urlQR = $baseUrl . $idLote;

    // Ruta física del archivo
    $file = $dir . "qr_lote_" . $idLote . ".png";

    // Generar QR
    QRcode::png($urlQR, $file, QR_ECLEVEL_L, 6, 4);

    // Ruta pública accesible desde el navegador
    return "/qr/lotes/qr_lote_" . $idLote . ".png";
}
