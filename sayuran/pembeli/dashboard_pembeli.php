<?php
// D:\xampp\htdocs\sayuran\pembeli\dashboard_pembeli.php

session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pembeli') {
    header('Location: ../login/login.php');
    exit();
}

$page_title = "Dashboard Pembeli - KangSayur";
include_once __DIR__ . '/../include/header.php';

$user_id = $_SESSION['user_id'];
$nama_pembeli = $_SESSION['nama'];

$orders = [];
$jumlah_diproses = 0;
$jumlah_selesai = 0;
$total_item_dibeli = 0;
$total_pembelian = 0;
$error_message = '';

try {
    $pdo = get_pdo_connection();

    // Ambil semua pesanan milik pembeli
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as $order) {
        // Hitung pesanan diproses dan selesai
        if (in_array($order['status'], ['pending', 'processing', 'shipped'])) {
            $jumlah_diproses++;
        } elseif (in_array($order['status'], ['completed', 'complete'])) {
            $jumlah_selesai++;
        }
        $total_pembelian += (float)$order['total_amount'];
    }

    // Hitung total item yang dibeli
    $stmt_items = $pdo->prepare("
        SELECT SUM(oi.quantity) 
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = ?
    ");
    $stmt_items->execute([$user_id]);
    $total_item_dibeli = (int)$stmt_items->fetchColumn();

} catch (PDOException $e) {
    $error_message = "Kesalahan database: " . $e->getMessage();
}
?>

<div class="container mt-5">
    <h2 class="mb-3">Selamat Datang, <?php echo htmlspecialchars($nama_pembeli); ?>!</h2>
    <p>Berikut adalah ringkasan dan riwayat pesanan Anda di KangSayur.</p>

    <a href="#riwayat-pesanan" class="btn btn-outline-primary mb-4">⬇ Lihat Riwayat Pesanan</a>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-warning text-dark text-center">
                <div class="card-body">
                    <h5 class="card-title">Pesanan Diproses</h5>
                    <p class="fs-1"><?php echo $jumlah_diproses; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white text-center">
                <div class="card-body">
                    <h5 class="card-title">Pesanan Selesai</h5>
                    <p class="fs-1"><?php echo $jumlah_selesai; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Produk Dibeli</h5>
                    <p class="fs-1"><?php echo $total_item_dibeli; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark text-white text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Harga Dibeli</h5>
                    <p class="fs-2">Rp <?php echo number_format($total_pembelian, 0, ',', '.'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- RIWAYAT PESANAN -->
    <h4 id="riwayat-pesanan">Riwayat Pesanan</h4>

    <?php if (empty($orders)): ?>
        <div class="alert alert-info text-center">Belum ada pesanan.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Tanggal</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Detail</th>
                        <th>Invoice</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($o['id']); ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($o['order_date'])); ?></td>
                            <td>Rp <?php echo number_format($o['total_amount'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="badge 
                                    <?php 
                                        if ($o['status'] === 'pending') echo 'bg-secondary';
                                        elseif ($o['status'] === 'processing') echo 'bg-warning text-dark';
                                        elseif ($o['status'] === 'shipped') echo 'bg-info text-dark';
                                        elseif (in_array($o['status'], ['completed', 'complete'])) echo 'bg-success';
                                        else echo 'bg-dark';
                                    ?>">
                                    <?php echo ucfirst($o['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="detail_pesanan.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-info">Lihat</a>
                            </td>
                            <td>
                                <a href="invoice.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-secondary" target="_blank">
                                    <i class="fas fa-print"></i> Cetak
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../include/footer.php'; ?>
