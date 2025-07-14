<?php
// D:\xampp\htdocs\harvestly_2\seller\add_product_seller.php

session_start();
// Pastikan path ini benar relatif terhadap add_product_seller.php
// __DIR__ adalah direktori saat ini (D:\xampp\htdocs\harvestly_2\seller)
require_once __DIR__ . '/../config/db.php';
// Gunakan base_url dari header untuk link relatif yang benar
require_once __DIR__ . '/../include/header.php'; // Header umum untuk penjual, sesuaikan jika ada header spesifik seller

// Pastikan user login dan role adalah 'penjual'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'penjual') {
    // Arahkan ke halaman login di root aplikasi
    header('Location: ' . $base_url . 'login/login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Ambil daftar kategori dari database
$categories = [];
try {
    $pdo = get_pdo_connection();
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan database saat mengambil kategori: " . $e->getMessage();
}

// Inisialisasi variabel untuk form agar tidak error saat pertama kali load
$name = '';
$description = '';
$price = 0;
$stock = 0;
$category_id = null;

// Tangani proses penambahan produk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $category_id = $_POST['category_id'] ?? null;
    $seller_id = $_SESSION['user_id'];

    // Handle gambar
    $image_url_db = null; // Ini akan menjadi path yang disimpan di database
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Mendapatkan ekstensi file asli
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        // Membuat nama file unik
        $file_name = uniqid('prod_') . '.' . $file_extension;

        // --- PENTING: PERBAIKAN PATH TUJUAN UPLOAD ---
        // __DIR__ adalah D:\xampp\htdocs\harvestly_2\seller\
        // '/../' naik satu tingkat menjadi D:\xampp\htdocs\harvestly_2\
        // '/assets/img/produk/' masuk ke folder tujuan
        $upload_dir_absolute = __DIR__ . '/../assets/img/produk/';

        // Pastikan direktori tujuan ada, jika tidak, buatlah
        if (!is_dir($upload_dir_absolute)) {
            // mode 0777 untuk pengembangan, ubah ke 0755 di produksi
            mkdir($upload_dir_absolute, 0777, true);
        }

        $target_file_absolute = $upload_dir_absolute . $file_name; // Path absolut untuk move_uploaded_file

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file_absolute)) {
            // Ini adalah path yang akan disimpan di database (relatif dari root Harvestly_2)
            $image_url_db = 'assets/img/produk/' . $file_name;
        } else {
            $error_message = "Gagal mengupload gambar. Kode Error: " . $_FILES['image']['error'];
            // Debugging tambahan jika masih gagal (hapus di produksi)
            // $error_info = error_get_last();
            // $error_message .= " - Info: " . ($error_info['message'] ?? 'N/A');
        }
    }

    // Validasi input
    if (empty($name) || empty($description) || $price <= 0 || $stock <= 0 || empty($category_id)) {
        $error_message = "Semua kolom harus diisi dan harga/stok harus lebih dari 0. Kategori harus dipilih.";
    } else if (!empty($error_message) && substr($error_message, 0, 16) === "Gagal mengupload") {
        // Jika error_message sudah diisi oleh kegagalan upload, jangan timpa dengan error validasi input lainnya
        // atau tangani secara spesifik jika validasi lain juga gagal
    }
    else {
        try {
            $pdo = get_pdo_connection();
            $stmt = $pdo->prepare("INSERT INTO produk (name, description, price, stock, category_id, seller_id, image_url, status_persetujuan, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");

            if ($stmt->execute([$name, $description, $price, $stock, $category_id, $seller_id, $image_url_db])) {
                $success_message = "Produk berhasil ditambahkan dan menunggu persetujuan admin!";
                // Reset form fields setelah berhasil
                $name = $description = '';
                $price = $stock = 0;
                $category_id = null;
                // $image_url_db = null; // Tidak perlu direset karena ini variabel temporer
            } else {
                $error_message = "Gagal menambahkan produk ke database.";
            }
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan database: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Tambah Produk'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/main.css">
    </head>
<body>
    <?php // Header sudah di-include di bagian atas ?>
    <div class="container mt-5" style="padding-top: 20px;"> <h2 class="mb-4">Tambah Produk Baru</h2>

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

        <form action="add_product_seller.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Nama Produk</label>
                <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($name); ?>">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi Produk</label>
                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Harga</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" required value="<?php echo htmlspecialchars($price); ?>">
            </div>
            <div class="mb-3">
                <label for="stock" class="form-label">Stok</label>
                <input type="number" class="form-control" id="stock" name="stock" required value="<?php echo htmlspecialchars($stock); ?>">
            </div>

            <div class="mb-3">
                <label for="category_id" class="form-label">Kategori Produk</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">Pilih Kategori</option>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo ($category['id'] == $category_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Tidak ada kategori ditemukan. Silakan tambahkan kategori terlebih dahulu.</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">Gambar Produk (Opsional)</label>
                <input type="file" class="form-control" id="image" name="image" accept="image/jpeg, image/png, image/gif">
                <small class="form-text text-muted">Maksimal 5 MB. Format: JPG, JPEG, PNG, GIF.</small>
            </div>

            <button type="submit" class="btn btn-primary">Tambah Produk</button>
            <a href="seller_dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>