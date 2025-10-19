-- Base de datos para la tienda BarbeX
CREATE DATABASE IF NOT EXISTS barbex_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE barbex_shop;

-- Agregar nuevas columnas a la tabla products existente
ALTER TABLE products
ADD COLUMN short_description VARCHAR(500) AFTER description,
ADD COLUMN sale_price DECIMAL(10,2) NULL AFTER price,
ADD COLUMN gallery_images TEXT COMMENT 'JSON array of image URLs' AFTER image,
ADD COLUMN tags VARCHAR(255) COMMENT 'Comma separated tags' AFTER category,
ADD COLUMN sku VARCHAR(100) UNIQUE AFTER tags,
ADD COLUMN stock_status ENUM('instock', 'outofstock', 'onbackorder') DEFAULT 'instock' AFTER stock,
ADD COLUMN weight DECIMAL(10,2) NULL AFTER stock_status,
ADD COLUMN dimensions VARCHAR(100) COMMENT 'LxWxH format' AFTER weight,
ADD COLUMN brand VARCHAR(100) AFTER dimensions,
ADD COLUMN featured BOOLEAN DEFAULT FALSE AFTER brand;

-- Tabla de usuarios
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    profile_image VARCHAR(255),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de sesiones de carrito
CREATE TABLE cart_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de items del carrito
CREATE TABLE cart_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(255) NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_session_product (session_id, product_id)
) ENGINE=InnoDB;

-- Tabla de pedidos
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    session_id VARCHAR(255),
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20),
    customer_address TEXT NOT NULL,
    customer_city VARCHAR(100),
    customer_state VARCHAR(100),
    customer_postcode VARCHAR(20),
    customer_country VARCHAR(100),
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) DEFAULT 0,
    shipping DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de items de pedidos
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Crear foreign keys con ALTER TABLE (más confiable)
-- ALTER TABLE cart_sessions ADD CONSTRAINT fk_cart_sessions_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
-- ALTER TABLE cart_items ADD CONSTRAINT fk_cart_items_product_id FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;
-- ALTER TABLE cart_items ADD CONSTRAINT fk_cart_items_session_id FOREIGN KEY (session_id) REFERENCES cart_sessions(session_id) ON DELETE CASCADE;
-- ALTER TABLE orders ADD CONSTRAINT fk_orders_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
-- ALTER TABLE order_items ADD CONSTRAINT fk_order_items_order_id FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;
-- Nota: Eliminamos TODAS las FK para simplificar y evitar problemas de dependencias
-- ALTER TABLE order_items ADD CONSTRAINT fk_order_items_product_id FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL;

-- Insertar productos de ejemplo con todas las características
INSERT INTO products (name, description, short_description, price, sale_price, image, gallery_images, category, tags, sku, stock, stock_status, weight, dimensions, brand, featured) VALUES
('New Fresh Wash', 'Producto de lavado facial premium para una piel fresca y limpia. Contiene ingredientes naturales que limpian profundamente sin resecar la piel. Ideal para uso diario.', 'Lavado facial premium con ingredientes naturales', 56.00, NULL, 'assets/img/products/products-1.jpg', '["assets/img/products/products-1.jpg","assets/img/products/products-11.jpg"]', 'Face Wash', 'limpieza,facial,natural', 'FW-001', 50, 'instock', 0.25, '10x5x15', 'BarbeX', TRUE),
('Face Cream', 'Crema facial hidratante con ingredientes naturales. Proporciona hidratación profunda y nutrición para todo tipo de piel. Absorción rápida sin dejar residuos grasos.', 'Crema hidratante con ingredientes naturales', 51.39, 45.99, 'assets/img/products/products-2.jpg', '["assets/img/products/products-2.jpg","assets/img/products/products-12.jpg"]', 'Face Cream', 'hidratante,facial,natural', 'FC-002', 30, 'instock', 0.30, '8x8x12', 'BarbeX', FALSE),
('Hair Treatment', 'Tratamiento capilar profesional para cabello dañado. Repara y fortalece el cabello desde la raíz hasta las puntas. Resultados visibles después del primer uso.', 'Tratamiento profesional para cabello dañado', 63.87, NULL, 'assets/img/products/products-3.jpg', '["assets/img/products/products-3.jpg","assets/img/products/products-13.jpg"]', 'Hair Care', 'tratamiento,dañado,reparador', 'HT-003', 25, 'instock', 0.40, '12x6x18', 'BarbeX', TRUE),
('Shampoo', 'Champú profesional para todo tipo de cabello. Limpia suavemente mientras nutre y protege el cuero cabelludo. Fórmula sin sulfatos.', 'Champú profesional sin sulfatos', 47.89, NULL, 'assets/img/products/products-4.jpg', '["assets/img/products/products-4.jpg","assets/img/products/products-14.jpg"]', 'Hair Care', 'champú,limpieza,natural', 'SH-004', 40, 'instock', 0.35, '9x7x22', 'BarbeX', FALSE),
('Conditioner', 'Acondicionador nutritivo para cabello. Proporciona suavidad y brillo excepcional. Facilita el peinado y protege contra el daño térmico.', 'Acondicionador nutritivo para brillo', 42.50, 38.99, 'assets/img/products/products-5.jpg', '["assets/img/products/products-5.jpg","assets/img/products/products-15.jpg"]', 'Hair Care', 'acondicionador,nutritivo,brillo', 'CO-005', 35, 'instock', 0.32, '9x7x22', 'BarbeX', FALSE),
('Beard Oil', 'Aceite para barba premium. Nutre y suaviza la barba y la piel debajo. Contiene aceites esenciales naturales para un aroma masculino.', 'Aceite premium para barba y piel', 38.99, NULL, 'assets/img/products/products-6.jpg', '["assets/img/products/products-6.jpg"]', 'Beard Care', 'barba,aceite,natural', 'BO-006', 20, 'instock', 0.15, '5x5x10', 'BarbeX', TRUE),
('Hair Wax', 'Cera para cabello con fijación fuerte. Proporciona estilo duradero sin dejar residuos. Ideal para looks modernos y clásicos.', 'Cera para fijación fuerte y duradera', 29.99, NULL, 'assets/img/products/products-7.jpg', '["assets/img/products/products-7.jpg"]', 'Hair Styling', 'cera,fijación,estilo', 'HW-007', 45, 'instock', 0.20, '6x6x8', 'BarbeX', FALSE),
('Face Mask', 'Máscara facial purificante. Elimina impurezas y toxinas profundas. Deja la piel fresca, renovada y radiante.', 'Máscara purificante para piel renovada', 24.50, 19.99, 'assets/img/products/products-8.jpg', '["assets/img/products/products-8.jpg"]', 'Face Care', 'máscara,purificante,renovadora', 'FM-008', 60, 'instock', 0.18, '7x7x5', 'BarbeX', FALSE);

-- Actualizar productos existentes para agregar las nuevas columnas
UPDATE products SET
    short_description = SUBSTRING(description, 1, 200),
    stock_status = 'instock',
    brand = 'BarbeX',
    featured = 0
WHERE short_description IS NULL;

-- Crear usuario admin de ejemplo
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@barbex.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Password: password