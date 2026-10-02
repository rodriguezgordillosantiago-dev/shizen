CREATE DATABASE IF NOT EXISTS shizen
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_spanish2_ci;

USE shizen;

CREATE TABLE IF NOT EXISTS usuario (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  direccion VARCHAR(255) NULL,
  email VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  rol VARCHAR(20) NOT NULL DEFAULT 'usuario',
  localidad VARCHAR(100) NULL DEFAULT 'Chapinero',
  telefono VARCHAR(50) NULL DEFAULT '+57 300 123 4567',
  CONSTRAINT uq_usuario_email UNIQUE (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS negocios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  gmail_negocio TEXT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  direccion VARCHAR(255) NOT NULL,
  cedula VARCHAR(20) NOT NULL,
  hora_apertura TIME NOT NULL,
  hora_cierre TIME NOT NULL,
  logo_url VARCHAR(500) NULL,
  rut_url VARCHAR(500) NOT NULL,
  documento_identidad_representante_url VARCHAR(500) NOT NULL,
  certificado_bancario_url VARCHAR(500) NOT NULL,
  certificado_camara_comercio_url VARCHAR(500) NULL,
  CONSTRAINT uq_negocios_usuario UNIQUE (id_usuario),
  CONSTRAINT fk_negocios_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS repartidor (
  id_repartidor INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  email_repartidor VARCHAR(150) NULL,
  num_telefono VARCHAR(20) NULL,
  direccion VARCHAR(255) NULL,
  cedula VARCHAR(20) NOT NULL,
  vehiculo VARCHAR(50) NOT NULL,
  foto_url VARCHAR(500) NOT NULL,
  cedula_documento_url VARCHAR(500) NOT NULL,
  licencia_conduccion_url VARCHAR(500) NULL,
  soat_url VARCHAR(500) NULL,
  tarjeta_propiedad_url VARCHAR(500) NULL,
  estado VARCHAR(15) NOT NULL DEFAULT 'Activo',
  CONSTRAINT uq_repartidor_usuario UNIQUE (id_usuario),
  CONSTRAINT uq_repartidor_telefono UNIQUE (num_telefono),
  CONSTRAINT fk_repartidor_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NULL,
  nombre VARCHAR(60) NOT NULL,
  apellido VARCHAR(60) NOT NULL,
  tipo_documento VARCHAR(5) NOT NULL,
  numero_documento VARCHAR(20) NOT NULL,
  correo VARCHAR(120) NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  localidad VARCHAR(60) NULL,
  direccion TEXT NOT NULL,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  estado VARCHAR(20) NOT NULL DEFAULT 'recibido',
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE pedidos
  ADD COLUMN IF NOT EXISTS id_usuario INT NULL,
  ADD COLUMN IF NOT EXISTS total DECIMAL(12,2) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS estado VARCHAR(20) NOT NULL DEFAULT 'recibido';

CREATE TABLE IF NOT EXISTS pedido_detalles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT NOT NULL,
  menu_item_id INT NOT NULL,
  nombre_producto VARCHAR(150) NOT NULL,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_detalle_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contactos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  correo VARCHAR(120) NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  mensaje TEXT NOT NULL,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorias (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(100) NOT NULL,
  icon         VARCHAR(10)  NOT NULL,
  descripcion  TEXT         NULL,
  cover_img    VARCHAR(255) NULL,
  bg_color     VARCHAR(20)  NULL,
  accent_color VARCHAR(20)  NULL
) ENGINE=InnoDB;

ALTER TABLE menu_items
  ADD COLUMN IF NOT EXISTS categoria_id INT NULL,
  ADD CONSTRAINT fk_menu_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS favorito (
  id_favorito INT AUTO_INCREMENT PRIMARY KEY,
  id_negocio INT NULL,
  id_menu_item INT NULL,
  id_usuario INT NULL,
  fecha_reg DATE NOT NULL DEFAULT (CURRENT_DATE),
  CONSTRAINT uq_favorito_usuario_negocio UNIQUE (id_usuario, id_negocio),
  CONSTRAINT uq_favorito_usuario_menu_item UNIQUE (id_usuario, id_menu_item),
  CONSTRAINT fk_favorito_negocio FOREIGN KEY (id_negocio) REFERENCES negocios(id_negocio),
  CONSTRAINT fk_favorito_menu_item FOREIGN KEY (id_menu_item) REFERENCES menu_items(id_menu_item) ON DELETE SET NULL,
  CONSTRAINT fk_favorito_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;

ALTER TABLE entrega
  ADD COLUMN IF NOT EXISTS codigo_entrega VARCHAR(6) NULL AFTER estado,
  ADD COLUMN IF NOT EXISTS fecha_confirmacion DATETIME NULL AFTER codigo_entrega;

CREATE TABLE IF NOT EXISTS notificacion (
  id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  audiencia VARCHAR(20) NOT NULL,
  id_pedido INT NULL,
  tipo VARCHAR(60) NOT NULL,
  titulo VARCHAR(180) NOT NULL,
  mensaje TEXT NOT NULL,
  leida TINYINT(1) NOT NULL DEFAULT 0,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notificacion_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
  CONSTRAINT fk_notificacion_pedido FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido) ON DELETE CASCADE,
  INDEX idx_notificacion_usuario_fecha (id_usuario, fecha_creacion),
  INDEX idx_notificacion_audiencia_fecha (audiencia, fecha_creacion)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS carrito (
  id_carrito INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  id_negocio INT NOT NULL,
  id_menu_item INT NOT NULL,
  cantidad INT NOT NULL DEFAULT 1,
  precio_unitario DECIMAL(12,2) NOT NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_carrito_usuario_item UNIQUE (id_usuario, id_menu_item),
  CONSTRAINT fk_carrito_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
  CONSTRAINT fk_carrito_negocio FOREIGN KEY (id_negocio) REFERENCES negocios(id_negocio) ON DELETE CASCADE,
  CONSTRAINT fk_carrito_menu_item FOREIGN KEY (id_menu_item) REFERENCES menu_items(id_menu_item) ON DELETE CASCADE
) ENGINE=InnoDB;

-- === SEED DATA FOR 2 BUSINESSES AND 46 DISHES ===
INSERT INTO usuario (id_usuario, nombre, apellido, direccion, email, password_hash, rol, localidad) VALUES
(1, 'Carlos', 'Mendoza', 'Calle 45 #7-12, Bogotá', 'contacto@veganocentral.com', '$2y$10$h7V/7V0m764hR1aHkS4MGeB17Jd5o1A1l/06.f4e4W/m.g4s.m.l.', 'negocio', 'Chapinero'),
(2, 'Laura', 'Gómez', 'Carrera 13 #58-30, Bogotá', 'contacto@ecomarket.com', '$2y$10$h7V/7V0m764hR1aHkS4MGeB17Jd5o1A1l/06.f4e4W/m.g4s.m.l.', 'negocio', 'Chapinero')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), email=VALUES(email);

INSERT INTO negocios (id_negocio, id_usuario, gmail_negocio, nombre, direccion, cedula, hora_apertura, hora_cierre, logo_url, rut_url, documento_identidad_representante_url, certificado_bancario_url, certificado_camara_comercio_url) VALUES
(1, 1, 'contacto@veganocentral.com', 'Restaurante Vegano Central', 'Calle 45 #7-12, Bogotá', '1010203040', '08:00:00', '22:00:00', '../Imagenes_prueba/logo.jpg', 'legales/negocio/negocio_1010203040/rut_1010203040.pdf', 'legales/negocio/negocio_1010203040/doc_representante_1010203040.pdf', 'legales/negocio/negocio_1010203040/cert_bancario_1010203040.pdf', 'legales/negocio/negocio_1010203040/camara_comercio_1010203040.pdf'),
(2, 2, 'contacto@ecomarket.com', 'Eco Market Chapinero', 'Carrera 13 #58-30, Bogotá', '1020304050', '08:00:00', '22:00:00', '../Imagenes_prueba/logo2.jpg', 'legales/negocio/negocio_1020304050/rut_1020304050.pdf', 'legales/negocio/negocio_1020304050/doc_representante_1020304050.pdf', 'legales/negocio/negocio_1020304050/cert_bancario_1020304050.pdf', 'legales/negocio/negocio_1020304050/camara_comercio_1020304050.pdf')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), id_usuario=VALUES(id_usuario);

INSERT INTO menu_items (id_menu_item, id_negocio, id_categoria, nombre, descripcion, precio, imagen_url, on_promo, precio_promocion) VALUES
(1, 1, 3, 'Bowl Saludable', 'Bowl saludable con quinoa, aguacate, vegetales frescos, semillas, jugo de lim¾n y aderezo de hierbas. Una opci¾n equilibrada, colorida y llena de sabor para quienes buscan una comida nutritiva, ligera y satisfactoria a cualquier hora del dÝa.', 10000, '../assets/Imagenes_prueba/plato1.jpg', 0, NULL),
(2, 1, 3, 'Ensalada César', 'Lechuga, pollo, crutones y aderezo césar', 15000, '../assets/Imagenes_prueba/plato1.jpg', 0, NULL),
(3, 1, 1, 'Hamburguesa Vegana', 'Deliciosa Hamburguesa (Plant-Bassed)', 25000, '../assets/Imagenes_prueba/plato1.jpg', 0, NULL),
(4, 1, 3, 'Bowl de Quinoa', 'Descripción pendiente por definir', 18000, '../assets/Imagenes_prueba/plato1.jpg', 0, NULL),
(5, 1, 1, 'Tacos de Jackfruit', 'Deliciosos Tacos a base de frutas', 22500, '../assets/Imagenes_prueba/plato1.jpg', 0, NULL),
(6, 1, 1, '2x1 en pizza mediana', 'Válido solo los martes, no acumulable con otras promociones', 15000, '../Imagenes_prueba/pizzavegana.jpg', 0, NULL),
(7, 1, 1, 'Wrap de Falafel', 'Falafel casero con vegetales y salsa tahini', 18000, '../Imagenes_prueba/plato1.jpg', 0, NULL),
(8, 1, 2, 'Lasaña de Berenjena', 'Capas de berenjena, salsa napolitana y queso vegano', 28000, '../Imagenes_prueba/plato6.jpg', 0, NULL),
(9, 1, 4, 'Brownie Vegano', 'Brownie de chocolate 100% sin lácteos ni huevo', 9000, '../Imagenes_prueba/postre1.jpg', 0, NULL),
(10, 1, 5, 'Limonada de Coco', 'Limonada natural con leche de coco', 8000, '../Imagenes_prueba/bebida1.jpg', 0, NULL),
(11, 1, 6, 'Granola Bowl', 'Granola casera, frutas frescas y yogurt vegetal', 12000, '../Imagenes_prueba/desayuno6.jpg', 0, NULL),
(12, 1, 5, 'Jugo Verde Detox', 'Espinaca, apio, manzana verde y limón', 9000, '../Imagenes_prueba/bebida2.jpg', 0, NULL),
(13, 1, 7, 'Mix de Frutos Secos', 'Almendras, nueces, pasas y semillas', 7000, '../Imagenes_prueba/snack1.jpg', 0, NULL),
(14, 1, 3, 'Ensalada Mediterránea', 'Hummus, garbanzos, tomate y aceitunas', 16000, '../Imagenes_prueba/desayuno1.jpg', 0, NULL),
(15, 1, 3, 'Ensalada Kale & Aguacate', 'Kale, aguacate, quinoa y vinagreta de limón', 17000, '../Imagenes_prueba/desayuno2.jpg', 0, NULL),
(16, 1, 3, 'Bowl Buddha', 'Vegetales asados, garbanzos y tahini', 19000, '../Imagenes_prueba/desayuno3.jpg', 0, NULL),
(17, 1, 5, 'Smoothie Verde', 'Espinaca, piña y jengibre', 10000, '../Imagenes_prueba/bebida3.jpg', 0, NULL),
(18, 1, 6, 'Tostada de Aguacate', 'Pan integral, aguacate y semillas', 11000, '../Imagenes_prueba/desayuno7.jpg', 0, NULL),
(19, 1, 2, 'Pasta Vegana Alfredo', 'Pasta con salsa cremosa a base de anacardos', 24000, '../Imagenes_prueba/plato7.jpg', 0, NULL),
(20, 1, 4, 'Cheesecake Vegano', 'Cheesecake sin lácteos con frutos rojos', 10000, '../Imagenes_prueba/postre2.jpg', 0, NULL),
(21, 1, 5, 'Té Helado de Frutos Rojos', 'Té frío natural con frutos rojos', 7000, '../Imagenes_prueba/bebida4.jpg', 0, NULL),
(22, 1, 2, 'Cazuela de Mariscos', 'Cazuela criolla con camarón, mejillón y pescado', 32000, '../Imagenes_prueba/plato8.jpg', 0, NULL),
(23, 1, 1, 'Ceviche de Camarón', 'Ceviche fresco estilo costa pacífica', 26000, '../Imagenes_prueba/plato2.jpg', 0, NULL),
(24, 2, 2, 'Arroz con Coco y Camarón', 'Arroz de coco tradicional con camarón salteado', 28000, '../Imagenes_prueba/plato9.jpg', 0, NULL),
(25, 2, 7, 'Patacones con Camarón', 'Patacones crocantes con camarón al ajillo', 18000, '../Imagenes_prueba/snack2.jpg', 0, NULL),
(26, 2, 5, 'Jugo de Maracuyá', 'Jugo natural de maracuyá', 8000, '../Imagenes_prueba/bebida5.jpg', 0, NULL),
(27, 2, 1, 'Rollo California', 'Cangrejo, aguacate y pepino', 22000, '../Imagenes_prueba/plato3.jpg', 0, NULL),
(28, 2, 2, 'Rollo Sakura Especial', 'Salmón, queso crema y cebollín crocante', 27000, '../Imagenes_prueba/DonCamaronplato.jpg', 0, NULL),
(29, 2, 2, 'Sashimi Mixto', 'Selección de pescados frescos del día', 30000, '../Imagenes_prueba/veganrestaurantplato.jpg', 0, NULL),
(30, 2, 7, 'Gyozas', 'Empanadillas japonesas al vapor', 14000, '../Imagenes_prueba/snack3.jpg', 0, NULL),
(31, 2, 5, 'Té Verde Frío', 'Té verde japonés servido frío', 6000, '../Imagenes_prueba/bebida6.jpg', 0, NULL),
(32, 2, 1, 'Pizza Margarita Vegana', 'Salsa napolitana, mozzarella vegana y albahaca', 24000, '../Imagenes_prueba/plato4.jpg', 0, NULL),
(33, 2, 1, 'Pizza Hawaiana Vegana', 'Piña, jamón vegetal y mozzarella vegana', 26000, '../Imagenes_prueba/plato5.jpg', 1, NULL),
(34, 2, 2, 'Calzone Vegetariano', 'Calzone relleno de vegetales y queso', 23000, '../Imagenes_prueba/sushisakura.jpg', 0, NULL),
(35, 2, 4, 'Tiramisú Vegano', 'Tiramisú clásico sin lácteos ni huevo', 11000, '../Imagenes_prueba/postre3.jpg', 0, NULL),
(36, 2, 3, 'Bowl Energético', 'Quinoa, garbanzo, aguacate y semillas', 17000, '../Imagenes_prueba/desayuno4.jpg', 0, NULL),
(37, 2, 3, 'Bowl Tropical', 'Mango, piña, coco y granola', 18000, '../Imagenes_prueba/desayuno5.jpg', 0, NULL),
(38, 2, 3, 'Bowl Proteico', 'Tofu marinado, arroz integral y vegetales', 19000, '../Imagenes_prueba/snack6.jpg', 0, NULL),
(39, 2, 5, 'Smoothie de Mango', 'Mango, banano y leche de almendras', 9000, '../Imagenes_prueba/bebida7.jpg', 0, NULL),
(40, 2, 7, 'Chips de Plátano', 'Chips horneados de plátano verde', 6000, '../Imagenes_prueba/snack4.png', 0, NULL),
(41, 2, 4, 'Brownie sin Gluten', 'Brownie de chocolate apto para celíacos', 9000, '../Imagenes_prueba/postre4.jpg', 0, NULL),
(42, 2, 4, 'Cheesecake de Maracuyá', 'Cheesecake vegano con coulis de maracuyá', 11000, '../Imagenes_prueba/postre5.jpg', 0, NULL),
(43, 2, 7, 'Galletas de Avena', 'Galletas de avena y pasas sin lácteos', 6000, '../Imagenes_prueba/snack5.png', 0, NULL),
(44, 2, 4, 'Torta de Zanahoria Vegana', 'Torta húmeda de zanahoria con frosting vegano', 12000, '../Imagenes_prueba/postre6.jpg', 0, NULL),
(45, 2, 5, 'Chocolate Caliente Vegano', 'Chocolate caliente con leche de avena', 7000, '../Imagenes_prueba/bebida8.jpg', 1, NULL),
(48, 2, 1, 'hamburguesa', 'rica hamburguesa', 13000, '/shizen-home/public/images/catalogo/Imagenes_prueba/producto_1789620142_f18bdb96.jpg', 0, NULL)
ON DUPLICATE KEY UPDATE id_negocio=VALUES(id_negocio), nombre=VALUES(nombre), precio=VALUES(precio);

-- === SEED DATA FOR 2 BUSINESSES AND 46 DISHES ===
INSERT INTO usuario (id_usuario, nombre, apellido, direccion, email, password_hash, rol, localidad) VALUES
(4, 'Carlos', 'Mendoza', 'Calle 45 #7-12, Bogotá', 'contacto@veganocentral.com', '$2y$10$h7V/7V0m764hR1aHkS4MGeB17Jd5o1A1l/06.f4e4W/m.g4s.m.l.', 'Negocio', 'Chapinero'),
(5, 'Laura', 'Gómez', 'Carrera 13 #58-30, Bogotá', 'contacto@ecomarketchapinero.com', '$2y$10$h7V/7V0m764hR1aHkS4MGeB17Jd5o1A1l/06.f4e4W/m.g4s.m.l.', 'Negocio', 'Chapinero')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), email=VALUES(email);

INSERT INTO negocios (id_negocio, id_usuario, gmail_negocio, nombre, direccion, cedula, hora_apertura, hora_cierre, logo_url, rut_url, documento_identidad_representante_url, certificado_bancario_url, certificado_camara_comercio_url) VALUES
(1, 4, 'contacto@veganocentral.com', 'Restaurante Vegano Central', 'Calle 45 #7-12, Bogotá', '1010203040', '08:00:00', '22:00:00', '../Imagenes_prueba/logo.jpg', 'legales/negocio/negocio_1010203040/rut_1010203040.pdf', 'legales/negocio/negocio_1010203040/doc_representante_1010203040.pdf', 'legales/negocio/negocio_1010203040/cert_bancario_1010203040.pdf', 'legales/negocio/negocio_1010203040/camara_comercio_1010203040.pdf'),
(2, 5, 'contacto@ecomarketchapinero.com', 'Eco Market Chapinero', 'Carrera 13 #58-30, Bogotá', '1020304050', '08:00:00', '22:00:00', '../Imagenes_prueba/logo2.jpg', 'legales/negocio/negocio_1020304050/rut_1020304050.pdf', 'legales/negocio/negocio_1020304050/doc_representante_1020304050.pdf', 'legales/negocio/negocio_1020304050/cert_bancario_1020304050.pdf', 'legales/negocio/negocio_1020304050/camara_comercio_1020304050.pdf')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), id_usuario=VALUES(id_usuario);

INSERT INTO menu_items (id_menu_item, id_negocio, id_categoria, nombre, descripcion, precio, imagen_url, on_promo, precio_promocion) VALUES
(1, 1, 3, 'Bowl Saludable', 'Bowl saludable con quinoa, aguacate, vegetales frescos, semillas, jugo de lim¾n y aderezo de hierbas. Una opci¾n equilibrada, colorida y llena de sabor para quienes buscan una comida nutritiva, ligera y satisfactoria a cualquier hora del dÝa.', 10000, '../assets/Imagenes_prueba/plato1.jpg', 0, NULL),
(2, 1, 3, 'Ensalada César', 'Lechuga, pollo, crutones y aderezo césar', 15000, '../assets/Imagenes_prueba/plato1.jpg', 0, NULL),
(3, 1, 1, 'Hamburguesa Vegana', 'Deliciosa Hamburguesa (Plant-Bassed)', 25000, '../assets/Imagenes_prueba/plato1.jpg', 0, NULL),
(4, 1, 3, 'Bowl de Quinoa', 'Descripción pendiente por definir', 18000, '../assets/Imagenes_prueba/plato1.jpg', 0, NULL),
(5, 1, 1, 'Tacos de Jackfruit', 'Deliciosos Tacos a base de frutas', 22500, '../assets/Imagenes_prueba/plato1.jpg', 0, NULL),
(6, 1, 1, '2x1 en pizza mediana', 'Válido solo los martes, no acumulable con otras promociones', 15000, '../Imagenes_prueba/pizzavegana.jpg', 0, NULL),
(7, 1, 1, 'Wrap de Falafel', 'Falafel casero con vegetales y salsa tahini', 18000, '../Imagenes_prueba/plato1.jpg', 0, NULL),
(8, 1, 2, 'Lasaña de Berenjena', 'Capas de berenjena, salsa napolitana y queso vegano', 28000, '../Imagenes_prueba/plato6.jpg', 0, NULL),
(9, 1, 4, 'Brownie Vegano', 'Brownie de chocolate 100% sin lácteos ni huevo', 9000, '../Imagenes_prueba/postre1.jpg', 0, NULL),
(10, 1, 5, 'Limonada de Coco', 'Limonada natural con leche de coco', 8000, '../Imagenes_prueba/bebida1.jpg', 0, NULL),
(11, 1, 6, 'Granola Bowl', 'Granola casera, frutas frescas y yogurt vegetal', 12000, '../Imagenes_prueba/desayuno6.jpg', 0, NULL),
(12, 1, 5, 'Jugo Verde Detox', 'Espinaca, apio, manzana verde y limón', 9000, '../Imagenes_prueba/bebida2.jpg', 0, NULL),
(13, 1, 7, 'Mix de Frutos Secos', 'Almendras, nueces, pasas y semillas', 7000, '../Imagenes_prueba/snack1.jpg', 0, NULL),
(14, 1, 3, 'Ensalada Mediterránea', 'Hummus, garbanzos, tomate y aceitunas', 16000, '../Imagenes_prueba/desayuno1.jpg', 0, NULL),
(15, 1, 3, 'Ensalada Kale & Aguacate', 'Kale, aguacate, quinoa y vinagreta de limón', 17000, '../Imagenes_prueba/desayuno2.jpg', 0, NULL),
(16, 1, 3, 'Bowl Buddha', 'Vegetales asados, garbanzos y tahini', 19000, '../Imagenes_prueba/desayuno3.jpg', 0, NULL),
(17, 1, 5, 'Smoothie Verde', 'Espinaca, piña y jengibre', 10000, '../Imagenes_prueba/bebida3.jpg', 0, NULL),
(18, 1, 6, 'Tostada de Aguacate', 'Pan integral, aguacate y semillas', 11000, '../Imagenes_prueba/desayuno7.jpg', 0, NULL),
(19, 1, 2, 'Pasta Vegana Alfredo', 'Pasta con salsa cremosa a base de anacardos', 24000, '../Imagenes_prueba/plato7.jpg', 0, NULL),
(20, 1, 4, 'Cheesecake Vegano', 'Cheesecake sin lácteos con frutos rojos', 10000, '../Imagenes_prueba/postre2.jpg', 0, NULL),
(21, 1, 5, 'Té Helado de Frutos Rojos', 'Té frío natural con frutos rojos', 7000, '../Imagenes_prueba/bebida4.jpg', 0, NULL),
(22, 1, 2, 'Cazuela de Mariscos', 'Cazuela criolla con camarón, mejillón y pescado', 32000, '../Imagenes_prueba/plato8.jpg', 0, NULL),
(23, 1, 1, 'Ceviche de Camarón', 'Ceviche fresco estilo costa pacífica', 26000, '../Imagenes_prueba/plato2.jpg', 0, NULL),
(24, 2, 2, 'Arroz con Coco y Camarón', 'Arroz de coco tradicional con camarón salteado', 28000, '../Imagenes_prueba/plato9.jpg', 0, NULL),
(25, 2, 7, 'Patacones con Camarón', 'Patacones crocantes con camarón al ajillo', 18000, '../Imagenes_prueba/snack2.jpg', 0, NULL),
(26, 2, 5, 'Jugo de Maracuyá', 'Jugo natural de maracuyá', 8000, '../Imagenes_prueba/bebida5.jpg', 0, NULL),
(27, 2, 1, 'Rollo California', 'Cangrejo, aguacate y pepino', 22000, '../Imagenes_prueba/plato3.jpg', 0, NULL),
(28, 2, 2, 'Rollo Sakura Especial', 'Salmón, queso crema y cebollín crocante', 27000, '../Imagenes_prueba/DonCamaronplato.jpg', 0, NULL),
(29, 2, 2, 'Sashimi Mixto', 'Selección de pescados frescos del día', 30000, '../Imagenes_prueba/veganrestaurantplato.jpg', 0, NULL),
(30, 2, 7, 'Gyozas', 'Empanadillas japonesas al vapor', 14000, '../Imagenes_prueba/snack3.jpg', 0, NULL),
(31, 2, 5, 'Té Verde Frío', 'Té verde japonés servido frío', 6000, '../Imagenes_prueba/bebida6.jpg', 0, NULL),
(32, 2, 1, 'Pizza Margarita Vegana', 'Salsa napolitana, mozzarella vegana y albahaca', 24000, '../Imagenes_prueba/plato4.jpg', 0, NULL),
(33, 2, 1, 'Pizza Hawaiana Vegana', 'Piña, jamón vegetal y mozzarella vegana', 26000, '../Imagenes_prueba/plato5.jpg', 1, NULL),
(34, 2, 2, 'Calzone Vegetariano', 'Calzone relleno de vegetales y queso', 23000, '../Imagenes_prueba/sushisakura.jpg', 0, NULL),
(35, 2, 4, 'Tiramisú Vegano', 'Tiramisú clásico sin lácteos ni huevo', 11000, '../Imagenes_prueba/postre3.jpg', 0, NULL),
(36, 2, 3, 'Bowl Energético', 'Quinoa, garbanzo, aguacate y semillas', 17000, '../Imagenes_prueba/desayuno4.jpg', 0, NULL),
(37, 2, 3, 'Bowl Tropical', 'Mango, piña, coco y granola', 18000, '../Imagenes_prueba/desayuno5.jpg', 0, NULL),
(38, 2, 3, 'Bowl Proteico', 'Tofu marinado, arroz integral y vegetales', 19000, '../Imagenes_prueba/snack6.jpg', 0, NULL),
(39, 2, 5, 'Smoothie de Mango', 'Mango, banano y leche de almendras', 9000, '../Imagenes_prueba/bebida7.jpg', 0, NULL),
(40, 2, 7, 'Chips de Plátano', 'Chips horneados de plátano verde', 6000, '../Imagenes_prueba/snack4.png', 0, NULL),
(41, 2, 4, 'Brownie sin Gluten', 'Brownie de chocolate apto para celíacos', 9000, '../Imagenes_prueba/postre4.jpg', 0, NULL),
(42, 2, 4, 'Cheesecake de Maracuyá', 'Cheesecake vegano con coulis de maracuyá', 11000, '../Imagenes_prueba/postre5.jpg', 0, NULL),
(43, 2, 7, 'Galletas de Avena', 'Galletas de avena y pasas sin lácteos', 6000, '../Imagenes_prueba/snack5.png', 0, NULL),
(44, 2, 4, 'Torta de Zanahoria Vegana', 'Torta húmeda de zanahoria con frosting vegano', 12000, '../Imagenes_prueba/postre6.jpg', 0, NULL),
(45, 2, 5, 'Chocolate Caliente Vegano', 'Chocolate caliente con leche de avena', 7000, '../Imagenes_prueba/bebida8.jpg', 1, NULL),
(48, 2, 1, 'hamburguesa', 'rica hamburguesa', 13000, '/shizen-home/public/images/catalogo/Imagenes_prueba/producto_1789620142_f18bdb96.jpg', 0, NULL)
ON DUPLICATE KEY UPDATE id_negocio=VALUES(id_negocio), nombre=VALUES(nombre), precio=VALUES(precio);
