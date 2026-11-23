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
        $error = 'Completa los campos obligatorios.';
    } elseif ($precio < 0 || $stock_actual < 0 || $stock_minimo < 0) {
        $error = 'El precio y los stocks no pueden ser negativos.';
    } elseif ($stmtDup->fetch()) {
        $error = 'Ese código de producto ya existe.';
    } else {
        if ($id > 0) {
            $sql = "UPDATE productos SET codigo=?, nombre=?, descripcion=?, precio=?, stock_actual=?, stock_minimo=?, id_categoria=? WHERE id_producto=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$codigo, $nombre, $descripcion, $precio, $stock_actual, $stock_minimo, $id_categoria, $id]);
            $_SESSION['mensaje'] = 'Producto actualizado.';
        } else {
            $sql = "INSERT INTO productos (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$codigo, $nombre, $descripcion, $precio, $stock_actual, $stock_minimo, $id_categoria]);
            $_SESSION['mensaje'] = 'Producto registrado.';
        }

        header('Location: index.php');
        exit;
    }
}

$categorias = $pdo->query("SELECT * FROM categorias WHERE estado = 'activo'")->fetchAll(PDO::FETCH_ASSOC);
require_once 'includes/header.php';
?>

<div class="card mx-auto" style="max-width: 650px;">
    <div class="card-header bg-white">
        <h5 class="mb-0"><?php echo ($id > 0) ? 'Editar Producto' : 'Nuevo Producto'; ?></h5>
    </div>
    <div class="card-body">
        <?php if ($error != ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" id="formProducto">
            <div class="mb-3">
                <label class="form-label">Código *</label>
                <input type="text" id="codigo" name="codigo" class="form-control" value="<?php echo htmlspecialchars($codigo); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Nombre *</label>
                <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($nombre); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Categoría *</label>
                <select id="id_categoria" name="id_categoria" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id_categoria']; ?>" <?php if ($id_categoria == $cat['id_categoria']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cat['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Precio *</label>
                    <input type="number" id="precio" name="precio" min="0" class="form-control" value="<?php echo htmlspecialchars($precio); ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Stock Actual *</label>
                    <input type="number" id="stock_actual" name="stock_actual" min="0" class="form-control" value="<?php echo htmlspecialchars($stock_actual); ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Stock Mínimo *</label>
                    <input type="number" id="stock_minimo" name="stock_minimo" min="0" class="form-control" value="<?php echo htmlspecialchars($stock_minimo); ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2"><?php echo htmlspecialchars($descripcion); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>