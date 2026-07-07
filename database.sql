-- ShoeShoeGaze schema version 2.1
-- PERINGATAN: skrip ini menghapus database shoeshoegaze lama.
DROP DATABASE IF EXISTS shoeshoegaze;
CREATE DATABASE shoeshoegaze CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shoeshoegaze;

CREATE TABLE app_meta (
 meta_key VARCHAR(100) PRIMARY KEY,
 meta_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

INSERT INTO app_meta(meta_key,meta_value) VALUES('schema_version','2.1');

CREATE TABLE users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL UNIQUE,
 description VARCHAR(255) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_id BIGINT UNSIGNED NULL,
 name VARCHAR(160) NOT NULL,
 slug VARCHAR(180) NOT NULL UNIQUE,
 description TEXT NULL,
 price DECIMAL(12,2) NOT NULL DEFAULT 0,
 stock INT UNSIGNED NOT NULL DEFAULT 0,
 sizes VARCHAR(120) NOT NULL DEFAULT '38,39,40,41,42',
 image_path VARCHAR(255) NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_products_category(category_id),
 INDEX idx_products_active(is_active),
 CONSTRAINT fk_products_category FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 size VARCHAR(10) NOT NULL,
 quantity INT UNSIGNED NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uniq_cart_item(user_id,product_id,size),
 CONSTRAINT fk_cart_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT fk_cart_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 invoice_number VARCHAR(40) NOT NULL UNIQUE,
 recipient_name VARCHAR(120) NOT NULL,
 phone VARCHAR(30) NOT NULL,
 shipping_address TEXT NOT NULL,
 payment_method ENUM('transfer','cod') NOT NULL DEFAULT 'transfer',
 total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
 status ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
 notes VARCHAR(255) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_orders_user(user_id), INDEX idx_orders_status(status),
 CONSTRAINT fk_orders_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE order_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NULL,
 product_name VARCHAR(160) NOT NULL,
 size VARCHAR(10) NOT NULL,
 price DECIMAL(12,2) NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 subtotal DECIMAL(12,2) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_order_items_order(order_id),
 CONSTRAINT fk_order_items_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT fk_order_items_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Password seluruh akun awal: password
INSERT INTO users(name,email,password,role) VALUES
('Artika Dhea Maharani','artika@shoeshoegaze.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin'),
('Siti Zulaikha Akbar','siti@shoeshoegaze.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer'),
('Ismail Wahyu Ginanjar','ismail@shoeshoegaze.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer'),
('Bintoro','bintoro@shoeshoegaze.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer');

INSERT INTO categories(name,description) VALUES
('Sneakers','Sepatu kasual untuk aktivitas harian.'),
('Running','Sepatu olahraga dan lari.'),
('Lifestyle','Sepatu gaya hidup untuk aktivitas harian.'),
('Trail','Sepatu dengan grip kuat untuk aktivitas luar ruang.');

INSERT INTO products(category_id,name,slug,description,price,stock,sizes,image_path,is_active) VALUES
(3,'Nebula Knit','nebula-classic','Sneakers rajut minimal dengan bantalan nyaman untuk aktivitas harian.',399000,24,'38,39,40,41,42,43','public/images/shoe-nebula.svg',1),
(2,'Velocity Run','velocity-run','Sepatu lari berwarna hitam dengan upper breathable dan bantalan responsif.',549000,18,'39,40,41,42,43,44','public/images/shoe-velocity.svg',1),
(3,'Monochrome High','midnight-oxford','Sneakers high-top hitam dengan desain modern untuk gaya urban.',629000,12,'39,40,41,42,43','public/images/shoe-oxford.svg',1),
(1,'Echo Court','echo-street','Sneakers court putih dengan aksen lime dan siluet bersih.',479000,20,'38,39,40,41,42','public/images/shoe-echo.svg',1),
(2,'Aero Motion','drift-slide','Sepatu latihan abu muda yang ringan dan nyaman untuk pemakaian aktif.',489000,30,'38,39,40,41,42,43','public/images/shoe-drift.svg',1),
(4,'Terra Pulse','aero-pulse','Sepatu trail olive dengan outsole rugged dan grip stabil.',589000,15,'39,40,41,42,43,44','public/images/shoe-aero.svg',1);
