<?php


session_start();
require_once __DIR__ . '/../config/db.php';

// Pastikan user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // PERBAIKAN DI SINI: Path redirect ke login
    header('Location: ../login/login.php'); 
    exit();
}

$page_title = "Manajemen Pesanan - Admin KangSayur";
// Sertakan header admin
// PERBAIKAN DI SINI: Path include admin_header.php
include_once __DIR__ . '/admin_header.php';

$orders = [];
$error_message = '';

try {
    $pdo = get_pdo_connection();

    // Query untuk mengambil semua pesanan beserta informasi user dan alamat pengiriman
    // Menggunakan alias untuk kolom-kolom yang diambil dari JOIN
    $stmt = $pdo->prepare("
        SELECT
            o.id AS order_id,
            o.user_id,
            u.username AS customer_username,
            o.total_amount,
            o.status,
            o.order_date,
            a.address_line1,
            a.city,
            a.postal_code,
            a.phone_number AS shipping_phone_number
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN addresses a ON o.shipping_address_id = a.id
        ORDER BY o.order_date DESC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Tangani error database
    $error_message = "Terjadi kesalahan database saat mengambil data pesanan: " . $e->getMessage();
}

?>

<div class="container mt-5">
    <h2 class="mb-4">Manajemen Pesanan</h2>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="alert alert-info text-center" role="alert">
            Belum ada pesanan yang masuk saat ini.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Pelanggan</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                        <th>Tanggal Pesan</th>
                        <th>Alamat Pengiriman</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_username']); ?></td>
                            <td>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="badge 
                                    <?php 
                                        // Menggunakan nilai status yang sesuai dengan ENUM di database
                                        if ($order['status'] == 'pending') {
                                            echo 'bg-warning text-dark';
                                        } elseif ($order['status'] == 'processed') {
                                            echo 'bg-info'; 
                                        } elseif ($order['status'] == 'shipped') {
                                            echo 'bg-primary'; 
                                        } elseif ($order['status'] == 'completed') {
                                            echo 'bg-success';
                                        } elseif ($order['status'] == 'cancelled') {
                                            echo 'bg-danger';
                                        } else {
                                            echo 'bg-secondary'; // Fallback jika status tidak dikenal
                                        }
                                    ?>">
                                    <?php 
                                        // Tampilkan teks yang lebih user-friendly
                                        if ($order['status'] == 'pending') {
                                            echo 'Pending';
                                        } elseif ($order['status'] == 'processed') {
                                            echo 'Diproses';
                                        } elseif ($order['status'] == 'shipped') {
                                            echo 'Dikirim';
                                        } elseif ($order['status'] == 'completed') {
                                            echo 'Selesai';
                                        } elseif ($order['status'] == 'cancelled') {
                                            echo 'Dibatalkan';
                                        } else {
                                            echo 'Tidak Diketahui'; 
                                        }
                                    ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y H:i', strtotime($order['order_date'])); ?></td>
                            <td>
                                <?php 
                                    echo htmlspecialchars($order['address_line1'] . ', ' . $order['city'] . ', ' . $order['postal_code']); 
                                    // Menambahkan baris untuk nomor telepon dari alamat pengiriman (tabel addresses)
                                    if (!empty($order['shipping_phone_number'])) {
                                        echo '<br>Telp: ' . htmlspecialchars($order['shipping_phone_number']);
                                    }
                                ?>
                            </td>
                            <td>
                                <a href="order_detail.php?order_id=<?php echo htmlspecialchars($order['order_id']); ?>" class="btn btn-sm btn-info me-2"><i class="fas fa-eye"></i> Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../include/footer.php'; ?>