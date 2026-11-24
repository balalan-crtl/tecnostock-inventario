<?php
require_once 'includes/auth.php';
require_once 'config/db.php';

// 1. Cambiar estado (activar / desactivar producto)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_producto'])) {
    $id_prod      = (int)$_POST['id_producto'];
    $nuevo_estado = $_POST['nuevo_estado'];

    $sql = "UPDATE productos SET estado = ? WHERE id_producto = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nuevo_estado, $id_prod]);

    $_SESSION['mensaje'] = 'Estado del producto actualizado correctamente.';
    header('Location: index.php');
    exit;
}

// 2. Filtros de búsqueda
$buscar    = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;

$sql = "SELECT p.*, c.nombre AS categoria_nombre
        FROM productos p
        INNER JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE 1=1";
$parametros = [];

if ($buscar != '') {
    $sql .= " AND (p.nombre LIKE ? OR p.codigo LIKE ?)";
    $parametros[] = "%$buscar%";
    $parametros[] = "%$buscar%";
}

if ($categoria > 0) {
    $sql .= " AND p.id_categoria = ?";
    $parametros[] = $categoria;
}

$sql .= " ORDER BY p.estado ASC, p.nombre ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular indicadores KPI sobre los productos listados
$total_activos       = 0;
$total_unidades      = 0;
$valor_inventario    = 0;
$cantidad_bajo_stock = 0;

foreach ($productos as $p) {
    if ($p['estado'] == 'activo') {
        $total_activos++;
        $total_unidades   += $p['stock_actual'];
        $valor_inventario += ($p['precio'] * $p['stock_actual']);
        if ($p['stock_actual'] < $p['stock_minimo']) {
            $cantidad_bajo_stock++;
        }
    }
}

$categorias = $pdo->query("SELECT * FROM categorias WHERE estado = 'activo'")->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<!-- Encabezado de sección -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Inventario de Productos</h4>
        <p class="text-secondary small mb-0">Control centralizado de catálogo, existencias y reposición.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="movimientos.php" class="btn btn-outline-secondary btn-sm px-3 py-2">
            <i class="bi bi-arrow-left-right me-1"></i>Registrar Movimiento
        </a>
        <a href="producto_form.php" class="btn btn-slate btn-sm px-3 py-2">
            <i class="bi bi-plus-lg me-1"></i>Nuevo Producto
        </a>
    </div>
</div>

<!-- Tarjetas KPI -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card-clean p-3">
            <div class="text-secondary small mb-1">SKUs Activos</div>
            <div class="fs-4 fw-bold num-tabular"><?php echo $total_activos; ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card-clean p-3">
            <div class="text-secondary small mb-1">Unidades en Stock</div>
            <div class="fs-4 fw-bold num-tabular"><?php echo number_format($total_unidades, 0, ',', '.'); ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card-clean p-3">
            <div class="text-secondary small mb-1">Valorización Stock</div>
            <div class="fs-4 fw-bold num-tabular">$<?php echo number_format($valor_inventario, 0, ',', '.'); ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card-clean p-3">
            <div class="text-secondary small mb-1">Reposición Requerida</div>
            <div class="fs-4 fw-bold num-tabular <?php if ($cantidad_bajo_stock > 0) echo 'text-danger'; ?>">
                <?php echo $cantidad_bajo_stock; ?>
            </div>
        </div>
    </div>
</div>

<!-- Alerta de stock bajo -->
<?php if ($cantidad_bajo_stock > 0): ?>
    <div class="callout-danger p-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill fs-5 me-3 text-danger"></i>
            <div>
                <strong class="d-block">Alerta de stock bajo</strong>
                <span class="small">Hay <?php echo $cantidad_bajo_stock; ?> producto(s) activo(s) con existencias por debajo del mínimo definido.</span>
            </div>
        </div>
        <a href="movimientos.php" class="btn btn-sm btn-outline-danger">Reponer stock</a>
    </div>
<?php endif; ?>

<!-- Barra de filtros -->
<div class="card-clean p-3 mb-4">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="buscar" class="form-control border-start-0"
                       placeholder="Buscar por código SKU o nombre..."
                       value="<?php echo htmlspecialchars($buscar); ?>">
            </div>
        </div>
        <div class="col-md-4">
            <select name="categoria" class="form-select">
                <option value="0">Todas las categorías</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo $cat['id_categoria']; ?>" <?php if ($categoria == $cat['id_categoria']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-slate w-100">Filtrar</button>
            <a href="index.php" class="btn btn-outline-secondary">Limpiar</a>
        </div>
    </form>
</div>

<!-- Tabla de productos -->
<div class="card-clean overflow-hidden">
    <div class="table-responsive">
        <table class="table table-clean table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-3">SKU</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th class="text-end">Precio Unit.</th>
                    <th class="text-center">Disponible</th>
                    <th class="text-center">Mínimo</th>
                    <th class="text-center">Estado</th>
                    <th class="text-end pe-3">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($productos) == 0): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-secondary">
                            No se encontraron productos con los filtros aplicados.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($productos as $p): ?>
                    <?php
                    $bajo_stock = ($p['estado'] == 'activo' && $p['stock_actual'] < $p['stock_minimo']);
                    ?>
                    <tr class="<?php if ($p['estado'] == 'inactivo') echo 'opacity-50'; ?>">
                        <td class="ps-3"><span class="sku-code"><?php echo htmlspecialchars($p['codigo']); ?></span></td>
                        <td>
                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($p['nombre']); ?></div>
                            <?php if ($p['descripcion'] != ''): ?>
                                <div class="text-secondary small text-truncate" style="max-width: 280px;">
                                    <?php echo htmlspecialchars($p['descripcion']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><span class="text-secondary small"><?php echo htmlspecialchars($p['categoria_nombre']); ?></span></td>
                        <td class="text-end num-tabular fw-medium">$<?php echo number_format($p['precio'], 0, ',', '.'); ?></td>
                        <td class="text-center num-tabular">
                            <span class="fw-bold <?php if ($bajo_stock) echo 'text-danger'; ?>">
                                <?php echo $p['stock_actual']; ?>
                            </span>
                            <?php if ($bajo_stock): ?>
                                <div class="mt-1">
                                    <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle" style="font-size: 0.7rem;">
                                        Stock bajo
                                    </span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center num-tabular text-secondary"><?php echo $p['stock_minimo']; ?></td>
                        <td class="text-center">
                            <?php if ($p['estado'] == 'activo'): ?>
                                <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle">Activo</span>
                            <?php else: ?>
                                <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-3 text-nowrap">
                            <a href="producto_form.php?id=<?php echo $p['id_producto']; ?>" class="btn btn-outline-secondary btn-sm" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>

                            <form method="POST" class="d-inline form-toggle-estado">
                                <input type="hidden" name="id_producto" value="<?php echo $p['id_producto']; ?>">
                                <?php if ($p['estado'] == 'activo'): ?>
                                    <input type="hidden" name="nuevo_estado" value="inactivo">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Desactivar">
                                        <i class="bi bi-slash-circle"></i>
                                    </button>
                                <?php else: ?>
                                    <input type="hidden" name="nuevo_estado" value="activo">
                                    <button type="submit" class="btn btn-outline-success btn-sm" title="Activar">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>