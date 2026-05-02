<?php

require_once __DIR__ . '/../phpqrcode/qrlib.php';

function generarQRlote($idLote, $urlBase = "https://192.168.56.105/lote.php?id=") {

    $dir = __DIR__ . "/../../qr/lotes/";

    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    $urlQR = $urlBase . $idLote;

    $file = $dir . "qr_lote_" . $idLote . ".png";

    QRcode::png($urlQR, $file, QR_ECLEVEL_L, 6, 4);

    return $file;
}

