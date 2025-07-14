<?php


// Pastikan base_url ini mengarah ke root aplikasi Harvestly_2 Anda.
// Sesuaikan jika port atau nama folder Anda berbeda.
$base_url = "http://localhost:8080/sayuran/"; // PASTIKAN URL INI BENAR
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'KangSayur Admin'; ?></title>
    <link href="<?php echo $base_url; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo $base_url; ?>assets/fontawesome/css/all.min.css" rel="stylesheet">
    <link href="<?php echo $base_url; ?>assets/css/main.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $base_url; ?>admin/admin_dashboard.php">
    <img src="<?php echo $base_url; ?>assets/img/kangsayur.png" alt="kangsayur Admin Logo" style="max-height: 40px;">
    </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>admin/admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>admin/admin_produk.php"><i class="fas fa-box"></i> Produk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="<?php echo $base_url; ?>admin/admin_orders.php"><i class="fas fa-receipt"></i> Pesanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>admin/admin_users.php"><i class="fas fa-users"></i> Pengguna</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>produk/tambah_produk.php"><i class="fas fa-plus-square"></i> Tambah Produk Baru</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['username'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAdmin" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownAdmin">
                                <li><a class="dropdown-item" href="<?php echo $base_url; ?>login/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div style="padding-top: 70px;"></div>