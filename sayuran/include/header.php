<?php
// Panggil session_start() di paling atas, sebelum output HTML
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_url = "http://localhost:8080/sayuran/"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'KangSayur.com'; ?></title>

    <link href="<?php echo $base_url; ?>assets/img/favicon.png" rel="icon">
    <link href="<?php echo $base_url; ?>assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Inter:wght@100;200;300;400;500;600;700;800;900&family=Nunito:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;2,200;2,300;2,400;2,500;2,600;2,700;2,800;2,900&display=swap" rel="stylesheet">

    <link href="<?php echo $base_url; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo $base_url; ?>assets/css/main.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        body { background-image:url('<?php echo $base_url; ?>assets/img/backkangsayur.png') ; }
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .navmenu ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .navmenu li { position: relative; }
        .navmenu a {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: #495057;
            font-size: 16px;
            font-weight: 500;
            transition: 0.3s;
        }
        .navmenu a:hover, .navmenu .active { color: #28a745; }
        .btn-getstarted {
            background-color: #28a745;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            transition: 0.3s;
            text-decoration: none;
        }
        .btn-getstarted:hover { background-color: #218838; }
        .navmenu .badge {
            font-size: 0.75em;
            vertical-align: super;
            margin-left: 5px;
        }
    </style>
</head>
<body class="index-page">

<header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
        <a href="<?php echo $base_url; ?>index.php" class="logo d-flex align-items-center me-auto">
            <img src="<?php echo $base_url; ?>assets/img/kangsayur.png" alt="KangSayur Logo">
        </a>

        <nav id="navmenu" class="navmenu">
            <ul>
                <li><a href="<?php echo $base_url; ?>index.php" class="active">Home</a></li>
                <li><a href="<?php echo $base_url; ?>include/about.php">About</a></li>
                <li><a href="<?php echo $base_url; ?>produk/produk.php">Produk</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'pembeli'): ?>
                    <li>
                        <a href="<?php echo $base_url; ?>pembeli/dashboard_pembeli.php#riwayat-pesanan">
                            <i class="fas fa-receipt"></i> Riwayat Pesanan
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_url; ?>keranjang/keranjang.php">
                            <i class="fas fa-shopping-cart"></i> Keranjang
                            <?php
                            if (isset($_SESSION['user_id'])) {
                                require_once __DIR__ . '/../config/db.php';
                                try {
                                    $pdo = get_pdo_connection();
                                    $stmt = $pdo->prepare("SELECT SUM(quantity) AS total_items FROM keranjang WHERE user_id = ?");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $total_items_db = $stmt->fetchColumn();
                                    if ($total_items_db > 0) {
                                        echo '<span class="badge bg-success ms-1">' . htmlspecialchars($total_items_db) . '</span>';
                                    }
                                } catch (PDOException $e) {}
                            }
                            ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="<?php echo $base_url; ?>profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="<?php echo $base_url; ?>login/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo $base_url; ?>login/login.php">Login</a></li>
                    <li><a href="<?php echo $base_url; ?>register.php">Register</a></li>
                <?php endif; ?>
            </ul>
            <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>

        <a class="btn-getstarted" href="<?php echo $base_url; ?>contact.php">Contact Us</a>
    </div>
</header>
<main>
