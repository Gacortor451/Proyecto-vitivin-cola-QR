<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole(['auditor', 'admin']);

require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$resultados = [];
$busqueda = trim($_GET['q'] ?? '');

if ($busqueda !== '') {

    $stmt = $conn->prepare("
        SELECT id, codigo_lote, nombre_producto, bodega
        FROM lotes
        WHERE 
            codigo_lote ILIKE :q
            OR nombre_producto ILIKE :q
            OR bodega ILIKE :q
        ORDER BY codigo_lote ASC
    ");

    $stmt->execute([':q' => "%$busqueda%"]);
    $resultados = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="auditor-contenedor">

    <h1 class="auditor-titulo">Panel del Auditor</h1>

    <!-- ========================= -->
    <!-- BUSCADOR DE LOTES         -->
    <!-- ========================= -->
    <form method="GET" class="auditor-buscador">
        <input 
            type="text" 
            name="q" 
            placeholder="Buscar lote por código, producto o bodega..."
            value="<?php echo htmlspecialchars($busqueda ?? ''); ?>"
            class="auditor-input"
        >
        <button type="submit" class="auditor-btn">Buscar</button>
    </form>

    <!-- ========================= -->
    <!-- RESULTADOS DE BÚSQUEDA    -->
    <!-- ========================= -->
    <?php if ($busqueda !== ''): ?>
        <h2 class="auditor-subtitulo">
            Resultados para: "<?php echo htmlspecialchars($busqueda ?? ''); ?>"
        </h2>

        <?php if (empty($resultados)): ?>
            <p class="auditor-noresultados">No se encontraron lotes.</p>
        <?php else: ?>
            <ul class="auditor-lista">
                <?php foreach ($resultados as $l): ?>
                    <li class="auditor-item">
                        <div>
                            <strong><?php echo htmlspecialchars($l['codigo_lote'] ?? ''); ?></strong><br>
                            <?php echo htmlspecialchars($l['nombre_producto'] ?? 'No especificado'); ?><br>
                            <span class="auditor-bodega">
                                <?php echo htmlspecialchars($l['bodega'] ?? 'No especificado'); ?>
                            </span>
                        </div>

                        <a href="/auditor/auditor_lote.php?id=<?php echo htmlspecialchars($l['id']); ?>" 
                           class="auditor-ver-btn">
                           Ver lote
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

    <?php endif; ?>

    <!-- ========================= -->
    <!-- LOTES PENDIENTES (LISTA) -->
    <!-- ========================= -->
    <?php
    $stmt = $conn->query("
        SELECT id, codigo_lote, nombre_producto, bodega
        FROM lotes
        WHERE estado_auditoria = 'pendiente'
        ORDER BY id ASC
    ");
    $lotesPendientes = $stmt->fetchAll();
    ?>

    <h2 class="auditor-subtitulo">Lotes pendientes de validación</h2>

    <?php if (empty($lotesPendientes)): ?>
        <p>No hay lotes pendientes.</p>
    <?php else: ?>
        <ul class="auditor-lista">
            <?php foreach ($lotesPendientes as $l): ?>
                <li class="auditor-item">
                    <div>
                        <strong><?php echo htmlspecialchars($l['codigo_lote'] ?? ''); ?></strong><br>
                        <?php echo htmlspecialchars($l['nombre_producto'] ?? 'No especificado'); ?><br>
                        <span class="auditor-bodega">
                            <?php echo htmlspecialchars($l['bodega'] ?? 'No especificado'); ?>
                        </span>
                    </div>

                    <div style="display:flex; gap:0.5rem;">
                        <form method="POST" action="/auditor/validar_lote.php">
                            <input type="hidden" name="id_lote" value="<?php echo $l['id']; ?>">
                            <button class="auditor-ver-btn">Validar</button>
                        </form>

                        <a href="/auditor/auditor_lote.php?id=<?php echo $l['id']; ?>" 
                           class="auditor-ver-btn">
                           Ver lote
                        </a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- ========================= -->
    <!-- LOTES VALIDADOS (LISTA)  -->
    <!-- ========================= -->
    <?php
    $stmt = $conn->query("
        SELECT id, codigo_lote, fecha_validacion
        FROM lotes
        WHERE estado_auditoria = 'validado'
        ORDER BY fecha_validacion DESC
    ");
    $lotesValidados = $stmt->fetchAll();
    ?>

    <h2 class="auditor-subtitulo">Lotes validados</h2>

    <?php if (empty($lotesValidados)): ?>
        <p>No hay lotes validados todavía.</p>
    <?php else: ?>
        <ul class="auditor-lista">
            <?php foreach ($lotesValidados as $l): ?>
                <li class="auditor-item">
                    <div>
                        <strong><?php echo htmlspecialchars($l['codigo_lote'] ?? ''); ?></strong><br>
                        <span class="auditor-bodega">
                            Validado el: <?php echo date("d/m/Y H:i", strtotime($l['fecha_validacion'])); ?>
                        </span>
                    </div>

                    <a href="/auditor/auditor_lote.php?id=<?php echo $l['id']; ?>" 
                       class="auditor-ver-btn">
                       Ver lote
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
