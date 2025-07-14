<?php


session_start();
require_once __DIR__ . '/../config/db.php';

// Pastikan user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$page_title = "Detail Pesanan - Admin KangSayur";
include_once __DIR__ . '/admin_header.php';

$order_id = $_GET['order_id'] ?? null;
$order = null;
$order_items = [];
$error_message = '';
$success_message = '';

if (!$order_id) {
    $error_message = "ID Pesanan tidak ditemukan.";
} else {
    try {
        $pdo = get_pdo_connection();

        // Handle status update form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
            $new_status = trim($_POST['status'] ?? '');
            $allowed_statuses = ['pending', 'processed', 'shipped', 'completed', 'cancelled']; // Sesuai dengan ENUM di tabel orders Anda

            if (in_array($new_status, $allowed_statuses)) {
                $stmt_update = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                if ($stmt_update->execute([$new_status, $order_id])) {
                    $success_message = "Status pesanan berhasil diperbarui menjadi " . ucfirst($new_status) . ".";
                    // Refresh data pesanan setelah update
                    // Mengarahkan kembali ke halaman daftar pesanan admin
                    header('Location: admin_orders.php'); 
                    exit();
                } else {
                    $error_message = "Gagal memperbarui status pesanan.";
                }
            } else {
                $error_message = "Status tidak valid.";
            }
        }

        // Ambil detail pesanan
        $stmt = $pdo->prepare("SELECT o.id, u.nama AS customer_name, u.email AS customer_email, u.phone_number AS customer_phone,
                               o.total_amount AS total_price, o.status, o.order_date, o.notes,
                               o.shipping_address_id, o.billing_address_id
                               FROM orders o
                               JOIN users u ON o.user_id = u.id
                               WHERE o.id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $error_message = "Pesanan tidak ditemukan.";
        } else {
            // Ambil detail alamat pengiriman
            if (!empty($order['shipping_address_id'])) {
                $stmt_address = $pdo->prepare("SELECT address_line1, city, postal_code, phone_number FROM addresses WHERE id = ?");
                $stmt_address->execute([$order['shipping_address_id']]);
                $shipping_address = $stmt_address->fetch(PDO::FETCH_ASSOC);
                if ($shipping_address) {
                    $order['shipping_full_address'] = $shipping_address['address_line1'] . ', ' . $shipping_address['city'] . ', ' . $shipping_address['postal_code'];
                    $order['shipping_phone_number'] = $shipping_address['phone_number'];
                } else {
                    $order['shipping_full_address'] = 'Alamat tidak ditemukan';
                    $order['shipping_phone_number'] = 'Tidak tersedia';
                }
            } else {
                $order['shipping_full_address'] = 'Tidak ada alamat pengiriman';
                $order['shipping_phone_number'] = 'Tidak tersedia';
            }

            // Ambil item-item dalam pesanan
            $stmt_items = $pdo->prepare("SELECT oi.product_name, oi.quantity, oi.price_at_order AS item_price 
                                        FROM order_items oi
                                        WHERE oi.order_id = ?");
            $stmt_items->execute([$order_id]);
            $order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        $error_message = "Terjadi kesalahan database saat mengambil detail pesanan: " . $e->getMessage();
    }
}
?>

<div class="container mt-5">
    <h2 class="mb-4">Detail Pesanan #<?php echo htmlspecialchars($order_id); ?></h2>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($order): ?>
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        Informasi Pelanggan
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Nama:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></li>
                        <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></li>
                        <li class="list-group-item"><strong>Telepon:</strong> <?php echo htmlspecialchars($order['customer_phone'] ?? 'Tidak tersedia'); ?></li>
                        <li class="list-group-item"><strong>ID Alamat Pengiriman:</strong> <?php echo htmlspecialchars($order['shipping_address_id']); ?></li>
                        <li class="list-group-item"><strong>Catatan:</strong> <?php echo htmlspecialchars($order['notes'] ?? 'Tidak ada catatan.'); ?></li>
                    </ul>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        Alamat Pengiriman
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><?php echo htmlspecialchars($order['shipping_full_address'] ?? 'Tidak tersedia'); ?></li>
                        <li class="list-group-item">Telp: <?php echo htmlspecialchars($order['shipping_phone_number'] ?? 'Tidak tersedia'); ?></li>
                    </ul>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        Detail Pesanan
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>ID Pesanan:</strong> #<?php echo htmlspecialchars($order['id']); ?></li>
                        <li class="list-group-item"><strong>Total Harga:</strong> Rp <?php echo number_format($order['total_price'], 0, ',', '.'); ?></li>
                        <li class="list-group-item">
                            <strong>Status:</strong>
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
                                        echo 'bg-secondary'; 
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
                        </li>
                        <li class="list-group-item"><strong>Tanggal Pesan:</strong> <?php echo date('d M Y H:i', strtotime($order['order_date'])); ?></li>
                    </ul>
                </div>

                <div class="card">
                    <div class="card-header">
                        Ubah Status Pesanan
                    </div>
                    <div class="card-body">
                        <form action="order_detail.php?order_id=<?php echo htmlspecialchars($order['id']); ?>" method="POST">
                            <div class="mb-3">
                                <label for="status" class="form-label">Pilih Status Baru:</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processed" <?php echo ($order['status'] == 'processed') ? 'selected' : ''; ?>>Diproses</option>
                                    <option value="shipped" <?php echo ($order['status'] == 'shipped') ? 'selected' : ''; ?>>Dikirim</option>
                                    <option value="completed" <?php echo ($order['status'] == 'completed') ? 'selected' : ''; ?>>Selesai</option>
                                    <option value="cancelled" <?php echo ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>Dibatalkan</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-primary">Perbarui Status</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mt-5 mb-3">Item Pesanan</h3>
        <?php if (empty($order_items)): ?>
            <div class="alert alert-info" role="alert">
                Tidak ada item dalam pesanan ini.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Kuantitas</th>
                            <th>Harga Satuan</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_total = 0;
                        foreach ($order_items as $item): 
                            $subtotal = $item['quantity'] * $item['item_price'];
                            $grand_total += $subtotal;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td>Rp <?php echo number_format($item['item_price'], 0, ',', '.'); ?></td>
                                <td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total Pesanan:</strong></td>
                            <td><strong>Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="admin_orders.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Pesanan</a>
        </div>

    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../include/footer.php'; ?>