<?php
// pembeli/invoice.php
require_once __DIR__ . '/../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pembeli') {
    die("Akses ditolak.");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID pesanan tidak valid.");
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    $pdo = get_pdo_connection();

    // Ambil data pesanan
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("Pesanan tidak ditemukan.");
    }

    // Ambil item pesanan + join nama produk dari tabel produk
    $stmt_items = $pdo->prepare("
        SELECT oi.*, p.name AS product_name
        FROM order_items oi
        JOIN produk p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt_items->execute([$order_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Kesalahan database: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Invoice #<?php echo $order['id']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h2 { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .text-right { text-align: right; }
        .print-btn { margin-top: 20px; }
    </style>
</head>
<body>
    <h2>Invoice #<?php echo $order['id']; ?></h2>
    <p>Tanggal: <?php echo date('d-m-Y H:i', strtotime($order['order_date'])); ?></p>
    <p>Status: <?php echo ucfirst($order['status']); ?></p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Produk</th>
                <th>Harga</th>
                <th>Jumlah</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1; 
            $total = 0;
            foreach ($items as $item): 
                $subtotal = $item['price_at_order'] * $item['quantity'];
                $total += $subtotal;
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td>Rp <?php echo number_format($item['price_at_order'], 0, ',', '.'); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <th colspan="4" class="text-right">Total</th>
                <th>Rp <?php echo number_format($total, 0, ',', '.'); ?></th>
            </tr>
        </tbody>
    </table>

    <div class="print-btn">
        <button onclick="window.print()">🖨 Cetak Invoice</button>
    </div>
</body>
</html>
