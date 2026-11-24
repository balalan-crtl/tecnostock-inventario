<?php
$pagina = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock · Gestión de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-tecnostock sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-dark" href="index.php">
            <span class="d-inline-flex align-items-center justify-content-center rounded-2 bg-dark text-white" style="width: 32px; height: 32px;">
                <i class="bi bi-boxes"></i>
            </span>
            <span>TecnoStock</span>
            <span class="badge bg-light text-secondary border fw-normal" style="font-size: 0.7rem;">Bodega</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <div class="navbar-nav ms-lg-4 me-auto gap-1">
                <a class="nav-link <?php if ($pagina == 'index.php') echo 'active'; ?>" href="index.php">
                    <i class="bi bi-grid me-1"></i>Catálogo
                </a>
                <a class="nav-link <?php if ($pagina == 'producto_form.php') echo 'active'; ?>" href="producto_form.php">
                    <i class="bi bi-plus-square me-1"></i>Nuevo Producto
                </a>
                <a class="nav-link <?php if ($pagina == 'movimientos.php') echo 'active'; ?>" href="movimientos.php">
                    <i class="bi bi-arrow-left-right me-1"></i>Movimientos
                </a>
            </div>

            <div class="d-flex align-items-center gap-3 mt-2 mt-lg-0">
                <span class="text-secondary small">
                    <i class="bi bi-person-check me-1"></i><?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2"></i>
            <div><?php echo htmlspecialchars($_SESSION['mensaje']); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>