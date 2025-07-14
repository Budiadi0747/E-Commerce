<?php
// D:\xampp\htdocs\harvestly_2\seller\delete_product_seller.php

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../include/header.php'; // Atau header khusus penjual jika ada

// Pastikan user login dan role adalah 'penjual'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'penjual') {
    header('Location: ' . $base_url . 'login/login.php');
    exit();
}

$seller_id = $_SESSION['user_id'];
$product_id = $_GET['id'] ?? null; // Ambil ID produk dari URL

$error_message = '';
$success_message = '';

if ($product_id === null || !is_numeric($product_id)) {
    $error_message = "ID produk tidak valid.";
} else {
    try {
        $pdo = get_pdo_connection();

        // 1. Ambil nama file gambar sebelum menghapus data produk dari database
        //    Ini penting agar kita bisa menghapus file gambar fisik
        $stmt_get_image = $pdo->prepare("SELECT image_url FROM produk WHERE id = ? AND seller_id = ?");
        $stmt_get_image->execute([$product_id, $seller_id]);
        $product_data = $stmt_get_image->fetch(PDO::FETCH_ASSOC);

        if ($product_data) {
            $image_to_delete = $product_data['image_url'];

            // 2. Hapus produk dari database
            $stmt_delete = $pdo->prepare("DELETE FROM produk WHERE id = ? AND seller_id = ?");
            if ($stmt_delete->execute([$product_id, $seller_id])) {
                $success_message = "Produk berhasil dihapus.";

                // 3. Hapus file gambar fisik jika ada dan valid
                if ($image_to_delete) {
                    // Bangun path absolut ke file gambar
                    $absolute_image_path = __DIR__ . '/../' . $image_to_delete;
                    
                    // Pastikan file tersebut ada dan merupakan file yang valid sebelum dihapus
                    // Hindari menghapus direktori atau file sistem penting
                    if (file_exists($absolute_image_path) && !is_dir($absolute_image_path)) {
                        unlink($absolute_image_path); // Hapus file
                        $success_message .= " Gambar produk juga dihapus.";
                    } else {
                        $error_message = "File gambar tidak ditemukan atau bukan file yang valid: " . $absolute_image_path;
                    }
                }
            } else {
                $error_message = "Gagal menghapus produk. Mungkin produk tidak ditemukan atau Anda tidak memiliki izin.";
            }
        } else {
            $error_message = "Produk tidak ditemukan atau Anda tidak memiliki izin untuk menghapusnya.";
        }
    } catch (PDOException $e) {
        $error_message = "Terjadi kesalahan database: " . $e->getMessage();
    }
}

// Redirect kembali ke dashboard penjual setelah operasi selesai
// Gunakan JavaScript untuk memastikan pesan ditampilkan sebelum redirect
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/main.css">
</head>
<body>
    <div class="container mt-5" style="padding-top: 20px;">
        <h2 class="mb-4">Hapus Produk</h2>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <a href="seller_dashboard.php" class="btn btn-primary">Kembali ke Dashboard Produk</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Redirect setelah beberapa detik agar user bisa membaca pesan
        setTimeout(function() {
            window.location.href = '<?php echo $base_url; ?>seller/seller_dashboard.php';
        }, 3000); // Redirect setelah 3 detik
    </script>
</body>
</html>