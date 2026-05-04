<?php

require_once __DIR__ . '/../phpqrcode/qrlib.php';

function generarQRlote($idLote) {

    // URL base dinámica
    $urlBase = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/lote.php?id=';

    // Carpeta donde se guardan los QR
    $dir = $_SERVER['DOCUMENT_ROOT'] . "/qr/lotes/";

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
    return "/qr/lotes/qr_lote_" . $idLote . ".png";
}
