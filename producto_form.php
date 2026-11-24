<?php
require_once 'includes/auth.php';
require_once 'config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

$codigo       = '';
$nombre       = '';
$descripcion  = '';
$precio       = '';
$stock_actual = 0;
$stock_minimo = 1;
$id_categoria = 0;

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id_producto = ?");
    $stmt->execute([$id]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prod) {
        $codigo       = $prod['codigo'];
        $nombre       = $prod['nombre'];
        $descripcion  = $prod['descripcion'];
        $precio       = $prod['precio'];
        $stock_actual = $prod['stock_actual'];
        $stock_minimo = $prod['stock_minimo'];
        $id_categoria = $prod['id_categoria'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo       = trim($_POST['codigo']);
    $nombre       = trim($_POST['nombre']);
    $descripcion  = trim($_POST['descripcion']);
    $precio       = $_POST['precio'];
    $stock_actual = $_POST['stock_actual'];
    $stock_minimo = $_POST['stock_minimo'];
    $id_categoria = (int)$_POST['id_categoria'];

    $stmtDup = $pdo->prepare("SELECT id_producto FROM productos WHERE codigo = ? AND id_producto != ?");
    $stmtDup->execute([$codigo, $id]);

    if ($codigo == '' || $nombre == '' || $id_categoria <= 0) {
        $error = 'Completa todos los campos obligatorios marcados con *.';
    } elseif (!is_numeric($precio) || !is_numeric($stock_actual) || !is_numeric($stock_minimo)) { // <-- AQUÍ VA is_numeric
        $error = 'El precio y las cantidades de stock deben ser valores numéricos.';
    } elseif ($precio < 0 || $stock_actual < 0 || $stock_minimo < 0) {
        $error = 'El precio y las cantidades de stock no pueden ser negativos.';
    } elseif ($stmtDup->fetch()) {
        $error = 'El código SKU ingresado ya pertenece a otro producto.';
    } else {
        if ($id > 0) {
            $sql = "UPDATE productos SET codigo=?, nombre=?, descripcion=?, precio=?, stock_actual=?, stock_minimo=?, id_categoria=? WHERE id_producto=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$codigo, $nombre, $descripcion, $precio, $stock_actual, $stock_minimo, $id_categoria, $id]);
            $_SESSION['mensaje'] = 'Ficha de producto actualizada.';
        } else {
            $sql = "INSERT INTO productos (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$codigo, $nombre, $descripcion, $precio, $stock_actual, $stock_minimo, $id_categoria]);
            $_SESSION['mensaje'] = 'Producto incorporado al catálogo.';
        }

        header('Location: index.php');
        exit;
    }
}

$categorias = $pdo->query("SELECT * FROM categorias WHERE estado = 'activo'")->fetchAll(PDO::FETCH_ASSOC);
require_once 'includes/header.php';
?>

<div class="mx-auto" style="max-width: 680px;">
    <div class="mb-3">
        <a href="index.php" class="text-decoration-none text-secondary small">
            <i class="bi bi-arrow-left me-1"></i>Volver al catálogo
        </a>
    </div>

    <div class="card-clean">
        <div class="card-header">
            <h5 class="fw-bold mb-0"><?php echo ($id > 0) ? 'Editar Ficha de Producto' : 'Registrar Nuevo Producto'; ?></h5>
            <span class="text-secondary small">Los campos marcados con * son obligatorios.</span>
        </div>

        <div class="card-body p-4">
            <?php if ($error != ''): ?>
                <div class="alert alert-danger py-2 small">
                    <i class="bi bi-exclamation-circle me-1"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="formProducto">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-medium text-secondary">Código SKU *</label>
                        <input type="text" id="codigo" name="codigo" class="form-control font-monospace"
                               placeholder="TEC-005" value="<?php echo htmlspecialchars($codigo); ?>" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-medium text-secondary">Nombre del producto *</label>
                        <input type="text" id="nombre" name="nombre" class="form-control"
                               placeholder="Ej: Teclado Mecánico Inalámbrico" value="<?php echo htmlspecialchars($nombre); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium text-secondary">Categoría *</label>
                        <select id="id_categoria" name="id_categoria" class="form-select" required>
                            <option value="">Seleccionar categoría...</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id_categoria']; ?>" <?php if ($id_categoria == $cat['id_categoria']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium text-secondary">Precio unitario *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary">$</span>
                            <input type="number" id="precio" name="precio" min="0" class="form-control num-tabular"
                                   value="<?php echo htmlspecialchars($precio); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium text-secondary">Stock actual *</label>
                        <input type="number" id="stock_actual" name="stock_actual" min="0" class="form-control num-tabular"
                               value="<?php echo htmlspecialchars($stock_actual); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium text-secondary">Stock mínimo (Alerta) *</label>
                        <input type="number" id="stock_minimo" name="stock_minimo" min="0" class="form-control num-tabular"
                               value="<?php echo htmlspecialchars($stock_minimo); ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-medium text-secondary">Especificaciones / Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"
                                  placeholder="Detalles técnicos, compatibilidad o presentación..."><?php echo htmlspecialchars($descripcion); ?></textarea>
                    </div>
                </div>

                <div class="border-top mt-4 pt-3 d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-slate px-4">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>