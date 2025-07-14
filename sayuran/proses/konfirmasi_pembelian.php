<?php
// D:\xampp\htdocs\harvestly_2\konfirmasi_pembelian.php

session_start();
// Tidak perlu require db.php jika hanya menampilkan pesan

$page_title = "Konfirmasi Pembelian - KangSayur";
include_once __DIR__ . '/../include/header.php';

$message = $_GET['message'] ?? 'Pembelian Anda telah berhasil diproses.';
$status_type = $_GET['status'] ?? 'success'; // success, error, warning, info
$alert_class = ($status_type === 'success') ? 'alert-success' : 'alert-danger'; // Sesuaikan jika ada status lain
?>

<div class="container mt-5">
    <h2 class="mb-4">Konfirmasi Pembelian</h2>

    <div class="alert <?php echo htmlspecialchars($alert_class); ?>" role="alert">
        <?php echo htmlspecialchars($message); ?>
    </div>

    <p>Terima kasih telah berbelanja di KangSayur!</p>
    <a href="../produk/produk.php" class="btn btn-primary">Kembali ke Daftar Produk</a>
</div>

<?php include_once __DIR__ . '/../include/footer.php'; ?>