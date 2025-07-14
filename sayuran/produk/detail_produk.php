<?php
// D:\xampp\htdocs\harvestly_2\detail_produk.php

session_start();

require_once __DIR__ . '/../config/db.php';

$product = null;
$error_message = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: produk.php');
    exit();
}

$product_id = $_GET['id'];

try {
    $pdo = get_pdo_connection();
    // Pastikan produk juga disetujui untuk ditampilkan di detail produk
    $stmt = $pdo->prepare("SELECT id, name, description, price, stock, image_url, created_at FROM produk WHERE id = ? AND status_persetujuan = 'approved'");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $error_message = "Produk yang Anda cari tidak ditemukan atau belum disetujui.";
    }

} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan database: " . $e->getMessage();
}

$page_title = ($product) ? htmlspecialchars($product['name']) . " - Detail Produk" : "Detail Produk - KangSayur";
include_once __DIR__ . '/../include/header.php';
?>

<div class="container mt-5">
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger text-center" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
            <p class="mt-2"><a href="produk.php" class="btn btn-primary">Kembali ke Daftar Produk</a></p>
        </div>
    <?php elseif ($product): ?>
        <div class="row">
            <div class="col-md-6 mb-4">
                <?php if (!empty($product['image_url'])): ?>
                    <img src="/sayuran/<?php echo htmlspecialchars($product['image_url']); ?>" class="img-fluid rounded shadow-sm" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <?php else: ?>
                    <img src="/sayuran/assets/img/placeholder.png" class="img-fluid rounded shadow-sm" alt="Tidak ada gambar">
                <?php endif; ?>
            </div>
            <div class="col-md-6 mb-4">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="lead text-success">
                    <strong>Harga:</strong> Rp <?php echo number_format($product['price'], 0, ',', '.'); ?> / Kg
                </p>
                <p>
                    <strong>Stok Tersedia:</strong> <?php echo htmlspecialchars($product['stock']); ?> kg
                    <?php if ($product['stock'] > 0): ?>
                        <div class="mt-3">
                            <a href="beli_produk.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-success btn-lg">
                                <i class="fas fa-shopping-cart"></i> Beli Sekarang
                            </a>
                            <a href="produk.php" class="btn btn-outline-secondary btn-lg ms-2">Kembali ke Daftar Produk</a>
                        </div>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-lg mt-3" disabled>Stok Habis</button>
                        <a href="produk.php" class="btn btn-outline-secondary btn-lg mt-3 ms-2">Kembali ke Daftar Produk</a>
                    <?php endif; ?>
                </p>
                <hr>
                <h4>Deskripsi Produk:</h4>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                <p class="text-muted small">Ditambahkan pada: <?php echo htmlspecialchars(date('d M Y H:i', strtotime($product['created_at']))); ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
include_once __DIR__ . '/../include/footer.php';
?>