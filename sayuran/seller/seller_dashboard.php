<?php
// D:\xampp\htdocs\harvestly_2\seller\seller_dashboard.php

session_start();
// PERBAIKAN: Path ke db.php
// Dari 'seller/' naik satu level ke 'harvestly_2/', lalu masuk ke 'config/'
require_once __DIR__ . '/../config/db.php';

// Pastikan user adalah penjual
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'penjual') {
    // PERBAIKAN: Path ke login.php. Dari 'seller/' naik satu level, lalu masuk 'login/'
    header('Location: ../login/login.php');
    exit();
}

$page_title = "Dashboard Penjual - KangSayur";
// PERBAIKAN: Path ke header.php
// Dari 'seller/' naik satu level ke 'harvestly_2/', lalu masuk ke 'include/'
include_once __DIR__ . '/../include/header.php'; // Menggunakan header umum

$seller_id = $_SESSION['user_id'];
$seller_username = $_SESSION['username'];

$products = [];
$total_products = 0;
$pending_products = 0;
$error_message = '';

try {
    $pdo = get_pdo_connection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ambil semua produk yang dimiliki oleh penjual ini
    $stmt = $pdo->prepare("SELECT id, name, description, price, stock, status_persetujuan FROM produk WHERE seller_id = ? ORDER BY created_at DESC");
    $stmt->execute([$seller_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Hitung total produk
    $total_products = count($products);

    // Hitung produk menunggu persetujuan
    $stmt_pending_count = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE seller_id = ? AND status_persetujuan = 'pending'");
    $stmt_pending_count->execute([$seller_id]);
    $pending_products = $stmt_pending_count->fetchColumn();

} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan database: " . $e->getMessage();
}
?>

<div class="container mt-5">
    <h2 class="mb-4">Selamat Datang, Penjual <?php echo htmlspecialchars($seller_username); ?>!</h2>
    <p>Ini adalah dashboard Anda. Di sini Anda dapat mengelola produk-produk yang Anda jual.</p>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Produk Anda</h5>
                    <p class="card-text fs-1"><?php echo htmlspecialchars($total_products); ?></p>
                    <a href="seller_produk.php" class="btn btn-light">Lihat Produk</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Produk Menunggu Persetujuan</h5>
                    <p class="card-text fs-1"><?php echo htmlspecialchars($pending_products); ?></p>
                    <a href="seller_produk.php?status=pending" class="btn btn-light">Lihat Detail</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Produk Disetujui</h5>
                    <?php
                    $approved_count = 0;
                    foreach ($products as $p) {
                        if ($p['status_persetujuan'] == 'approved') {
                            $approved_count++;
                        }
                    }
                    ?>
                    <p class="card-text fs-1"><?php echo htmlspecialchars($approved_count); ?></p>
                    <a href="seller_produk.php?status=approved" class="btn btn-light">Lihat Detail</a>
                </div>
            </div>
        </div>
    </div>

    <hr>

    <h3 class="mb-4" id="daftar-produk">Produk Anda</h3>

    <?php 
    // Tangani pesan status dari redirect (setelah add, edit, atau delete produk)
    // Pastikan parameter yang dikirim dari halaman lain juga menggunakan 'status_msg' dan 'message'
    if (isset($_GET['status_msg']) && isset($_GET['message'])) {
        $status_type = $_GET['status_msg'] == 'success' ? 'alert-success' : 'alert-danger';
        echo '<div class="alert ' . $status_type . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($_GET['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    ?>

    <div class="mb-3">
        <a href="add_product_seller.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Produk Baru</a>
    </div>

    <?php if (empty($products)): ?>
        <div class="alert alert-info text-center" role="alert">
            Anda belum menambahkan produk apapun.
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
                    <?php 
                    // Jika Anda ingin membatasi jumlah produk yang ditampilkan di dashboard,
                    // gunakan array_slice, misalnya: foreach (array_slice($products, 0, 5) as $product):
                    foreach ($products as $product): ?>
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

<?php 
// PERBAIKAN: Path ke footer.php
// Dari 'seller/' naik satu level ke 'harvestly_2/', lalu masuk ke 'include/'
include_once __DIR__ . '/../include/footer.php'; 
?>