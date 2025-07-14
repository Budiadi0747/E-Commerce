<?php
// D:\xampp\htdocs\harvestly_2\pembeli\detail_pesanan.php

session_start();
require_once __DIR__ . '/../config/db.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pembeli') {
    header('Location: ../login/login.php');
    exit();
}

$page_title = "Detail Pesanan - KangSayur";
include_once __DIR__ . '/../include/header.php';

$order_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];
$order = null;
$items = [];
$error_message = '';

if (!$order_id || !is_numeric($order_id)) {
    $error_message = "ID pesanan tidak valid.";
} else {
    try {
        $pdo = get_pdo_connection();

        // Cek apakah pesanan milik pembeli ini
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // Ambil detail item dari tabel order_item
            $stmt_items = $pdo->prepare("SELECT oi.*, p.name AS product_name FROM order_items oi
                                         JOIN produk p ON oi.product_id = p.id
                                         WHERE oi.order_id = ?");
            $stmt_items->execute([$order_id]);
            $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error_message = "Pesanan tidak ditemukan atau bukan milik Anda.";
        }
    } catch (PDOException $e) {
        $error_message = "Kesalahan database: " . $e->getMessage();
    }
}
?>

<div class="container mt-5">
    <h2>Detail Pesanan</h2>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php elseif ($order): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5>ID Pesanan: <?php echo htmlspecialchars($order['id']); ?></h5>
                <p>Tanggal: <?php echo date('d-m-Y H:i', strtotime($order['order_date'])); ?></p>
                <p>Status: <span class="badge 
                    <?php
                        if ($order['status'] == 'diproses') echo 'bg-warning text-dark';
                        elseif ($order['status'] == 'selesai') echo 'bg-success';
                        else echo 'bg-secondary';
                    ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span></p>
                <p>Total: <strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong></p>
            </div>
        </div>

        <h4>Item Pesanan</h4>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td>Rp <?php echo number_format($item['price_at_order'], 0, ',', '.'); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>Rp <?php echo number_format($item['price_at_order'] * $item['quantity'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <a href="pembeli_dashboard.php" class="btn btn-secondary mt-3">Kembali ke Dashboard</a>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../include/footer.php'; ?>
