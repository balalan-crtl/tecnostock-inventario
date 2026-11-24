document.addEventListener('DOMContentLoaded', () => {
    // 0. Validar formulario de login
    const formLogin = document.getElementById('formLogin');
    if (formLogin) {
        formLogin.addEventListener('submit', (e) => {
            const correo = document.getElementById('correo').value.trim();
            const clave = document.getElementById('clave').value.trim();

            if (!correo || !clave) {
                e.preventDefault();
                alert('Debes ingresar tu correo y contraseña.');
                return;
            }
            if (!correo.includes('@') || !correo.includes('.')) {
                e.preventDefault();
                alert('Ingresa un formato de correo electrónico válido.');
            }
        });
    }

    // 1. Validar formulario de producto
    const formProducto = document.getElementById('formProducto');
    if (formProducto) {
        formProducto.addEventListener('submit', (e) => {
            const codigo = document.getElementById('codigo').value.trim();
            const nombre = document.getElementById('nombre').value.trim();
            const categoria = document.getElementById('id_categoria').value;
            const precio = parseFloat(document.getElementById('precio').value);
            const stockActual = parseInt(document.getElementById('stock_actual').value, 10);
            const stockMinimo = parseInt(document.getElementById('stock_minimo').value, 10);

            if (!codigo || !nombre || !categoria) {
                e.preventDefault();
                alert('Completa código, nombre y categoría.');
                return;
            }
            if (isNaN(precio) || precio < 0 || isNaN(stockActual) || stockActual < 0 || isNaN(stockMinimo) || stockMinimo < 0) {
                e.preventDefault();
                alert('El precio, stock actual y stock mínimo no pueden ser negativos.');
            }
        });
    }

    // 2. Validar stock disponible en salidas
    const formMovimiento = document.getElementById('formMovimiento');
    if (formMovimiento) {
        const selectProd = document.getElementById('id_producto');
        const selectTipo = document.getElementById('tipo');
        const inputCant = document.getElementById('cantidad');
        const alertaJS = document.getElementById('alertaStockJS');

        const verificarStock = () => {
            const opcion = selectProd.options[selectProd.selectedIndex];
            const stockDisponible = parseInt(opcion?.dataset.stock || '0', 10);
            const cantidad = parseInt(inputCant.value || '0', 10);

            if (selectTipo.value === 'salida' && cantidad > stockDisponible) {
                alertaJS.textContent = 'Stock insuficiente: solo hay ' + stockDisponible + ' unidades disponibles.';
                alertaJS.classList.remove('d-none');
                return false;
            } else {
                alertaJS.classList.add('d-none');
                return true;
            }
        };

        selectProd.addEventListener('change', verificarStock);
        selectTipo.addEventListener('change', verificarStock);
        inputCant.addEventListener('input', verificarStock);

        formMovimiento.addEventListener('submit', (e) => {
            const cantidad = parseInt(inputCant.value || '0', 10);
            if (!selectProd.value || isNaN(cantidad) || cantidad <= 0) {
                e.preventDefault();
                alert('Selecciona un producto e ingresa una cantidad mayor a 0.');
                return;
            }
            if (!verificarStock()) {
                e.preventDefault();
                alert('No puedes registrar una salida mayor al stock disponible.');
            }
        });
    }

    // 3. Confirmación al desactivar/activar producto
    document.querySelectorAll('.form-toggle-estado').forEach((form) => {
        form.addEventListener('submit', (e) => {
            const accion = form.querySelector('input[name="nuevo_estado"]').value;
            const texto = accion === 'inactivo'
                ? '¿Seguro que deseas desactivar este producto? No se eliminará del historial.'
                : '¿Deseas activar nuevamente este producto?';
            if (!confirm(texto)) {
                e.preventDefault();
            }
        });
    });
});