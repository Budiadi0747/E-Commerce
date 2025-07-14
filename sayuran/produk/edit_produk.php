<?php
// D:\xampp\htdocs\harvestly_2\produk\edit_produk.php <-- Lokasi file ini yang BENAR

session_start();

// Periksa apakah user sudah login dan apakah dia admin
// Jika tidak, redirect ke halaman login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Memastikan $base_url didefinisikan agar redirect benar
    // Ini biasanya didefinisikan di config.php atau header.php
    // Kita akan include db.php dulu karena seringkali config ada di sana
    require_once __DIR__ . '/../config/db.php'; // Path ke db.php
    header('Location: ' . (isset($base_url) ? $base_url : '/sayuran/') . 'login/login.php');
    exit();
}

// Path ke db.php: dari 'produk' naik satu tingkat (../) lalu ke 'config'
require_once __DIR__ . '/../config/db.php';

$product = null;
$error_message = '';
$success_message = '';

// Ambil ID produk dari URL
$product_id = $_GET['id'] ?? null;

// Pastikan ada ID produk di URL dan valid
if ($product_id === null || !is_numeric($product_id)) {
    $_SESSION['error_message_temp'] = "ID produk tidak valid atau tidak ditemukan.";
    // Redirect ke admin_produk.php yang ada di folder 'admin'
    // Dari 'produk', naik satu tingkat (../) lalu masuk ke 'admin'
    header('Location: ' . (isset($base_url) ? $base_url : '/sayuran/') . 'admin/admin_produk.php');
    exit();
}

try {
    $pdo = get_pdo_connection();

    // Ambil data produk berdasarkan ID
    $stmt = $pdo->prepare("SELECT id, name, description, price, stock, image_url FROM produk WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['error_message_temp'] = "Produk tidak ditemukan.";
        // Redirect ke admin_produk.php yang ada di folder 'admin'
        header('Location: ' . (isset($base_url) ? $base_url : '/sayuran/') . 'admin/admin_produk.php');
        exit();
    }

} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan database saat mengambil data produk: " . $e->getMessage();
}

// Proses Update Produk jika Form Disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $current_image_url_filename = $product['image_url'] ?? ''; // Ambil nama file gambar saat ini dari DB
    
    $image_url_to_save_in_db = $current_image_url_filename; // Default: gunakan nama file gambar lama

    // Validasi input
    if (empty($name) || empty($description) || empty($price) || empty($stock)) {
        $error_message = "Semua field nama, deskripsi, harga, dan stok harus diisi.";
    } elseif (!is_numeric($price) || $price <= 0) {
        $error_message = "Harga harus angka positif.";
    } elseif (!is_numeric($stock) || $stock < 0) {
        $error_message = "Stok harus angka non-negatif.";
    } else {
        // --- Perbaikan path upload gambar ---
        // Dari produk/edit_produk.php, naik dua tingkat (../../) untuk mencapai root (harvestly_2),
        // lalu masuk ke assets/img/produk/
        // ATAU, karena 'produk' adalah subfolder dari 'img', cukup naik satu tingkat dari 'produk'
        // dan masuk ke 'img/produk/'
        // Mengingat struktur Anda: D:\xampp\htdocs\harvestly_2\assets\img\produk\
        // Dan file ini di D:\xampp\htdocs\harvestly_2\produk\
        // Maka path upload harus: D:\xampp\htdocs\harvestly_2\assets\img\produk\
        // Ini berarti dari `__DIR__` (yaitu /produk/), kita perlu naik ke /harvestly_2/, lalu ke /assets/img/produk/
        $upload_dir_absolute = __DIR__ . '/../assets/img/produk/';


        // Pastikan direktori upload ada
        if (!is_dir($upload_dir_absolute)) {
            mkdir($upload_dir_absolute, 0777, true); // Buat direktori dengan izin rekursif
        }

        // Penanganan upload gambar baru
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['image']['tmp_name'];
            $original_file_name = basename($_FILES['image']['name']);
            $file_ext = strtolower(pathinfo($original_file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp']; // Tambahkan webp jika digunakan

            if (in_array($file_ext, $allowed_ext)) {
                // Buat nama file unik untuk gambar baru
                $new_file_name = uniqid('produk_', true) . '.' . $file_ext;
                $target_upload_path_absolute = $upload_dir_absolute . $new_file_name;

                if (move_uploaded_file($file_tmp_name, $target_upload_path_absolute)) {
                    $image_url_to_save_in_db = $new_file_name; // Simpan hanya nama filenya di DB

                    // Hapus gambar lama jika ada dan berbeda dengan yang baru
                    if (!empty($current_image_url_filename) && $current_image_url_filename !== $new_file_name) {
                        $old_image_path_absolute = $upload_dir_absolute . $current_image_url_filename;
                        if (file_exists($old_image_path_absolute) && !is_dir($old_image_path_absolute)) { // Pastikan itu file, bukan direktori
                            unlink($old_image_path_absolute); // Hapus file gambar lama
                        }
                    }
                } else {
                    $error_message = "Gagal mengunggah gambar baru. Kode Error: " . $_FILES['image']['error'];
                }
            } else {
                $error_message = "Jenis file gambar tidak diizinkan. Hanya JPG, JPEG, PNG, GIF, WEBP.";
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
            // Ini menangani error upload selain 'tidak ada file yang diupload'
            $error_message = "Terjadi kesalahan saat upload gambar: " . $_FILES['image']['error'];
        }

        // Lanjutkan hanya jika tidak ada error sebelumnya
        if (empty($error_message)) {
            try {
                $pdo = get_pdo_connection();
                $stmt = $pdo->prepare("UPDATE produk SET name = ?, description = ?, price = ?, stock = ?, image_url = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $stock, $image_url_to_save_in_db, $product_id]);

                // Update objek produk yang ditampilkan di form dengan data terbaru
                $product['name'] = $name;
                $product['description'] = $description;
                $product['price'] = $price;
                $product['stock'] = $stock;
                $product['image_url'] = $image_url_to_save_in_db; // Update image_url juga

                $success_message = "Produk '" . htmlspecialchars($name) . "' berhasil diperbarui.";
                
                // Redirect ke admin_produk.php setelah sukses dengan pesan
                $_SESSION['success_message'] = $success_message;
                // Path ke admin_produk.php: dari 'produk' naik satu tingkat (../) lalu ke 'admin'
                header('Location: ' . (isset($base_url) ? $base_url : '/sayuran/') . 'admin/admin_produk.php');
                exit();
            } catch (PDOException $e) {
                $error_message = "Terjadi kesalahan database: " . $e->getMessage();
            }
        }
    }
}

// --- Header dan HTML ---
$page_title = "Edit Produk - KangSayur Admin";
// Path ke header.php: dari 'produk' naik satu tingkat (../) lalu ke 'include'
include_once __DIR__ . '/../include/header.php';
// $base_url seharusnya didefinisikan di header.php atau file config yang di-include
?>

<div class="container mt-5" style="padding-top: 20px;">
    <div class="register-container">
        <h2 class="text-center mb-4">Edit Produk</h2>

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

        <?php if ($product): // Pastikan produk ditemukan sebelum menampilkan form ?>
            <form action="edit_produk.php?id=<?php echo htmlspecialchars($product_id); ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="current_image_url" value="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>">

                <div class="mb-3">
                    <label for="name" class="form-label">Nama Produk</label>
                    <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Deskripsi Produk</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="price" class="form-label">Harga per Kg (Rp)</label>
                    <input type="number" class="form-control" id="price" name="price" step="0.01" required value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="stock" class="form-label">Stok (Kg)</label>
                    <input type="number" class="form-control" id="stock" name="stock" required value="<?php echo htmlspecialchars($product['stock'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Gambar Produk Saat Ini</label><br>
                    <?php if (!empty($product['image_url'])): ?>
                        <img src="<?php echo (isset($base_url) ? $base_url : '/sayuran/'); ?>assets/img/produk/<?php echo htmlspecialchars($product['image_url']); ?>" alt="Gambar Produk" style="max-width: 150px; height: auto; display: block; margin-bottom: 10px;">
                    <?php else: ?>
                        <p>Tidak ada gambar saat ini.</p>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah gambar.</small>
                </div>
                <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
                <a href="<?php echo (isset($base_url) ? $base_url : '/sayuran/'); ?>admin/admin_produk.php" class="btn btn-secondary w-100 mt-2">Batal</a>
            </form>
        <?php else: ?>
            <div class="alert alert-warning text-center" role="alert">
                Produk tidak ditemukan atau ada masalah saat memuat data.
                <a href="<?php echo (isset($base_url) ? $base_url : '/sayuran/'); ?>admin/admin_produk.php" class="btn btn-primary mt-3">Kembali ke Daftar Produk</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Path ke footer.php: dari 'produk' naik satu tingkat (../) lalu ke 'include'
include_once __DIR__ . '/../include/footer.php';
?>