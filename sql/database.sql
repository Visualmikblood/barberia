-- Base de datos para la tienda BarbeX
CREATE DATABASE IF NOT EXISTS barbex_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE barbex_shop;

-- Tabla de productos
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    category VARCHAR(100),
    stock INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de usuarios
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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

-- Insertar productos de ejemplo
INSERT INTO products (name, description, price, image, category, stock) VALUES
('New Fresh Wash', 'Producto de lavado facial premium para una piel fresca y limpia', 56.00, 'assets/img/products/products-1.jpg', 'Face Wash', 50),
('Face Cream', 'Crema facial hidratante con ingredientes naturales', 51.39, 'assets/img/products/products-2.jpg', 'Face Cream', 30),
('Hair Treatment', 'Tratamiento capilar profesional para cabello dañado', 63.87, 'assets/img/products/products-3.jpg', 'Hair Care', 25),
('Shampoo', 'Champú profesional para todo tipo de cabello', 47.89, 'assets/img/products/products-4.jpg', 'Hair Care', 40),
('Conditioner', 'Acondicionador nutritivo para cabello', 42.50, 'assets/img/products/products-5.jpg', 'Hair Care', 35),
('Beard Oil', 'Aceite para barba premium', 38.99, 'assets/img/products/products-6.jpg', 'Beard Care', 20),
('Hair Wax', 'Cera para cabello con fijación fuerte', 29.99, 'assets/img/products/products-7.jpg', 'Hair Styling', 45),
('Face Mask', 'Máscara facial purificante', 24.50, 'assets/img/products/products-8.jpg', 'Face Care', 60);

-- Crear usuario admin de ejemplo
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@barbex.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Password: password