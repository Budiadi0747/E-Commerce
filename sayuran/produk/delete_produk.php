<?php
// D:\xampp\htdocs\harvestly_2\produk\delete_produk.php <-- Lokasi file ini yang BENAR

session_start();

// Asumsikan $base_url akan didefinisikan dari file konfigurasi atau header
// Kita perlu ini untuk redirect yang benar
require_once __DIR__ . '/../config/db.php'; // Path ke db.php, juga tempat $base_url mungkin didefinisikan

// Periksa apakah user sudah login dan apakah dia admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message_temp'] = "Akses ditolak. Anda harus login sebagai admin.";
    // Redirect ke halaman login yang ada di folder 'login'
    header('Location: ' . (isset($base_url) ? $base_url : '/sayuran/') . 'login/login.php');
    exit();
}


// Pastikan request adalah POST dan ada ID produk
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id']) && is_numeric($_POST['id'])) {
    $product_id = $_POST['id'];

    // --- Perbaikan path direktori upload gambar ---
    // Dari produk/delete_produk.php, naik satu tingkat (../) ke harvestly_2/,
    // lalu masuk ke assets/img/produk/
    $upload_dir_absolute = __DIR__ . '/../assets/img/produk/';

    try {
        $pdo = get_pdo_connection();

        // Ambil nama file gambar sebelum menghapus data produk dari database
        $stmt = $pdo->prepare("SELECT image_url FROM produk WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $image_to_delete = $product['image_url'];

            // Hapus data produk dari database
            $stmt = $pdo->prepare("DELETE FROM produk WHERE id = ?");
            $stmt->execute([$product_id]);

            // Jika penghapusan dari database berhasil, hapus juga file gambar
            // Pastikan image_to_delete tidak kosong dan file ada sebelum dihapus
            if (!empty($image_to_delete)) {
                $image_path_full = $upload_dir_absolute . $image_to_delete;
                if (file_exists($image_path_full) && !is_dir($image_path_full)) { // Pastikan itu file, bukan direktori
                    unlink($image_path_full); // Hapus file gambar fisik
                }
            }

            $_SESSION['success_message'] = "Produk berhasil dihapus.";
        } else {
            $_SESSION['error_message_temp'] = "Produk tidak ditemukan.";
        }

    } catch (PDOException $e) {
        $_SESSION['error_message_temp'] = "Terjadi kesalahan database saat menghapus produk: " . $e->getMessage();
    }
} else {
    $_SESSION['error_message_temp'] = "Permintaan tidak valid untuk menghapus produk.";
}

// Selalu redirect kembali ke halaman admin_produk.php
// Path ke admin_produk.php: dari 'produk' naik satu tingkat (../) lalu ke 'admin'
header('Location: ' . (isset($base_url) ? $base_url : '/sayuran/') . 'admin/admin_produk.php');
exit(); // Pastikan selalu ada exit() setelah header('Location')
?>