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
        $error = 'Stock insuficiente. Existencias disponibles: ' . $producto['stock_actual'];
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

        $_SESSION['mensaje'] = 'Movimiento registrado y stock actualizado.';
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

<div class="mb-4">
    <h4 class="fw-bold mb-1">Control de Movimientos</h4>
    <p class="text-secondary small mb-0">Registro auditado de entradas y salidas de mercadería.</p>
</div>

<div class="row g-4">
    <!-- Formulario -->
    <div class="col-lg-4">
        <div class="card-clean">
            <div class="card-header">
                <h6 class="fw-bold mb-0">Registrar Operación</h6>
            </div>
            <div class="card-body p-4">
                <?php if ($error != ''): ?>
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-circle me-1"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="formMovimiento">
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-secondary">Producto *</label>
                        <select id="id_producto" name="id_producto" class="form-select" required>
                            <option value="" data-stock="0">Seleccionar producto...</option>
                            <?php foreach ($productos as $p): ?>
                                <option value="<?php echo $p['id_producto']; ?>" data-stock="<?php echo $p['stock_actual']; ?>">
                                    <?php echo htmlspecialchars('[' . $p['codigo'] . '] ' . $p['nombre'] . ' (' . $p['stock_actual'] . ' disp.)'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-secondary">Tipo de movimiento *</label>
                        <select id="tipo" name="tipo" class="form-select" required>
                            <option value="entrada">Entrada (Recepción / Compra)</option>
                            <option value="salida">Salida (Despacho / Venta)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-secondary">Cantidad *</label>
                        <input type="number" id="cantidad" name="cantidad" min="1" class="form-control num-tabular" required>
                        <small class="text-danger d-none mt-1 d-block" id="alertaStockJS"></small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-medium text-secondary">Referencia / Observación</label>
                        <input type="text" name="observacion" class="form-control" placeholder="Ej: Guía despacho #204">
                    </div>
                    <button type="submit" class="btn btn-slate w-100 py-2">Confirmar registro</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabla Historial -->
    <div class="col-lg-8">
        <div class="card-clean overflow-hidden">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Bitácora de Movimientos</h6>
                <span class="text-secondary small"><?php echo count($historial); ?> registros</span>
            </div>
            <div class="table-responsive">
                <table class="table table-clean table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Fecha / Hora</th>
                            <th>Producto</th>
                            <th class="text-center">Operación</th>
                            <th class="text-center">Cant.</th>
                            <th>Responsable</th>
                            <th class="pe-3">Referencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $m): ?>
                            <tr>
                                <td class="ps-3 small text-secondary text-nowrap num-tabular">
                                    <?php echo date('d/m/Y H:i', strtotime($m['fecha_hora'])); ?>
                                </td>
                                <td>
                                    <span class="sku-code me-1"><?php echo htmlspecialchars($m['codigo']); ?></span>
                                    <span class="fw-medium"><?php echo htmlspecialchars($m['producto_nombre']); ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($m['tipo'] == 'entrada'): ?>
                                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle">
                                            <i class="bi bi-arrow-down-left me-1"></i>Entrada
                                        </span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                            <i class="bi bi-arrow-up-right me-1"></i>Salida
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-bold num-tabular"><?php echo $m['cantidad']; ?></td>
                                <td class="small text-secondary"><?php echo htmlspecialchars($m['usuario_nombre']); ?></td>
                                <td class="pe-3 small text-secondary"><?php echo htmlspecialchars($m['observacion'] != '' ? $m['observacion'] : '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>