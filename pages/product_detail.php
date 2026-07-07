<?php
require_once __DIR__ . '/../includes/config.php';
$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT p.*,c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.is_active=1');
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Produk Tidak Ditemukan';
    require __DIR__ . '/../includes/header.php';
    echo '<section class="section container"><div class="empty-state"><h1>Produk tidak ditemukan</h1><p>Produk mungkin sudah dihapus atau tidak aktif.</p><a class="btn btn-primary" href="' . e(url('pages/products.php')) . '">Kembali ke Koleksi</a></div></section>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_customer();
    verify_csrf();
    $size = trim((string) ($_POST['size'] ?? ''));
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
    $allowed = array_map('trim', explode(',', $product['sizes']));
    if (!in_array($size, $allowed, true)) {
        $error = 'Ukuran sepatu tidak valid.';
    } elseif ($quantity > (int) $product['stock']) {
        $error = 'Jumlah melebihi stok yang tersedia.';
    } else {
        $userId = (int) current_user()['id'];
        $cart = db()->prepare('INSERT INTO cart_items(user_id,product_id,size,quantity) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE quantity=LEAST(quantity+VALUES(quantity), ?)');
        $maxStock = (int) $product['stock'];
        $cart->bind_param('iisii', $userId, $id, $size, $quantity, $maxStock);
        $cart->execute();
        $cart->close();
        flash('success', 'Produk ditambahkan ke keranjang.');
        redirect('pages/cart.php');
    }
}

$pageTitle = $product['name'];
require __DIR__ . '/../includes/header.php';
$sizes = array_filter(array_map('trim', explode(',', $product['sizes'])));
?>
<section class="section container">
    <div class="detail-breadcrumb">
        <a href="<?= e(url()) ?>">Beranda</a><span>›</span>
        <a href="<?= e(url('pages/products.php')) ?>">Koleksi</a><span>›</span>
        <span><?= e($product['name']) ?></span>
    </div>
    <div class="detail-grid">
        <div class="detail-image">
            <img src="<?= e(url($product['image_path'] ?: 'public/images/product-placeholder.svg')) ?>" alt="<?= e($product['name']) ?>">
        </div>
        <div class="detail-info">
            <span class="eyebrow"><?= e($product['category_name'] ?? 'Tanpa Kategori') ?></span>
            <h1><?= e($product['name']) ?></h1>
            <div class="detail-price"><?= rupiah($product['price']) ?></div>
            <p class="detail-description"><?= nl2br(e($product['description'])) ?></p>
            <?php if ((int) $product['stock'] > 0): ?>
                <div class="stock-line">Stok tersedia: <?= (int) $product['stock'] ?> unit</div>
            <?php endif; ?>

            <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

            <?php if ((int) $product['stock'] > 0): ?>
                <form method="post" class="purchase-form">
                    <?= csrf_field() ?>
                    <label>Ukuran
                        <select name="size" required>
                            <option value="">Pilih ukuran</option>
                            <?php foreach ($sizes as $size): ?><option value="<?= e($size) ?>"><?= e($size) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label>Jumlah
                        <input type="number" name="quantity" min="1" max="<?= (int) $product['stock'] ?>" value="1" required>
                    </label>
                    <button class="btn btn-primary" type="submit">Tambah ke Keranjang</button>
                </form>
            <?php else: ?>
                <div class="flash flash-warning">Stok produk sedang habis.</div>
            <?php endif; ?>

            <div class="detail-benefits">
                <div class="detail-benefit"><strong>Pembayaran Aman</strong>Transfer bank atau COD.</div>
                <div class="detail-benefit"><strong>Retur 7 Hari</strong>Proses retur lebih mudah.</div>
                <div class="detail-benefit"><strong>Produk Terpilih</strong>Kualitas diperiksa sebelum dikirim.</div>
                <div class="detail-benefit"><strong>Pengiriman Cepat</strong>Pesanan segera diproses.</div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
