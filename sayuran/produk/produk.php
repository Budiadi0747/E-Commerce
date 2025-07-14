<?php
// D:\xampp\htdocs\harvestly_2\produk.php

session_start(); // Mulai sesi PHP untuk mengakses status login

// Sertakan file koneksi database Anda
require_once __DIR__ . '/../config/db.php';

$products = []; // Variabel untuk menyimpan data produk
$error_message = '';

try {
    $pdo = get_pdo_connection();
    // Query untuk mengambil data dari tabel 'produk' dengan nama kolom yang sesuai
    // Pastikan hanya produk yang disetujui ('approved') yang ditampilkan kepada pembeli
    $stmt = $pdo->prepare("SELECT id, name, description, price, stock, image_url FROM produk WHERE status_persetujuan = 'approved' ORDER BY name ASC");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan saat mengambil data produk: " . $e->getMessage();
    // Di lingkungan produksi, log error ini, jangan tampilkan ke user
}

// Judul halaman untuk header
$page_title = "Daftar Produk Panen - KangSayur";
include_once __DIR__ . '/../include/header.php';
?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Daftar Produk Panen KangSayur</h2>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <div class="alert alert-info text-center" role="alert">
            Belum ada produk yang tersedia saat ini.
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm p-2" style="font-size: 0.55rem;">
                        <a href="detail_produk.php?id=<?php echo htmlspecialchars($product['id']); ?>">
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="/sayuran/<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <img src="/sayuran/assets/img/no_image.png" class="card-img-top" alt="Gambar Tidak Tersedia" style="height: 100px; object-fit: cover;">
                            <?php endif; ?>
                        </a>
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="detail_produk.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h5>
                            <p class="card-text">
                                **Deskripsi:** <?php echo htmlspecialchars($product['description']); ?><br>
                                **Harga:** Rp <?php echo number_format($product['price'], 0, ',', '.'); ?> / kg<br>
                                **Stok:** <?php echo htmlspecialchars($product['stock']); ?> kg
                            </p>
                            <?php if ($product['stock'] > 0): ?>
                                <a href="beli_produk.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-success">Beli Sekarang</a>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>Stok Habis</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
include_once __DIR__ . '/../include/footer.php';
?>