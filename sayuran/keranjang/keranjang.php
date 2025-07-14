<?php


session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_items = [];
$total_cart_price = 0;
$message = '';
$status_type = '';

// Ambil pesan status dari URL (misalnya dari keranjang_belanja.php setelah update/hapus)
if (isset($_GET['status']) && isset($_GET['message'])) {
    $status_type = htmlspecialchars($_GET['status']);
    $message = htmlspecialchars($_GET['message']);
}

try {
    $pdo = get_pdo_connection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ambil item di keranjang pengguna
    $stmt = $pdo->prepare("
        SELECT 
            k.id as cart_item_id, 
            k.product_id, 
            k.quantity, 
            p.name as product_name, 
            p.price, 
            p.stock,
            p.image_url
        FROM keranjang k
        JOIN produk p ON k.product_id = p.id
        WHERE k.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cart_items as $item) {
        $total_cart_price += $item['quantity'] * $item['price'];
    }

} catch (PDOException $e) {
    $status_type = 'error';
    $message = "Terjadi kesalahan database saat memuat keranjang: " . $e->getMessage();
}

$page_title = "Keranjang Belanja";
include_once __DIR__ . '/../include/header.php';
?>

<div class="container mt-5">
    <h2 class="mb-4">Keranjang Belanja Anda</h2>

    <?php if (!empty($message)): ?>
        <div class="alert <?php echo ($status_type === 'success') ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div class="alert alert-info text-center" role="alert">
            Keranjang belanja Anda kosong. <a href="../produk/produk.php" class="alert-link">Mulai belanja sekarang!</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-8">
                <?php foreach ($cart_items as $item): ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="row g-0 align-items-center">
                            <div class="col-md-3 text-center p-2">
                                <img src="/sayuran/<?php echo htmlspecialchars($item['image_url'] ?? 'assets/img/default_product.jpg'); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['product_name']); ?>" style="max-height: 100px; object-fit: cover;">
                            </div>
                            <div class="col-md-9">
                                <div class="card-body">
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h5>
                                    <p class="card-text mb-1"><small class="text-muted">Harga: Rp <?php echo number_format($item['price'], 0, ',', '.'); ?> / Kg</small></p>
                                    <p class="card-text mb-2"><small class="text-muted">Stok Tersedia: <?php echo htmlspecialchars($item['stock']); ?> Kg</small></p>

                                    <form action="keranjang_belanja.php" method="POST" class="d-flex align-items-center mb-2">
                                        <input type="hidden" name="cart_item_id" value="<?php echo htmlspecialchars($item['cart_item_id']); ?>">
                                        <label for="quantity_<?php echo htmlspecialchars($item['cart_item_id']); ?>" class="form-label me-2 mb-0">Jumlah:</label>
                                        <input type="number" class="form-control form-control-sm me-2" id="quantity_<?php echo htmlspecialchars($item['cart_item_id']); ?>" name="quantity" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="1" max="<?php echo htmlspecialchars($item['stock']); ?>" style="width: 80px;" required>
                                        <button type="submit" name="action" value="update_cart_quantity" class="btn btn-sm btn-outline-primary me-2">Update</button>
                                        <button type="submit" name="action" value="remove_from_cart" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                    <p class="card-text">Subtotal: <strong>Rp <?php echo number_format($item['quantity'] * $item['price'], 0, ',', '.'); ?></strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Ringkasan Pesanan</h5>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($cart_items as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($item['product_name']); ?> (<?php echo htmlspecialchars($item['quantity']); ?> Kg)
                                    <span>Rp <?php echo number_format($item['quantity'] * $item['price'], 0, ',', '.'); ?></span>
                                </li>
                            <?php endforeach; ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center active">
                                <strong>Total Belanja:</strong>
                                <strong>Rp <?php echo number_format($total_cart_price, 0, ',', '.'); ?></strong>
                            </li>
                        </ul>
                        <hr>
                        <form action="keranjang_belanja.php" method="POST">
                            <input type="hidden" name="action" value="checkout_cart">
                            
                            <div class="mb-3">
                                <label for="payment_method_checkout" class="form-label">Metode Pembayaran:</label>
                                <select class="form-select" id="payment_method_checkout" name="payment_method" required>
                                    <option value="COD">Cash On Delivery (COD)</option>
                                    <option value="Transfer Bank">Transfer Bank</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-money-check-alt"></i> Lanjutkan Pembayaran
                            </button>
                        </form>
                        <a href="../produk/produk.php" class="btn btn-outline-secondary w-100 mt-2">Lanjutkan Belanja</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../include/footer.php'; ?>