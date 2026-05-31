<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['admin']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$db = new Database();
$conn = $db->getConnection();

// ============================
// ESTADÍSTICAS
// ============================

$stmt = $conn->query("SELECT COUNT(*) FROM incidencias WHERE estado = 'Pendiente'");
$inc_pendientes = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM incidencias WHERE estado = 'Resuelto'");
$inc_resueltas = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM usuarios");
$total_usuarios = $stmt->fetchColumn();

$stmt = $conn->query("
    SELECT r.nombre AS rol, COUNT(*) AS total
    FROM usuarios u
    JOIN roles r ON r.id = u.id_rol
    GROUP BY r.nombre
    ORDER BY r.nombre ASC
");
$usuarios_por_rol = $stmt->fetchAll();

$stmt = $conn->query("SELECT COUNT(*) FROM lotes");
$total_lotes = $stmt->fetchColumn();

// ============================
// GENERAR HTML DEL INFORME
// ============================

ob_start();
?>

<link rel="stylesheet" href="/assets/css/style.css">

<style>
.informe-container { 
    max-width: 900px; 
    margin: 0 auto; 
    padding: 20px; 
    font-family: Arial, Helvetica, sans-serif;
}

.informe-titulo { 
    text-align: center; 
    font-size: 28px; 
    margin-bottom: 25px; 
    font-weight: 600;
}

.informe-subtitulo {
    margin-top: 35px;
    font-size: 20px;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #444;
    padding-bottom: 6px;
}

/* TABLA PROFESIONAL */
.informe-tabla {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    font-size: 15px;
    border: 1px solid #ccc;
}

.informe-tabla th {
    background: #f0f0f0;
    padding: 12px;
    border: 1px solid #ccc;
    font-weight: 600;
    text-align: left;
    color: #222;
}

.informe-tabla td {
    padding: 10px 12px;
    border: 1px solid #ddd;
    color: #333;
}

/* Filas alternas más suaves */
.informe-tabla tr:nth-child(even) {
    background: #fafafa;
}

/* Hover (solo para visualización, no afecta al PDF) */
.informe-tabla tr:hover {
    background: #f3f3f3;
}

/* Ajuste de columnas */
.informe-tabla td:first-child,
.informe-tabla th:first-child {
    width: 70%;
}

.informe-tabla td:last-child,
.informe-tabla th:last-child {
    width: 30%;
    text-align: right;
    font-weight: 600;
}
</style>


<div class="informe-container">

    <h1 class="informe-titulo">Informe del sistema</h1>

    <h2 class="informe-subtitulo">Resumen general</h2>

    <table class="informe-tabla">
        <tr><th>Incidencias pendientes</th><td><?= $inc_pendientes ?></td></tr>
        <tr><th>Incidencias resueltas</th><td><?= $inc_resueltas ?></td></tr>
        <tr><th>Total de usuarios</th><td><?= $total_usuarios ?></td></tr>
        <tr><th>Total de lotes</th><td><?= $total_lotes ?></td></tr>
    </table>

    <h2 class="informe-subtitulo">Usuarios por rol</h2>

    <table class="informe-tabla">
        <thead>
            <tr><th>Rol</th><th>Total</th></tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios_por_rol as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['rol']) ?></td>
                    <td><?= $r['total'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>

<?php
$html = ob_get_clean();

// ============================
// CONFIGURAR DOMPDF
// ============================

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Descargar PDF
$dompdf->stream("informe_sistema.pdf", ["Attachment" => true]);
exit;
