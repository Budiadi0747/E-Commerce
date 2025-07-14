<?php
session_start();
require_once '../config/db.php'; // sesuaikan path-nya

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error mengambil data pesanan: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Riwayat Pesanan</title>
</head>
<body>
    <h2>Riwayat Pesanan Saya</h2>

    <?php if (count($orders) === 0): ?>
        <p>Belum ada pesanan yang dilakukan.</p>
    <?php else: ?>
        <table border="1" cellpadding="8">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal Pesanan</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Metode Pembayaran</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $index => $order): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= $order['order_date'] ?></td>
                    <td><?= ucfirst($order['status']) ?></td>
                    <td>Rp<?= number_format($order['total_amount'], 0, ',', '.') ?></td>
                    <td><?= $order['payment_method'] ?></td>
                    <td><a href="detail_pesanan.php?id=<?= $order['id'] ?>">Lihat</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
