CREATE DATABASE IF NOT EXISTS tecnostock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tecnostock;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(120) NOT NULL UNIQUE,
    clave VARCHAR(255) NOT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB;

CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB;

CREATE TABLE productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    nombre VARCHAR(120) NOT NULL,
    descripcion TEXT NULL,
    precio DECIMAL(10,2) NOT NULL CHECK (precio >= 0),
    stock_actual INT NOT NULL DEFAULT 0 CHECK (stock_actual >= 0),
    stock_minimo INT NOT NULL DEFAULT 0 CHECK (stock_minimo >= 0),
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    id_categoria INT NOT NULL,
    CONSTRAINT fk_producto_categoria
        FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE movimientos (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo ENUM('entrada', 'salida') NOT NULL,
    cantidad INT NOT NULL CHECK (cantidad > 0),
    observacion VARCHAR(255) NULL,
    id_producto INT NOT NULL,
    id_usuario INT NOT NULL,
    CONSTRAINT fk_movimiento_producto
        FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_movimiento_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Usuario inicial: admin@tecnostock.cl | Contraseña: password
INSERT INTO usuarios (nombre, correo, clave, estado) VALUES
('Encargado TecnoStock', 'admin@tecnostock.cl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo');

INSERT INTO categorias (nombre, descripcion) VALUES
('Audio', 'Audífonos inalámbricos, parlantes y micrófonos'),
('Carga y Energía', 'Cargadores rápidos, cables USB-C y power banks'),
('Periféricos', 'Mouse, teclados, webcams y soportes'),
('Protección', 'Carcasas, láminas de vidrio templado y fundas');

INSERT INTO productos (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, estado, id_categoria) VALUES
('TEC-001', 'Audífonos Bluetooth TWS Pro', 'Cancelación de ruido y estuche USB-C', 24990.00, 18, 5, 'activo', 1),
('TEC-002', 'Cargador Rápido GaN 35W', 'Doble puerto USB-C carga rápida', 16990.00, 12, 4, 'activo', 2),
('TEC-003', 'Cable USB-C a Lightning 2m', 'Cable trenzado de alta resistencia', 8990.00, 3, 8, 'activo', 2),
('TEC-004', 'Mouse Inalámbrico Ergonómico', 'Sensor óptico silencioso 1600 DPI', 14990.00, 9, 5, 'activo', 3);

INSERT INTO movimientos (tipo, cantidad, observacion, id_producto, id_usuario) VALUES
('entrada', 20, 'Compra inicial a proveedor', 1, 1),
('salida', 2, 'Venta en mostrador #101', 1, 1);