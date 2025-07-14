<?php
// D:\xampp\htdocs\harvestly_2\pesanan_sukses.php

session_start();
// Mengambil order_id dari parameter URL
$order_id = $_GET['order_id'] ?? null;

$page_title = "Pesanan Berhasil - KangSayur";
include_once __DIR__ . '/include/header.php';
?>

<div class="container mt-5">
    <div class="text-center p-5 border rounded shadow-sm bg-white">
        <i class="fas fa-check-circle text-success mb-4" style="font-size: 5rem;"></i>
        <h2 class="mb-3 text-success">Pesanan Berhasil Dibuat!</h2>
        <p class="lead">Terima kasih atas pesanan Anda.</p>
        <?php if ($order_id): ?>
            <p class="mb-4">Nomor Pesanan Anda adalah: <strong>#<?php echo htmlspecialchars($order_id); ?></strong></p>
        <?php endif; ?>
        <p>Kami akan segera memproses pesanan Anda.</p>
        <div class="mt-4">
            <a href="produk.php" class="btn btn-primary btn-lg"><i class="fas fa-shopping-bag"></i> Lanjutkan Belanja</a>
            </div>
    </div>
</div>

<?php
include_once __DIR__ . '/include/footer.php';
?>