<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_admin();
$pageTitle = 'Upgrade Visual v4';
$error = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = db();
    $conn->begin_transaction();
    try {
        $conn->query("INSERT INTO categories(name,description) VALUES
            ('Lifestyle','Sepatu gaya hidup untuk aktivitas harian.'),
            ('Trail','Sepatu dengan grip kuat untuk aktivitas luar ruang.')
            ON DUPLICATE KEY UPDATE description=VALUES(description)");

        $categoryIds = [];
        $result = $conn->query("SELECT id,name FROM categories WHERE name IN ('Sneakers','Running','Lifestyle','Trail')");
        while ($row = $result->fetch_assoc()) { $categoryIds[$row['name']] = (int) $row['id']; }

        $updates = [
            ['nebula-classic','Nebula Knit','Sneakers rajut minimal dengan bantalan nyaman untuk aktivitas harian.','Lifestyle','public/images/shoe-nebula.svg',399000],
            ['velocity-run','Velocity Run','Sepatu lari berwarna hitam dengan upper breathable dan bantalan responsif.','Running','public/images/shoe-velocity.svg',549000],
            ['midnight-oxford','Monochrome High','Sneakers high-top hitam dengan desain modern untuk gaya urban.','Lifestyle','public/images/shoe-oxford.svg',629000],
            ['echo-street','Echo Court','Sneakers court putih dengan aksen lime dan siluet bersih.','Sneakers','public/images/shoe-echo.svg',479000],
            ['drift-slide','Aero Motion','Sepatu latihan abu muda yang ringan dan nyaman untuk pemakaian aktif.','Running','public/images/shoe-drift.svg',489000],
            ['aero-pulse','Terra Pulse','Sepatu trail olive dengan outsole rugged dan grip stabil.','Trail','public/images/shoe-aero.svg',589000],
        ];
        $stmt = $conn->prepare('UPDATE products SET name=?,description=?,category_id=?,image_path=?,price=? WHERE slug=?');
        foreach ($updates as [$slug,$name,$description,$category,$image,$price]) {
            $categoryId = $categoryIds[$category] ?? null;
            if (!$categoryId) { throw new RuntimeException('Kategori ' . $category . ' tidak ditemukan.'); }
            $stmt->bind_param('ssisds', $name, $description, $categoryId, $image, $price, $slug);
            $stmt->execute();
        }
        $stmt->close();
        $conn->commit();
        $done = true;
    } catch (Throwable $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="section container">
  <div class="panel" style="max-width:760px;margin:auto">
    <span class="eyebrow">Visual Upgrade</span>
    <h1>Aktifkan gambar dan produk v4</h1>
    <p>Gunakan halaman ini satu kali jika database lama sudah terpasang. Data akun, keranjang, dan pesanan tidak dihapus.</p>
    <?php if ($done): ?>
      <div class="flash flash-success">Upgrade visual berhasil diterapkan.</div>
      <a class="btn btn-primary" href="<?= e(url()) ?>">Buka Toko</a>
    <?php else: ?>
      <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
      <form method="post" class="form-stack">
        <?= csrf_field() ?>
        <button class="btn btn-primary" type="submit">Terapkan Upgrade Visual</button>
      </form>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
