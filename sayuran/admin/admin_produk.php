<?php

// (Nama file dan lokasi sudah diperbarui sesuai konfirmasi Anda)

session_start();

// Periksa apakah user sudah login dan apakah dia admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // PERBAIKAN: Arahkan kembali ke login/login.php dari admin/
    header('Location: ../login/login.php');
    exit();
}

// PERBAIKAN: Sertakan file koneksi database
// Dari 'admin/' untuk ke 'config/', perlu naik satu level (..) lalu masuk ke 'config/'.
require_once __DIR__ . '/../config/db.php';

$products = [];
$error_message = '';

try {
    $pdo = get_pdo_connection();
    // Ambil semua data produk untuk admin, diurutkan berdasarkan nama
    $stmt = $pdo->query("SELECT id, name, description, price, stock, image_url, created_at FROM produk ORDER BY name ASC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan saat mengambil data produk: " . $e->getMessage();
}

$page_title = "Manajemen Produk - KangSayur Admin";

// PERBAIKAN: Sertakan header.php
// Dari 'admin/' untuk ke 'include/', perlu naik satu level (..) lalu masuk ke 'include/'.
include_once __DIR__ . '/../include/header.php';
?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Manajemen Produk (Admin)</h2>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
        </div>
        <?php unset($_SESSION['success_message']); // Hapus pesan setelah ditampilkan ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message_temp'])): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($_SESSION['error_message_temp']); ?>
        </div>
        <?php unset($_SESSION['error_message_temp']); // Hapus pesan setelah ditampilkan ?>
    <?php endif; ?>

    <div class="d-flex justify-content-end mb-3">
        <a href="../produk/tambah_produk.php" class="btn btn-success"><i class="fas fa-plus"></i> Tambah Produk Baru</a> </div>

    <?php if (empty($products)): ?>
        <div class="alert alert-info text-center" role="alert">
            Belum ada produk yang terdaftar. Silakan <a href="../produk/tambah_produk.php">tambahkan produk baru</a>. </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Gambar</th>
                        <th>Nama</th>
                        <th>Deskripsi</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Ditambahkan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['id']); ?></td>
                            <td>
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="/sayuran/<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 80px; height: 80px; object-fit: cover;">
                                <?php else: ?>
                                    <img src="/sayuran/assets/img/no_image.png" alt="Tidak ada gambar" style="width: 80px; height: 80px; object-fit: cover;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['description']); ?></td>
                            <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($product['stock']); ?> kg</td>
                            <td><?php echo htmlspecialchars(date('d M Y H:i', strtotime($product['created_at']))); ?></td>
                            <td>
                                <a href="../produk/edit_produk.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-sm btn-warning mb-1 me-1"><i class="fas fa-edit"></i> Edit</a> <form action="../produk/delete_produk.php" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');"> <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                    <button type="submit" class="btn btn-sm btn-danger mb-1"><i class="fas fa-trash-alt"></i> Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
// PERBAIKAN: Sertakan footer.php
// Dari 'admin/' untuk ke 'include/', perlu naik satu level (..) lalu masuk ke 'include/'.
include_once __DIR__ . '/../include/footer.php';
?>