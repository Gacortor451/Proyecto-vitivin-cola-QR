<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Solo auditores
if (!estaLogueado() || getRolActual() !== 'auditor') {
    header("Location: /login.php");
    exit;
}

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
                            <strong>
                                <?php echo htmlspecialchars($l['codigo_lote'] ?? ''); ?>
                            </strong><br>

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

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
