-- ============================================
-- Lumina Shop Database Schema (MySQL)
-- ============================================
-- Run this in phpMyAdmin or MySQL command line.
-- The app itself uses SQLite automatically (database.sqlite);
-- this file is only needed if you want to use MySQL instead.

-- Create and select the database
CREATE DATABASE IF NOT EXISTS lumina_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lumina_shop;

-- -----------------------------------------------------
-- Users table
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed admin user (email: admin1@gmail.com, password: admin1)
-- Password hash generated with PHP password_hash('admin1', PASSWORD_DEFAULT)
INSERT IGNORE INTO users (username, email, password, is_admin) VALUES
('admin', 'admin1@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- -----------------------------------------------------
-- Products table
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(50) DEFAULT '📦',
    stock INT DEFAULT 0,
    category VARCHAR(50) DEFAULT 'General',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Orders table
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_address TEXT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending','processing','completed','cancelled') DEFAULT 'pending',
    stripe_payment_id VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Order items table
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Seed sample products
-- -----------------------------------------------------
INSERT INTO products (name, description, price, image, stock, category) VALUES
('Aurora Headphones', 'Immersive wireless headphones with 40hr battery', 149.00, '🎧', 25, 'Audio'),
('Nebula Watch', 'Sleek smartwatch with ECG and GPS', 299.00, '⌚', 18, 'Wearables'),
('Void Backpack', 'Minimalist anti-theft backpack with USB-C', 89.00, '🎒', 40, 'Bags'),
('Pulse Speaker', 'Crimson 360° sound portable speaker', 79.00, '🔊', 50, 'Audio'),
('Cyber Keyboard', 'Hot-swappable mechanical keyboard', 199.00, '⌨️', 30, 'Accessories'),
('Lens Webcam', '4K HDR webcam with auto-framing', 129.00, '📹', 35, 'Accessories'),
('Halo Monitor', '32" 4K curved gaming monitor', 599.00, '🖥️', 12, 'Displays'),
('Vortex Chair', 'Ergonomic mesh office chair', 449.00, '🪑', 8, 'Furniture');