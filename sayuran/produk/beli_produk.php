<?php
// D:\xampp\htdocs\harvestly_2\produk\beli_produk.php
// Halaman untuk detail produk dan opsi pembelian

session_start();
require_once __DIR__ . '/../config/db.php'; // Path ini sudah benar: naik ke harvestly_2, lalu masuk config

// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    // PERBAIKAN: Ubah path redirect ke login.php sesuai struktur folder
    header('Location: ../login/login.php');
    exit();
}

$product_id = $_GET['id'] ?? null;
if (!$product_id || !is_numeric($product_id)) {
    // produk.php ada di folder yang sama, path sudah benar
    header('Location: produk.php?status=error&message=' . urlencode('ID produk tidak valid.'));
    exit();
}

$product = null;
$message = '';
$status_type = '';

// Ambil pesan status dari URL (misalnya dari keranjang_belanja.php)
if (isset($_GET['status']) && isset($_GET['message'])) {
    $status_type = htmlspecialchars($_GET['status']);
    $message = htmlspecialchars($_GET['message']);
}

try {
    $pdo = get_pdo_connection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ambil detail produk
    $stmt = $pdo->prepare("SELECT id, name, description, price, stock, image_url FROM produk WHERE id = ? AND status_persetujuan = 'approved'");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        // produk.php ada di folder yang sama, path sudah benar
        header('Location: produk.php?status=error&message=' . urlencode('Produk tidak ditemukan atau belum disetujui.'));
        exit();
    }

} catch (PDOException $e) {
    $status_type = 'error';
    $message = "Terjadi kesalahan database: " . $e->getMessage();
}

$page_title = "Beli Produk - " . htmlspecialchars($product['name'] ?? 'Produk');
include_once __DIR__ . '/../include/header.php'; // Path ini sudah benar: naik ke harvestly_2, lalu masuk include
?>

<div class="container mt-5">
    <h2 class="mb-4">Detail Produk untuk Pembelian</h2>

    <?php if (!empty($message)): ?>
        <div class="alert <?php echo ($status_type === 'success') ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($product): ?>
        <div class="card mb-3">
            <div class="row g-0">
                <div class="col-md-4">
                    <img src="/sayuran/<?php echo htmlspecialchars($product['image_url'] ?? 'assets/img/default_product.jpg'); ?>" class="img-fluid rounded-start" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                <div class="col-md-8">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        <p class="card-text">Harga: Rp <?php echo number_format($product['price'], 0, ',', '.'); ?> / kg</p>
                        <p class="card-text">Stok Tersedia: <?php echo htmlspecialchars($product['stock']); ?> kg</p>

                        <?php if ($product['stock'] > 0): ?>
                            <form action="../keranjang/keranjang_belanja.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Jumlah (kg):</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="<?php echo htmlspecialchars($product['stock']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">Metode Pembayaran:</label>
                                    <select class="form-select" id="payment_method" name="payment_method" required>
                                        <option value="COD">Cash On Delivery (COD)</option>
                                        <option value="Transfer Bank">Transfer Bank</option>
                                    </select>
                                </div>
                                
                                <button type="submit" name="action" value="add_to_cart" class="btn btn-primary me-2">Tambahkan ke Keranjang</button>
                                <button type="submit" name="action" value="buy_now" class="btn btn-success">Beli Sekarang (Langsung)</button>
                                <a href="produk.php" class="btn btn-secondary ms-2">← Kembali</a>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning" role="alert">
                                Maaf, stok produk ini sedang kosong.
                            </div>
                            <a href="produk.php" class="btn btn-secondary">← Kembali ke Daftar Produk</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger" role="alert">
            Produk tidak dapat dimuat.
        </div>
        <a href="produk.php" class="btn btn-secondary">← Kembali ke Daftar Produk</a>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../include/footer.php'; // Path ini sudah benar ?>