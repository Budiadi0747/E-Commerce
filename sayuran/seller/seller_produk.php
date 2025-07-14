<?php
// D:\xampp\htdocs\harvestly_2\seller_produk.php

session_start();
require_once __DIR__ . '/../config/db.php';

// Pastikan user adalah penjual
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'penjual') {
    header('Location: login.php');
    exit();
}

$page_title = "Daftar Produk Anda - KangSayur";
include_once __DIR__ . '/../include/header.php'; // Menggunakan header umum

$seller_id = $_SESSION['user_id'];
$products = [];
$error_message = '';
$filter_status = $_GET['status'] ?? ''; // Ambil parameter 'status' dari URL

// Judul halaman berdasarkan filter
$display_title = "Daftar Semua Produk Anda";
if ($filter_status === 'pending') {
    $display_title = "Daftar Produk Menunggu Persetujuan";
} elseif ($filter_status === 'approved') {
    $display_title = "Daftar Produk Disetujui";
} elseif ($filter_status === 'rejected') { // Tambahkan filter untuk produk ditolak
    $display_title = "Daftar Produk Ditolak";
}

try {
    $pdo = get_pdo_connection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Bangun query SQL berdasarkan filter status
    $sql = "SELECT id, name, description, price, stock, status_persetujuan FROM produk WHERE seller_id = ?";
    $params = [$seller_id];

    if ($filter_status === 'pending') {
        $sql .= " AND status_persetujuan = 'pending'";
    } elseif ($filter_status === 'approved') {
        $sql .= " AND status_persetujuan = 'approved'";
    } elseif ($filter_status === 'rejected') {
        $sql .= " AND status_persetujuan = 'rejected'";
    }
    
    $sql .= " ORDER BY created_at DESC"; // Tambahkan order by untuk konsistensi

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan database: " . $e->getMessage();
}
?>

<div class="container mt-5">
    <h2 class="mb-4"><?php echo htmlspecialchars($display_title); ?></h2>

    <?php 
    // Tangani pesan status dari redirect (setelah add, edit, atau delete produk)
    if (isset($_GET['status_msg']) && isset($_GET['message'])) { 
        $status_type = $_GET['status_msg'] == 'success' ? 'alert-success' : 'alert-danger';
        echo '<div class="alert ' . $status_type . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($_GET['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="mb-3">
        <a href="add_product_seller.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Produk Baru</a>
        <a href="seller_dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
        <?php if (!empty($filter_status)): ?>
            <a href="seller_produk.php" class="btn btn-info">Tampilkan Semua Produk</a>
        <?php endif; ?>
    </div>

    <?php if (empty($products)): ?>
        <div class="alert alert-info text-center" role="alert">
            Tidak ada produk yang ditemukan dengan kriteria ini.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Status Persetujuan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['id']); ?></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td> 
                            <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td> 
                            <td><?php echo htmlspecialchars($product['stock']); ?></td> 
                            <td>
                                <span class="badge 
                                    <?php 
                                        if ($product['status_persetujuan'] == 'pending') echo 'bg-warning text-dark';
                                        elseif ($product['status_persetujuan'] == 'approved') echo 'bg-success';
                                        elseif ($product['status_persetujuan'] == 'rejected') echo 'bg-danger';
                                        else echo 'bg-secondary';
                                    ?>">
                                    <?php echo htmlspecialchars(ucfirst($product['status_persetujuan'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit_product_seller.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-sm btn-warning me-2 
                                    <?php echo ($product['status_persetujuan'] == 'approved' || $product['status_persetujuan'] == 'rejected') ? 'disabled' : ''; ?>">Edit</a>
                                <a href="delete_product_seller.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-sm btn-danger 
                                    <?php echo ($product['status_persetujuan'] == 'approved' || $product['status_persetujuan'] == 'rejected') ? 'disabled' : ''; ?>" 
                                    onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../include/footer.php'; ?>