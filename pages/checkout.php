<?php
require_once __DIR__ . '/../includes/config.php'; require_customer();
$user=current_user(); $userId=(int)$user['id']; $error='';
function fetch_cart(mysqli $db,int $userId): array { $s=$db->prepare('SELECT c.product_id,c.size,c.quantity,p.name,p.price,p.stock,p.is_active FROM cart_items c JOIN products p ON p.id=c.product_id WHERE c.user_id=?'); $s->bind_param('i',$userId); $s->execute(); $r=$s->get_result(); $items=[]; while($row=$r->fetch_assoc())$items[]=$row; $s->close(); return $items; }
$cart=fetch_cart(db(),$userId); if(!$cart){ flash('warning','Keranjang masih kosong.'); redirect('pages/cart.php'); }
$total=array_reduce($cart,fn($sum,$item)=>$sum+((float)$item['price']*(int)$item['quantity']),0.0);
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf(); $recipient=trim((string)($_POST['recipient_name']??'')); $phone=trim((string)($_POST['phone']??'')); $address=trim((string)($_POST['shipping_address']??'')); $payment=(string)($_POST['payment_method']??''); $notes=trim((string)($_POST['notes']??''));
    if(strlen($recipient)<3)$error='Nama penerima minimal 3 karakter.'; elseif(strlen($phone)<8)$error='Nomor telepon tidak valid.'; elseif(strlen($address)<15)$error='Alamat pengiriman minimal 15 karakter.'; elseif(!in_array($payment,['transfer','cod'],true))$error='Metode pembayaran tidak valid.';
    else{
        $conn=db(); $conn->begin_transaction();
        try{
            $lock=$conn->prepare('SELECT c.product_id,c.size,c.quantity,p.name,p.price,p.stock,p.is_active FROM cart_items c JOIN products p ON p.id=c.product_id WHERE c.user_id=? FOR UPDATE'); $lock->bind_param('i',$userId); $lock->execute(); $result=$lock->get_result(); $locked=[]; while($row=$result->fetch_assoc())$locked[]=$row; $lock->close();
            if(!$locked)throw new RuntimeException('Keranjang kosong.'); $grandTotal=0;
            foreach($locked as $item){ if(!(int)$item['is_active'])throw new RuntimeException('Produk '.$item['name'].' tidak aktif.'); if((int)$item['quantity']>(int)$item['stock'])throw new RuntimeException('Stok '.$item['name'].' tidak mencukupi.'); $grandTotal+=(float)$item['price']*(int)$item['quantity']; }
            $invoice='SGZ-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(3))); $status='pending';
            $order=$conn->prepare('INSERT INTO orders(user_id,invoice_number,recipient_name,phone,shipping_address,payment_method,total_amount,status,notes) VALUES(?,?,?,?,?,?,?,?,?)'); $order->bind_param('isssssdss',$userId,$invoice,$recipient,$phone,$address,$payment,$grandTotal,$status,$notes); $order->execute(); $orderId=$conn->insert_id; $order->close();
            $itemStmt=$conn->prepare('INSERT INTO order_items(order_id,product_id,product_name,size,price,quantity,subtotal) VALUES(?,?,?,?,?,?,?)'); $stockStmt=$conn->prepare('UPDATE products SET stock=stock-? WHERE id=? AND stock>=?');
            foreach($locked as $item){ $productId=(int)$item['product_id']; $name=$item['name']; $size=$item['size']; $price=(float)$item['price']; $qty=(int)$item['quantity']; $subtotal=$price*$qty; $itemStmt->bind_param('iissdid',$orderId,$productId,$name,$size,$price,$qty,$subtotal); $itemStmt->execute(); $stockStmt->bind_param('iii',$qty,$productId,$qty); $stockStmt->execute(); if($stockStmt->affected_rows!==1)throw new RuntimeException('Gagal mengurangi stok produk.'); }
            $itemStmt->close(); $stockStmt->close(); $clear=$conn->prepare('DELETE FROM cart_items WHERE user_id=?'); $clear->bind_param('i',$userId); $clear->execute(); $clear->close(); $conn->commit(); flash('success','Pesanan berhasil dibuat dengan nomor '.$invoice.'.'); redirect('pages/order_detail.php?id='.$orderId);
        }catch(Throwable $ex){ $conn->rollback(); $error=$ex->getMessage(); }
    }
}
$pageTitle='Checkout'; require __DIR__.'/../includes/header.php';
?>
<section class="section container"><div class="section-head"><div><span class="eyebrow">Penyelesaian Pesanan</span><h1>Checkout</h1></div></div><?php if($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
<div class="checkout-grid"><form method="post" class="panel form-stack"><?= csrf_field() ?><h3>Data Pengiriman</h3><label>Nama penerima<input name="recipient_name" value="<?= old('recipient_name',$user['name']) ?>" required></label><label>Nomor telepon<input name="phone" value="<?= old('phone') ?>" required></label><label>Alamat lengkap<textarea name="shipping_address" rows="5" required><?= old('shipping_address') ?></textarea></label><label>Metode pembayaran<select name="payment_method" required><option value="transfer">Transfer Bank</option><option value="cod">Bayar di Tempat (COD)</option></select></label><label>Catatan<textarea name="notes" rows="3"><?= old('notes') ?></textarea></label><button class="btn btn-primary" type="submit">Buat Pesanan</button></form>
<aside class="summary-card"><h3>Ringkasan</h3><?php foreach($cart as $item): ?><div class="summary-row"><span><?= e($item['name']) ?> × <?= (int)$item['quantity'] ?><small>Ukuran <?= e($item['size']) ?></small></span><strong><?= rupiah((float)$item['price']*(int)$item['quantity']) ?></strong></div><?php endforeach; ?><hr><div class="summary-row total"><span>Total</span><strong><?= rupiah($total) ?></strong></div></aside></div></section>
<?php require __DIR__.'/../includes/footer.php'; ?>
