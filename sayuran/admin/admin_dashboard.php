<?php


session_start();

// Pastikan user sudah login dan role-nya adalah 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Arahkan kembali ke login/login.php jika tidak ada sesi admin
    header('Location: ../login/login.php'); 
    exit();
}

// Sertakan file koneksi database
require_once __DIR__ . '/../config/db.php';

$page_title = "Dashboard Admin - KangSayur.com";

// Sertakan admin_header.php
require_once __DIR__ . '/admin_header.php';
?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Selamat Datang, Admin <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <p class="text-center">Ini adalah halaman dashboard admin. Anda dapat menambahkan link untuk mengelola produk, user, dll di sini.</p>

    <div class="row mt-5">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Manajemen Produk</h5>
                    <p class="card-text">Kelola daftar produk panen.</p>
                    
                    <a href="admin_product_approval.php" class="btn btn-primary me-2 mb-2">Persetujuan Produk</a> 
                    
                    <a href="../produk/tambah_produk.php" class="btn btn-success me-2 mb-2">Tambah Produk (Admin)</a>
                    
                    <a href="admin_produk.php" class="btn btn-info me-2 mb-2">Kelola Produk (Admin)</a> 
                    
                    <a href="../produk/produk.php" class="btn btn-secondary me-2 mb-2">Lihat Semua Produk (Customer View)</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Manajemen Pengguna</h5>
                    <p class="card-text">Kelola akun pengguna KangSayur.com.</p>
                    <a href="admin_users.php" class="btn btn-warning me-2 mb-2">Lihat Pengguna</a>
                    <a href="add_user.php" class="btn btn-success me-2 mb-2">Tambah Pengguna</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Manajemen Pesanan</h5>
                    <p class="card-text">Kelola pesanan pembeli.</p>
                    <a href="admin_orders.php" class="btn btn-primary me-2 mb-2">Lihat Pesanan</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Sertakan footer.php
require_once __DIR__ . '/../include/footer.php';
?>