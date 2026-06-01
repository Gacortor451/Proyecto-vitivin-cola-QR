<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['auditor', 'admin']);

require_once __DIR__ . '/../config/database.php';

/* ============================================================
   PROTECCIÓN DEL FLUJO DEL QR
   ============================================================ */

// El auditor NO debe guardar ultimo_lote
unset($_SESSION['ultimo_lote']);

// No sobrescribir redirect_after_login si viene de un QR
if (!empty($_SESSION['redirect_after_login']) && str_contains($_SESSION['redirect_after_login'], 'lote.php')) {
    // Mantener el QR
} else {
    $_SESSION['redirect_after_login'] = null;
}

/* ============================================================
   OBTENER ID DEL LOTE (siempre entero)
   ============================================================ */

$id_lote = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_lote <= 0) {
    error404();
}

$db = new Database();
$conn = $db->getConnection();

/* ============================================================
   1. OBTENER DATOS DEL LOTE
   ============================================================ */

$stmt = $conn->prepare("SELECT * FROM lotes WHERE id = :id");
$stmt->execute([':id' => $id_lote]);
$lote = $stmt->fetch();

if (!$lote) {
    error404();
}

/* ============================================================
   2. OBTENER TRAZABILIDAD
   ============================================================ */

$stmt = $conn->prepare("
    SELECT * FROM trazabilidad 
    WHERE id_lote = :id 
    ORDER BY fecha ASC
");
$stmt->execute([':id' => $id_lote]);
$trazabilidad = $stmt->fetchAll();

/* ============================================================
   3. OBTENER INCIDENCIAS
   ============================================================ */

$stmt = $conn->prepare("
    SELECT i.*, u.nombre AS creador
    FROM incidencias i
    JOIN usuarios u ON u.id = i.id_usuario_creador
    WHERE i.id_lote = :id
    ORDER BY fecha DESC
");
$stmt->execute([':id' => $id_lote]);
$incidencias = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="auditor-lote-contenedor">

    <h1 class="auditor-lote-titulo">
        Auditoría del lote: <?php echo htmlspecialchars($lote['codigo_lote']); ?>
    </h1>

    <!-- BOTÓN VOLVER AL PANEL -->
    <a href="/auditor/auditor.php"
       style="display:inline-block; margin-bottom:15px; padding:8px 12px; background:#ddd; border-radius:4px; text-decoration:none; color:#333;">
        ← Volver al panel
    </a>

    <!-- QR DEL LOTE -->
    <section class="auditor-card">
        <h2 class="auditor-subtitulo">QR del lote</h2>

        <?php if (!empty($lote['qr_url'])): ?>
            <div class="auditor-qr-contenedor">
                <img src="<?php echo htmlspecialchars($lote['qr_url']); ?>" 
                     alt="QR del lote" 
                     class="auditor-qr-imagen">
            </div>
        <?php else: ?>
            <p>No hay QR generado para este lote.</p>
        <?php endif; ?>
    </section>

    <!-- DATOS DEL LOTE -->
    <section class="auditor-card">
        <h2 class="auditor-subtitulo">Datos del lote</h2>

        <div class="auditor-datos-grid">

            <p><strong>Variedad de uva:</strong> 
                <?php echo htmlspecialchars($lote['variedad_uva'] ?? 'No especificado'); ?>
            </p>

            <p><strong>Fecha de cosecha:</strong> 
                <?php echo htmlspecialchars($lote['fecha_cosecha'] ?? 'No especificado'); ?>
            </p>

            <p><strong>Bodega:</strong> 
                <?php echo htmlspecialchars($lote['bodega'] ?? 'No especificado'); ?>
            </p>

            <p><strong>Nombre del producto:</strong> 
                <?php echo htmlspecialchars($lote['nombre_producto'] ?? 'No especificado'); ?>
            </p>

            <p><strong>Fecha de producción:</strong> 
                <?php echo htmlspecialchars($lote['fecha_produccion'] ?? 'No especificado'); ?>
            </p>

            <p><strong>Graduación alcohólica:</strong> 
                <?php echo htmlspecialchars($lote['graduacion_alcoholica'] ?? 'No especificado'); ?>
            </p>

            <p><strong>Acidez:</strong> 
                <?php echo htmlspecialchars($lote['acidez'] ?? 'No especificado'); ?>
            </p>

            <p><strong>pH:</strong> 
                <?php echo htmlspecialchars($lote['ph'] ?? 'No especificado'); ?>
            </p>

            <p><strong>Sulfuroso total:</strong> 
                <?php echo htmlspecialchars($lote['sulfuroso_total'] ?? 'No especificado'); ?>
            </p>

        </div>

        <p class="auditor-descripcion">
            <strong>Descripción:</strong><br>
            <?php echo nl2br(htmlspecialchars($lote['descripcion'] ?? 'No especificado')); ?>
        </p>
    </section>

    <!-- TRAZABILIDAD -->
    <section class="auditor-card">
        <h2 class="auditor-subtitulo">Trazabilidad del lote</h2>

        <?php if (empty($trazabilidad)): ?>
            <p>No hay eventos registrados.</p>
        <?php else: ?>
            <ul class="timeline">
                <?php foreach ($trazabilidad as $evento): ?>
                    <li>
                        <div class="timeline-etapa">
                            <h3><?php echo htmlspecialchars($evento['tipo_evento']); ?></h3>

                            <p><strong>Fecha:</strong> 
                                <?php echo date("d/m/Y", strtotime($evento['fecha'])); ?>
                            </p>

                            <?php if (!empty($evento['descripcion'])): ?>
                                <p><?php echo nl2br(htmlspecialchars($evento['descripcion'])); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($evento['id_barrica'])): ?>
                                <p><strong>Barrica:</strong> <?php echo $evento['id_barrica']; ?></p>
                            <?php endif; ?>

                            <?php if (!empty($evento['id_deposito'])): ?>
                                <p><strong>Depósito:</strong> <?php echo $evento['id_deposito']; ?></p>
                            <?php endif; ?>

                            <?php if (!empty($evento['id_partida'])): ?>
                                <p><strong>Partida:</strong> <?php echo $evento['id_partida']; ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <!-- INCIDENCIAS -->
    <section class="auditor-card">
        <h2 class="auditor-subtitulo">Incidencias del lote</h2>

        <?php if (empty($incidencias)): ?>
            <p>No hay incidencias registradas.</p>
        <?php else: ?>
            <?php foreach ($incidencias as $inc): ?>
                <div class="incidencia-card">
                    <p><strong>Estado:</strong> <?php echo htmlspecialchars($inc['estado']); ?></p>

                    <p><strong>Fecha:</strong> 
                        <?php echo date("d/m/Y H:i", strtotime($inc['fecha'])); ?>
                    </p>

                    <p><strong>Creada por:</strong> <?php echo htmlspecialchars($inc['creador']); ?></p>

                    <p><strong>Descripción:</strong><br>
                        <?php echo nl2br(htmlspecialchars($inc['descripcion'])); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- VALIDAR LOTE -->
    <?php if ($lote['estado_auditoria'] === 'pendiente'): ?>
        <form method="POST" action="/auditor/validar_lote.php" 
              style="margin-bottom:15px; text-align:right;">

            <input type="hidden" name="id_lote" value="<?php echo $id_lote; ?>">

            <button 
                style="padding:8px 12px; background:#4CAF50; color:white; border:none; border-radius:4px; cursor:pointer;">
                ✔ Validar lote
            </button>
        </form>
    <?php else: ?>
        <p style="margin-bottom:15px; font-weight:bold; color:green;">
            ✔ Este lote ya está validado
        </p>
    <?php endif; ?>

    <!-- CREAR INCIDENCIA -->
    <section class="auditor-card">
        <h2 class="auditor-subtitulo">Crear incidencia</h2>

        <form method="POST" action="/auditor/auditor_incidencia.php" class="auditor-form">

            <input type="hidden" name="id_lote" value="<?php echo $id_lote; ?>">

            <label>Descripción de la incidencia</label>
            <textarea name="descripcion" class="textarea-control" required></textarea>

            <button type="submit" class="auditor-btn-crear">Crear incidencia</button>
        </form>
    </section>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
