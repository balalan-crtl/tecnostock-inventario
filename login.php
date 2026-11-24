<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = trim($_POST['correo']);
    $clave  = $_POST['clave'];

    if ($correo == '' || $clave == '') {
        $error = 'Ingresa correo y contraseña.';
    } else {
        $sql = "SELECT * FROM usuarios WHERE correo = ? AND estado = 'activo'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($clave, $usuario['clave'])) {
            $_SESSION['id_usuario']     = $usuario['id_usuario'];
            $_SESSION['nombre_usuario'] = $usuario['nombre'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Credenciales incorrectas o cuenta inactiva.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso · TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center min-vh-100">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-sm-9 col-md-6 col-lg-4">
            <div class="card-clean p-4 p-md-5">
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-2 bg-dark text-white mb-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-boxes fs-5"></i>
                    </div>
                    <h4 class="fw-bold mb-1">TecnoStock</h4>
                    <p class="text-secondary small mb-0">Sistema de Control de Inventario</p>
                </div>

                <?php if ($error != ''): ?>
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-circle me-1"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="formLogin" novalidate>
                    <div class="mb-3">
                        <label for="correo" class="form-label small fw-medium text-secondary">Correo corporativo</label>
                        <input type="email" id="correo" name="correo" class="form-control" value="admin@tecnostock.cl" required>
                    </div>
                    <div class="mb-4">
                        <label for="clave" class="form-label small fw-medium text-secondary">Contraseña</label>
                        <input type="password" id="clave" name="clave" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-slate w-100 py-2">
                        Iniciar sesión <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </form>

                <div class="border-top mt-4 pt-3 text-center">
                    <span class="text-muted" style="font-size: 0.78rem;">Acceso autorizado · Encargado de Bodega</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/validaciones.js"></script>
</body>
</html>