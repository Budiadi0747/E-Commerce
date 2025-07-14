<?php
// D:\xampp\htdocs\harvestly_2\seller\edit_product_seller.php

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../include/header.php'; // Atau header khusus penjual jika ada

// Pastikan user login dan role adalah 'penjual'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'penjual') {
    header('Location: ' . $base_url . 'login/login.php');
    exit();
}

$seller_id = $_SESSION['user_id'];
$product_id = $_GET['id'] ?? null; // Ambil ID produk dari URL untuk edit

$success_message = '';
$error_message = '';
$product = null; // Untuk menyimpan data produk yang akan diedit

// Ambil daftar kategori dari database
$categories = [];
try {
    $pdo = get_pdo_connection();
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan database saat mengambil kategori: " . $e->getMessage();
}

// 1. Jika ini adalah GET request, ambil data produk untuk mengisi form
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($product_id === null || !is_numeric($product_id)) {
        $error_message = "ID produk tidak valid.";
    } else {
        try {
            $pdo = get_pdo_connection();
            // Ambil data produk hanya jika itu milik penjual yang login
            $stmt = $pdo->prepare("SELECT * FROM produk WHERE id = ? AND seller_id = ?");
            $stmt->execute([$product_id, $seller_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                $error_message = "Produk tidak ditemukan atau Anda tidak memiliki izin untuk mengeditnya.";
            }
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan database: " . $e->getMessage();
        }
    }
}

// 2. Jika ini adalah POST request, proses pembaruan produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product_id !== null) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $category_id = $_POST['category_id'] ?? null;
    $current_image_url = $_POST['current_image_url'] ?? null; // Untuk menjaga gambar lama jika tidak ada upload baru

    $new_image_url = $current_image_url; // Default ke gambar yang sudah ada

    // Handle upload gambar baru
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('prod_') . '.' . $file_extension;
        $upload_dir_absolute = __DIR__ . '/../assets/img/produk/';

        if (!is_dir($upload_dir_absolute)) {
            mkdir($upload_dir_absolute, 0777, true);
        }

        $target_file_absolute = $upload_dir_absolute . $file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file_absolute)) {
            $new_image_url = 'assets/img/produk/' . $file_name;

            // Hapus gambar lama jika ada dan berbeda dari yang baru
            if ($current_image_url && $current_image_url !== $new_image_url) {
                $old_image_absolute_path = __DIR__ . '/../' . $current_image_url;
                if (file_exists($old_image_absolute_path) && !is_dir($old_image_absolute_path)) {
                    unlink($old_image_absolute_path);
                }
            }
        } else {
            $error_message = "Gagal mengupload gambar baru. Kode Error: " . $_FILES['image']['error'];
        }
    }

    // Validasi input
    if (empty($name) || empty($description) || $price <= 0 || $stock <= 0 || empty($category_id)) {
        $error_message = "Semua kolom harus diisi dan harga/stok harus lebih dari 0. Kategori harus dipilih.";
    } else if (!empty($error_message) && substr($error_message, 0, 16) === "Gagal mengupload") {
        // Jika error_message sudah diisi oleh kegagalan upload, biarkan saja
    }
    else {
        try {
            $pdo = get_pdo_connection();
            $stmt = $pdo->prepare("UPDATE produk SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, image_url = ? WHERE id = ? AND seller_id = ?");

            if ($stmt->execute([$name, $description, $price, $stock, $category_id, $new_image_url, $product_id, $seller_id])) {
                $success_message = "Produk berhasil diperbarui dan menunggu persetujuan admin kembali!";
                // Refresh data produk setelah update untuk ditampilkan di form
                $stmt = $pdo->prepare("SELECT * FROM produk WHERE id = ? AND seller_id = ?");
                $stmt->execute([$product_id, $seller_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error_message = "Gagal memperbarui produk.";
            }
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan database: " . $e->getMessage();
        }
    }
}

// Jika produk belum dimuat (misalnya saat ada error GET request), inisialisasi variabel untuk form
// Ini untuk menghindari "Undefined variable" jika $product null
$name_val = $product['name'] ?? '';
$description_val = $product['description'] ?? '';
$price_val = $product['price'] ?? 0;
$stock_val = $product['stock'] ?? 0;
$category_id_val = $product['category_id'] ?? null;
$image_url_val = $product['image_url'] ?? null;

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Edit Produk'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/main.css">
</head>
<body>
    <div class="container mt-5" style="padding-top: 20px;">
        <h2 class="mb-4">Edit Produk</h2>

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

        <?php if ($product): // Hanya tampilkan form jika produk ditemukan ?>
            <form action="edit_product_seller.php?id=<?php echo htmlspecialchars($product_id); ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="current_image_url" value="<?php echo htmlspecialchars($image_url_val); ?>">
                
                <div class="mb-3">
                    <label for="name" class="form-label">Nama Produk</label>
                    <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($name_val); ?>">
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Deskripsi Produk</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($description_val); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="price" class="form-label">Harga</label>
                    <input type="number" class="form-control" id="price" name="price" step="0.01" required value="<?php echo htmlspecialchars($price_val); ?>">
                </div>
                <div class="mb-3">
                    <label for="stock" class="form-label">Stok</label>
                    <input type="number" class="form-control" id="stock" name="stock" required value="<?php echo htmlspecialchars($stock_val); ?>">
                </div>

                <div class="mb-3">
                    <label for="category_id" class="form-label">Kategori Produk</label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="">Pilih Kategori</option>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>" 
                                    <?php echo ($category['id'] == $category_id_val) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Tidak ada kategori ditemukan.</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Gambar Produk (Biarkan kosong jika tidak ingin mengubah)</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/jpeg, image/png, image/gif">
                    <small class="form-text text-muted">Maksimal 5 MB. Format: JPG, JPEG, PNG, GIF.</small>
                    <?php if ($image_url_val): ?>
                        <div class="mt-2">
                            <p>Gambar saat ini:</p>
                            <img src="<?php echo $base_url . htmlspecialchars($image_url_val); ?>" alt="Gambar Produk" style="max-width: 150px; height: auto;">
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="seller_dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
            </form>
        <?php else: ?>
            <p>Tidak dapat memuat detail produk. Silakan kembali ke dashboard.</p>
            <a href="seller_dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>