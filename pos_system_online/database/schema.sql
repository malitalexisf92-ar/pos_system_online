-- Run this only if you have NOT already created the five tables.
-- Your existing pos_system database should already contain these tables.
CREATE TABLE IF NOT EXISTS users (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 username VARCHAR(50) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 role ENUM('admin','cashier') DEFAULT 'cashier',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS categories (
 id INT AUTO_INCREMENT PRIMARY KEY,
 category_name VARCHAR(100) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS products (
 id INT AUTO_INCREMENT PRIMARY KEY,
 product_name VARCHAR(150) NOT NULL,
 category_id INT NULL,
 price DECIMAL(10,2) NOT NULL,
 stock INT DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS sales (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NULL,
 total_amount DECIMAL(10,2) NOT NULL,
 amount_paid DECIMAL(10,2) NOT NULL,
 change_amount DECIMAL(10,2) NOT NULL,
 sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS sale_items (
 id INT AUTO_INCREMENT PRIMARY KEY,
 sale_id INT NOT NULL,
 product_id INT NULL,
 quantity INT NOT NULL,
 price DECIMAL(10,2) NOT NULL,
 subtotal DECIMAL(10,2) NOT NULL,
 FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
 FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);
