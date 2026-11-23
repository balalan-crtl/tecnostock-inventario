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

    $_SESSION['mensaje'] = 'Estado del producto actualizado.';
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

// Contar productos activos con stock bajo
$cantidad_bajo_stock = 0;
foreach ($productos as $p) {
    if ($p['estado'] == 'activo' && $p['stock_actual'] < $p['stock_minimo']) {
        $cantidad_bajo_stock++;
    }
}

$categorias = $pdo->query("SELECT * FROM categorias WHERE estado = 'activo'")->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<?php if ($cantidad_bajo_stock > 0): ?>
    <div class="alert alert-danger">
        <strong>¡Alerta de stock bajo!</strong> Hay <?php echo $cantidad_bajo_stock; ?> producto(s) con stock menor al mínimo.
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-5">
                <input type="text" name="buscar" class="form-control" placeholder="Buscar por código o nombre..."
                       value="<?php echo htmlspecialchars($buscar); ?>">
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
                <button type="submit" class="btn btn-primary w-100">Buscar</button>
                <a href="index.php" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Listado de Productos</h5>
        <a href="producto_form.php" class="btn btn-success btn-sm">+ Nuevo Producto</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Mínimo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $p): ?>
                    <?php
                    $bajo_stock = ($p['estado'] == 'activo' && $p['stock_actual'] < $p['stock_minimo']);
                    ?>
                    <tr class="<?php if ($bajo_stock) echo 'table-danger'; ?>">
                        <td class="fw-bold"><?php echo htmlspecialchars($p['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($p['categoria_nombre']); ?></td>
                        <td>$<?php echo number_format($p['precio'], 0, ',', '.'); ?></td>
                        <td class="fw-bold">
                            <?php echo $p['stock_actual']; ?>
                            <?php if ($bajo_stock): ?>
                                <span class="badge bg-danger">Stock bajo</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $p['stock_minimo']; ?></td>
                        <td>
                            <?php if ($p['estado'] == 'activo'): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <a href="producto_form.php?id=<?php echo $p['id_producto']; ?>" class="btn btn-warning btn-sm">Editar</a>

                            <form method="POST" class="d-inline form-toggle-estado">
                                <input type="hidden" name="id_producto" value="<?php echo $p['id_producto']; ?>">
                                <?php if ($p['estado'] == 'activo'): ?>
                                    <input type="hidden" name="nuevo_estado" value="inactivo">
                                    <button type="submit" class="btn btn-danger btn-sm">Desactivar</button>
                                <?php else: ?>
                                    <input type="hidden" name="nuevo_estado" value="activo">
                                    <button type="submit" class="btn btn-success btn-sm">Activar</button>
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