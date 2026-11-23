# TecnoStock — Sistema de Inventario (MVP)

## 1. Problema, Alcance y Actor
TecnoStock llevaba su inventario de accesorios tecnológicos en planillas manuales, provocando códigos duplicados y errores en el stock disponible. Esta aplicación web local (PHP 8 + PDO, MySQL, Bootstrap 5 y JavaScript) permite al **Encargado de inventario** gestionar productos y movimientos con integridad referencial.

## 2. Requerimientos y Reglas de Negocio
- **RF1:** Inicio y cierre de sesión (`login.php`, `logout.php`).
- **RF2–RF6:** Crear, listar, buscar, editar y desactivar productos sin eliminación física (`index.php`, `producto_form.php`).
- **RF7:** Registro de entradas y salidas con actualización automática de stock (`movimientos.php`).
- **RF8:** Alerta visual cuando `stock_actual < stock_minimo` (`index.php`).
- **Reglas:** Código único (`UNIQUE`), valores no negativos (`CHECK >= 0`), salidas no mayores al stock disponible y conservación de historial mediante desactivación.

## 3. Casos de Prueba Verificados
| ID | Prueba | Entrada | Resultado Esperado | Estado |
| :--- | :--- | :--- | :--- | :--- |
| CP-01 | Login válido | `admin@tecnostock.cl` / `password` | Ingreso al catálogo | OK |
| CP-02 | Código duplicado | Código `TEC-001` en producto nuevo | Mensaje de error, no guarda | OK |
| CP-03 | Valor negativo | Precio `-100` | Bloqueado por JS y PHP | OK |
| CP-04 | Salida excesiva | Salida `99` en producto con stock `9` | Bloqueado por JS y PHP | OK |
| CP-05 | Entrada válida | Entrada `10` en `TEC-003` (stock 3) | Stock sube a `13`, quita alerta | OK |
| CP-06 | Desactivar producto | Clic en Desactivar | Pasa a `inactivo` sin borrarse | OK |