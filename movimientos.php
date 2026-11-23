<?php
require_once 'includes/auth.php';
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_producto = (int)$_POST['id_producto'];
    $tipo        = $_POST['tipo'];
    $cantidad    = (int)$_POST['cantidad'];
    $observacion = trim($_POST['observacion']);
    $id_usuario  = (int)$_SESSION['id_usuario'];

    $stmt = $pdo->prepare("SELECT nombre, stock_actual FROM productos WHERE id_producto = ?");
    $stmt->execute([$id_producto]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto || $cantidad <= 0) {
        $error = 'Selecciona un producto e ingresa una cantidad válida mayor a 0.';
    } elseif ($tipo == 'salida' && $cantidad > $producto['stock_actual']) {
        $error = 'Stock insuficiente. Disponible: ' . $producto['stock_actual'];
    } else {
        if ($tipo == 'entrada') {
            $nuevo_stock = $producto['stock_actual'] + $cantidad;
        } else {
            $nuevo_stock = $producto['stock_actual'] - $cantidad;
        }

        // 1. Guardar el movimiento
        $sqlMov = "INSERT INTO movimientos (tipo, cantidad, observacion, id_producto, id_usuario) VALUES (?, ?, ?, ?, ?)";
        $stmtMov = $pdo->prepare($sqlMov);
        $stmtMov->execute([$tipo, $cantidad, $observacion, $id_producto, $id_usuario]);

        // 2. Actualizar el stock del producto
        $sqlUpd = "UPDATE productos SET stock_actual = ? WHERE id_producto = ?";
        $stmtUpd = $pdo->prepare($sqlUpd);
        $stmtUpd->execute([$nuevo_stock, $id_producto]);

        $_SESSION['mensaje'] = 'Movimiento registrado correctamente.';
        header('Location: movimientos.php');
        exit;
    }
}

$productos = $pdo->query("SELECT * FROM productos WHERE estado = 'activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

$historial = $pdo->query("SELECT m.*, p.codigo, p.nombre AS producto_nombre, u.nombre AS usuario_nombre
                          FROM movimientos m
                          INNER JOIN productos p ON m.id_producto = p.id_producto
                          INNER JOIN usuarios u ON m.id_usuario = u.id_usuario
                          ORDER BY m.fecha_hora DESC")->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white"><h5 class="mb-0">Registrar Movimiento</h5></div>
            <div class="card-body">
                <?php if ($error != ''): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" id="formMovimiento">
                    <div class="mb-3">
                        <label class="form-label">Producto *</label>
                        <select id="id_producto" name="id_producto" class="form-select" required>
                            <option value="" data-stock="0">Seleccione...</option>
                            <?php foreach ($productos as $p): ?>
                                <option value="<?php echo $p['id_producto']; ?>" data-stock="<?php echo $p['stock_actual']; ?>">
                                    <?php echo htmlspecialchars($p['codigo'] . ' - ' . $p['nombre'] . ' (Stock: ' . $p['stock_actual'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo *</label>
                        <select id="tipo" name="tipo" class="form-select" required>
                            <option value="entrada">Entrada</option>
                            <option value="salida">Salida</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cantidad *</label>
                        <input type="number" id="cantidad" name="cantidad" min="1" class="form-control" required>
                        <small class="text-danger d-none" id="alertaStockJS"></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observación</label>
                        <input type="text" name="observacion" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar Movimiento</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white"><h5 class="mb-0">Historial de Movimientos</h5></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Usuario</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $m): ?>
                            <tr>
                                <td><?php echo $m['fecha_hora']; ?></td>
                                <td><?php echo htmlspecialchars($m['codigo'] . ' - ' . $m['producto_nombre']); ?></td>
                                <td>
                                    <?php if ($m['tipo'] == 'entrada'): ?>
                                        <span class="badge bg-success">Entrada</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Salida</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold"><?php echo $m['cantidad']; ?></td>
                                <td><?php echo htmlspecialchars($m['usuario_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($m['observacion']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>