<?php
require_once __DIR__ . '/../includes/config.php'; require_customer();
$userId=(int)current_user()['id'];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(); $action=(string)($_POST['action']??''); $cartId=(int)($_POST['cart_id']??0);
    if ($action==='update') {
        $quantity=max(1,(int)($_POST['quantity']??1));
        $check=db()->prepare('SELECT c.id,p.stock FROM cart_items c JOIN products p ON p.id=c.product_id WHERE c.id=? AND c.user_id=?'); $check->bind_param('ii',$cartId,$userId); $check->execute(); $row=$check->get_result()->fetch_assoc(); $check->close();
        if (!$row) flash('error','Item keranjang tidak ditemukan.');
        elseif($quantity>(int)$row['stock']) flash('error','Jumlah melebihi stok produk.');
        else { $stmt=db()->prepare('UPDATE cart_items SET quantity=? WHERE id=? AND user_id=?'); $stmt->bind_param('iii',$quantity,$cartId,$userId); $stmt->execute(); $stmt->close(); flash('success','Jumlah produk diperbarui.'); }
    } elseif($action==='remove') { $stmt=db()->prepare('DELETE FROM cart_items WHERE id=? AND user_id=?'); $stmt->bind_param('ii',$cartId,$userId); $stmt->execute(); $stmt->close(); flash('success','Produk dihapus dari keranjang.'); }
    redirect('pages/cart.php');
}
$stmt=db()->prepare('SELECT c.id AS cart_id,c.size,c.quantity,p.id AS product_id,p.name,p.price,p.stock,p.image_path,(p.price*c.quantity) AS subtotal FROM cart_items c JOIN products p ON p.id=c.product_id WHERE c.user_id=? ORDER BY c.created_at DESC'); $stmt->bind_param('i',$userId); $stmt->execute(); $items=$stmt->get_result();
$total=0; $rows=[]; while($row=$items->fetch_assoc()){ $total+=(float)$row['subtotal']; $rows[]=$row; } $stmt->close();
$pageTitle='Keranjang'; require __DIR__.'/../includes/header.php';
?>
<section class="section container"><div class="section-head"><div><span class="eyebrow">Belanja</span><h1>Keranjang</h1></div></div>
<?php if(!$rows): ?><div class="empty-state"><h3>Keranjang masih kosong</h3><p>Pilih produk sebelum melakukan checkout.</p><a class="btn btn-primary" href="<?= e(url('pages/products.php')) ?>">Lihat Produk</a></div><?php else: ?>
<div class="cart-layout"><div class="cart-list"><?php foreach($rows as $item): ?><article class="cart-item"><img src="<?= e(url($item['image_path'] ?: 'public/images/product-placeholder.svg')) ?>" alt="<?= e($item['name']) ?>"><div class="cart-info"><h3><?= e($item['name']) ?></h3><p>Ukuran <?= e($item['size']) ?> · <?= rupiah($item['price']) ?></p><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>"><input type="number" name="quantity" min="1" max="<?= (int)$item['stock'] ?>" value="<?= (int)$item['quantity'] ?>"><button class="btn btn-outline btn-sm">Perbarui</button></form></div><div class="cart-price"><strong><?= rupiah($item['subtotal']) ?></strong><form method="post" onsubmit="return confirm('Hapus produk dari keranjang?')"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>"><button class="link-danger" type="submit">Hapus</button></form></div></article><?php endforeach; ?></div>
<aside class="summary-card"><h3>Ringkasan Belanja</h3><div class="summary-row"><span>Total</span><strong><?= rupiah($total) ?></strong></div><a class="btn btn-primary btn-block" href="<?= e(url('pages/checkout.php')) ?>">Lanjut Checkout</a></aside></div><?php endif; ?></section>
<?php require __DIR__.'/../includes/footer.php'; ?>
