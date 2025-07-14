<?php
// D:\xampp\htdocs\harvestly_2\produk\tambah_produk.php

session_start();

// Periksa apakah user sudah login dan apakah dia admin
// Ini adalah validasi dasar. Anda bisa memperketatnya.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // PERBAIKAN DI SINI: Arahkan ke halaman login yang benar
    header('Location: ../login/login.php'); 
    exit();
}

// PERBAIKAN DI SINI: Path ke db.php
require_once __DIR__ . '/../config/db.php';

$success_message = '';
$error_message = '';
$name = '';
$description = '';
$price = '';
$stock = '';
// $category_id = ''; // Jika Anda memiliki tabel category_id dan ingin menambahkannya di form

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    // $category_id = trim($_POST['category_id'] ?? '');

    // Validasi input
    if (empty($name) || empty($description) || empty($price) || empty($stock)) {
        $error_message = "Semua field nama, deskripsi, harga, dan stok harus diisi.";
    } elseif (!is_numeric($price) || $price <= 0) {
        $error_message = "Harga harus angka positif.";
    } elseif (!is_numeric($stock) || $stock < 0) {
        $error_message = "Stok harus angka non-negatif.";
    } else {
        // PERBAIKAN DI SINI: Lokasi penyimpanan gambar
        $upload_dir = __DIR__ . '/../assets/img/produk/'; 
        $image_url = null; // Default null jika tidak ada gambar diupload

        // Penanganan upload gambar
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['image']['tmp_name'];
            $file_name = basename($_FILES['image']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_ext, $allowed_ext)) {
                // Buat nama file unik untuk menghindari tabrakan
                $new_file_name = uniqid('produk_', true) . '.' . $file_ext;
                $upload_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp_name, $upload_path)) {
                    // Simpan path relatif dari root Harvestly_2 ke database
                    $image_url = 'assets/img/produk/' . $new_file_name; 
                } else {
                    $error_message = "Gagal mengunggah gambar.";
                }
            } else {
                $error_message = "Jenis file gambar tidak diizinkan. Hanya JPG, JPEG, PNG, GIF.";
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
             // Tangani error upload lainnya (misalnya ukuran terlalu besar)
             $error_message = "Terjadi kesalahan saat upload gambar: " . $_FILES['image']['error'];
        }

        if (empty($error_message)) { // Lanjutkan hanya jika tidak ada error sebelumnya
            try {
                $pdo = get_pdo_connection();
                // Menambahkan seller_id dan status_persetujuan sebagai 'pending' secara default
                $stmt = $pdo->prepare("INSERT INTO produk (name, description, price, stock, image_url, seller_id, status_persetujuan) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                
                // Pastikan seller_id diambil dari sesi admin yang sedang login
                $seller_id = $_SESSION['user_id']; 

                $stmt->execute([$name, $description, $price, $stock, $image_url, $seller_id]);

                $success_message = "Produk '" . htmlspecialchars($name) . "' berhasil ditambahkan dan menunggu persetujuan admin.";
                // Reset form setelah berhasil
                $name = '';
                $description = '';
                $price = '';
                $stock = '';
                // $category_id = '';
            } catch (PDOException $e) {
                $error_message = "Terjadi kesalahan database: " . $e->getMessage();
                // Di lingkungan produksi, log error ini, jangan tampilkan ke user
            }
        }
    }
}

$page_title = "Tambah Produk Baru - KangSayur";
// PERBAIKAN DI SINI: Path ke header.php
include_once __DIR__ . '/../include/header.php';
?>

<div class="container mt-5">
    <div class="register-container"> <h2 class="text-center mb-4">Tambah Produk Baru</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <form action="tambah_produk.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Nama Produk</label>
                <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($name); ?>">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi Produk</label>
                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Harga per Kg (Rp)</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" required value="<?php echo htmlspecialchars($price); ?>">
            </div>
            <div class="mb-3">
                <label for="stock" class="form-label">Stok (Kg)</label>
                <input type="number" class="form-control" id="stock" name="stock" required value="<?php echo htmlspecialchars($stock); ?>">
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Gambar Produk</label>
                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                <small class="form-text text-muted">Format: JPG, JPEG, PNG, GIF</small>
            </div>
            <button type="submit" class="btn btn-success w-100">Tambah Produk</button>
        </form>
    </div>
</div>

<?php
// PERBAIKAN DI SINI: Path ke footer.php
include_once __DIR__ . '/../include/footer.php';
?>