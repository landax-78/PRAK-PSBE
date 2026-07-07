<?php
declare(strict_types=1);
define('ALLOW_NO_DATABASE', true);
require_once __DIR__ . '/includes/config.php';

$messages = [];
$error = '';
$success = false;
$resetDatabase = true;
$currentStep = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $resetDatabase = isset($_POST['reset_database']);

    if (!extension_loaded('mysqli')) {
        $error = 'Ekstensi mysqli belum aktif. Aktifkan extension=mysqli pada php.ini, lalu restart Apache.';
    } else {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $currentStep = 'menghubungkan aplikasi ke MySQL';
            $installer = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
            $installer->set_charset('utf8mb4');

            if ($resetDatabase) {
                // Reset harus menghapus DATABASE, bukan hanya beberapa tabel.
                // Cara ini membersihkan seluruh tabel lama dan foreign key yang mungkin tersisa.
                $currentStep = 'menghapus database lama';
                $installer->query('DROP DATABASE IF EXISTS `' . DB_NAME . '`');
            }

            $currentStep = 'membuat database';
            $installer->query(
                'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` ' .
                'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
            $installer->select_db(DB_NAME);
            $installer->query('SET FOREIGN_KEY_CHECKS=0');

            // Jika pengguna tidak mereset database, bersihkan tabel aplikasi secara lengkap.
            // Daftar ini juga mencakup nama tabel dari versi proyek sebelumnya.
            if (!$resetDatabase) {
                $currentStep = 'membersihkan tabel versi sebelumnya';
                $legacyTables = [
                    'order_items', 'order_details', 'orders', 'transactions', 'transaction_details',
                    'transaksi', 'transaksi_detail', 'cart_items', 'cart', 'carts',
                    'products', 'product_sizes', 'categories', 'users', 'app_meta'
                ];
                foreach ($legacyTables as $table) {
                    $installer->query('DROP TABLE IF EXISTS `' . $table . '`');
                }
            }

            $currentStep = 'membuat tabel metadata';
            $installer->query(
                "CREATE TABLE app_meta (
                    meta_key VARCHAR(100) NOT NULL,
                    meta_value VARCHAR(255) NOT NULL,
                    PRIMARY KEY (meta_key)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $currentStep = 'membuat tabel pengguna';
            $installer->query(
                "CREATE TABLE users (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    name VARCHAR(120) NOT NULL,
                    email VARCHAR(190) NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uniq_users_email (email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $currentStep = 'membuat tabel kategori';
            $installer->query(
                "CREATE TABLE categories (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    name VARCHAR(100) NOT NULL,
                    description VARCHAR(255) NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uniq_categories_name (name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $currentStep = 'membuat tabel produk';
            $installer->query(
                "CREATE TABLE products (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    category_id BIGINT UNSIGNED NULL,
                    name VARCHAR(160) NOT NULL,
                    slug VARCHAR(180) NOT NULL,
                    description TEXT NULL,
                    price DECIMAL(12,2) NOT NULL DEFAULT 0,
                    stock INT UNSIGNED NOT NULL DEFAULT 0,
                    sizes VARCHAR(120) NOT NULL DEFAULT '38,39,40,41,42',
                    image_path VARCHAR(255) NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uniq_products_slug (slug),
                    KEY idx_products_category (category_id),
                    KEY idx_products_active (is_active),
                    CONSTRAINT fk_products_category
                        FOREIGN KEY (category_id) REFERENCES categories(id)
                        ON DELETE SET NULL ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $currentStep = 'membuat tabel keranjang';
            $installer->query(
                "CREATE TABLE cart_items (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id BIGINT UNSIGNED NOT NULL,
                    product_id BIGINT UNSIGNED NOT NULL,
                    size VARCHAR(10) NOT NULL,
                    quantity INT UNSIGNED NOT NULL DEFAULT 1,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uniq_cart_item (user_id, product_id, size),
                    KEY idx_cart_product (product_id),
                    CONSTRAINT fk_cart_user
                        FOREIGN KEY (user_id) REFERENCES users(id)
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT fk_cart_product
                        FOREIGN KEY (product_id) REFERENCES products(id)
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $currentStep = 'membuat tabel pesanan';
            $installer->query(
                "CREATE TABLE orders (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id BIGINT UNSIGNED NOT NULL,
                    invoice_number VARCHAR(40) NOT NULL,
                    recipient_name VARCHAR(120) NOT NULL,
                    phone VARCHAR(30) NOT NULL,
                    shipping_address TEXT NOT NULL,
                    payment_method ENUM('transfer','cod') NOT NULL DEFAULT 'transfer',
                    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                    status ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
                    notes VARCHAR(255) NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uniq_orders_invoice (invoice_number),
                    KEY idx_orders_user (user_id),
                    KEY idx_orders_status (status),
                    CONSTRAINT fk_orders_user
                        FOREIGN KEY (user_id) REFERENCES users(id)
                        ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $currentStep = 'membuat tabel detail pesanan';
            $installer->query(
                "CREATE TABLE order_items (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    order_id BIGINT UNSIGNED NOT NULL,
                    product_id BIGINT UNSIGNED NULL,
                    product_name VARCHAR(160) NOT NULL,
                    size VARCHAR(10) NOT NULL,
                    price DECIMAL(12,2) NOT NULL,
                    quantity INT UNSIGNED NOT NULL,
                    subtotal DECIMAL(12,2) NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_order_items_order (order_id),
                    KEY idx_order_items_product (product_id),
                    CONSTRAINT fk_order_items_order
                        FOREIGN KEY (order_id) REFERENCES orders(id)
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT fk_order_items_product
                        FOREIGN KEY (product_id) REFERENCES products(id)
                        ON DELETE SET NULL ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $installer->query('SET FOREIGN_KEY_CHECKS=1');

            $currentStep = 'menyimpan versi database';
            $version = APP_SCHEMA_VERSION;
            $metaStmt = $installer->prepare(
                "INSERT INTO app_meta(meta_key, meta_value) VALUES('schema_version', ?)
                 ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)"
            );
            $metaStmt->bind_param('s', $version);
            $metaStmt->execute();
            $metaStmt->close();

            $currentStep = 'membuat akun pengguna';
            $users = [
                ['Artika Dhea Maharani', 'artika@shoeshoegaze.com', 'admin'],
                ['Siti Zulaikha Akbar', 'siti@shoeshoegaze.com', 'customer'],
                ['Ismail Wahyu Ginanjar', 'ismail@shoeshoegaze.com', 'customer'],
                ['Bintoro', 'bintoro@shoeshoegaze.com', 'customer'],
            ];
            $userStmt = $installer->prepare(
                'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)'
            );
            foreach ($users as [$name, $email, $role]) {
                $hash = password_hash('password', PASSWORD_BCRYPT);
                $userStmt->bind_param('ssss', $name, $email, $hash, $role);
                $userStmt->execute();
            }
            $userStmt->close();

            $currentStep = 'membuat kategori';
            $categories = [
                ['Sneakers', 'Sepatu kasual untuk aktivitas harian.'],
                ['Running', 'Sepatu olahraga dan lari.'],
                ['Lifestyle', 'Sepatu gaya hidup untuk aktivitas harian.'],
                ['Trail', 'Sepatu dengan grip kuat untuk aktivitas luar ruang.'],
            ];
            $catStmt = $installer->prepare('INSERT INTO categories(name, description) VALUES(?, ?)');
            foreach ($categories as [$name, $description]) {
                $catStmt->bind_param('ss', $name, $description);
                $catStmt->execute();
            }
            $catStmt->close();

            $currentStep = 'membuat produk contoh';
            $categoryMap = [];
            $result = $installer->query('SELECT id, name FROM categories');
            while ($row = $result->fetch_assoc()) {
                $categoryMap[$row['name']] = (int) $row['id'];
            }

            $products = [
                [$categoryMap['Lifestyle'], 'Nebula Knit', 'nebula-classic', 'Sneakers rajut minimal dengan bantalan nyaman untuk aktivitas harian.', 399000, 24, '38,39,40,41,42,43', 'public/images/shoe-nebula.svg'],
                [$categoryMap['Running'], 'Velocity Run', 'velocity-run', 'Sepatu lari berwarna hitam dengan upper breathable dan bantalan responsif.', 549000, 18, '39,40,41,42,43,44', 'public/images/shoe-velocity.svg'],
                [$categoryMap['Lifestyle'], 'Monochrome High', 'midnight-oxford', 'Sneakers high-top hitam dengan desain modern untuk gaya urban.', 629000, 12, '39,40,41,42,43', 'public/images/shoe-oxford.svg'],
                [$categoryMap['Sneakers'], 'Echo Court', 'echo-street', 'Sneakers court putih dengan aksen lime dan siluet bersih.', 479000, 20, '38,39,40,41,42', 'public/images/shoe-echo.svg'],
                [$categoryMap['Running'], 'Aero Motion', 'drift-slide', 'Sepatu latihan abu muda yang ringan dan nyaman untuk pemakaian aktif.', 489000, 30, '38,39,40,41,42,43', 'public/images/shoe-drift.svg'],
                [$categoryMap['Trail'], 'Terra Pulse', 'aero-pulse', 'Sepatu trail olive dengan outsole rugged dan grip stabil.', 589000, 15, '39,40,41,42,43,44', 'public/images/shoe-aero.svg'],
            ];
            $productStmt = $installer->prepare(
                'INSERT INTO products '
                . '(category_id, name, slug, description, price, stock, sizes, image_path, is_active) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)'
            );
            foreach ($products as [$categoryId, $name, $slug, $description, $price, $stock, $sizes, $imagePath]) {
                $productStmt->bind_param(
                    'isssdiss',
                    $categoryId,
                    $name,
                    $slug,
                    $description,
                    $price,
                    $stock,
                    $sizes,
                    $imagePath
                );
                $productStmt->execute();
            }
            $productStmt->close();

            $success = true;
            $messages[] = 'Database lama dan seluruh foreign key lama berhasil dibersihkan.';
            $messages[] = 'Database, tabel, akun, kategori, dan produk berhasil dibuat.';
        } catch (Throwable $exception) {
            $detail = $exception->getMessage();
            $error = 'Instalasi gagal pada langkah ' . ($currentStep !== '' ? $currentStep : 'tidak diketahui') . ': ' . $detail;
        } finally {
            if (isset($installer) && $installer instanceof mysqli) {
                try {
                    $installer->query('SET FOREIGN_KEY_CHECKS=1');
                } catch (Throwable) {
                    // Abaikan jika database belum terpilih atau koneksi sudah terputus.
                }
                $installer->close();
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalasi ShoeShoeGaze</title>
    <link rel="stylesheet" href="<?= e(url('public/css/style.css')) ?>">
</head>
<body>
<div class="auth-shell">
    <div class="auth-card wide-card">
        <a class="brand brand-center" href="<?= e(url()) ?>"><span class="brand-mark">SG</span><span>ShoeShoeGaze</span></a>
        <h1>Instalasi Sistem</h1>
        <p>Gunakan halaman ini satu kali untuk membuat ulang database, tabel, akun, dan data awal.</p>
        <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?>
            <div class="flash flash-success"><?php foreach ($messages as $message): ?><div><?= e($message) ?></div><?php endforeach; ?></div>
            <div class="credential-box"><strong>Akun Admin</strong><br>Email: artika@shoeshoegaze.com<br>Password: password</div>
            <a class="btn btn-primary btn-block" href="<?= e(url('pages/login.php')) ?>">Buka Halaman Login</a>
        <?php else: ?>
            <div class="info-box">
                <strong>Konfigurasi MySQL yang digunakan:</strong><br>
                Host: <?= e(DB_HOST) ?> · Port: <?= e((string) DB_PORT) ?> · User: <?= e(DB_USER) ?> · Database: <?= e(DB_NAME) ?>
            </div>
            <form method="post" class="form-stack">
                <?= csrf_field() ?>
                <label class="check-label"><input type="checkbox" name="reset_database" value="1" checked> Hapus database lama dan pasang struktur baru</label>
                <div class="flash flash-warning">Pilihan ini menghapus seluruh database shoeshoegaze lama, termasuk foreign key yang tersisa, lalu memasang struktur baru yang bersih.</div>
                <button class="btn btn-primary btn-block" type="submit">Mulai Instalasi</button>
            </form>
            <p class="small-text">Pastikan Apache dan MySQL pada XAMPP sudah aktif. Konfigurasi default memakai user root tanpa password.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
