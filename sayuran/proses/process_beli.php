<?php
// D:\xampp\htdocs\harvestly_2\process_beli.php

session_start();
require_once __DIR__ . '/config/db.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Pastikan request adalah POST dari form pembelian
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? null;
    $user_id = $_SESSION['user_id'];

    if (empty($product_id) || empty($quantity) || !is_numeric($product_id) || !is_numeric($quantity) || $quantity <= 0) {
        // Redirect dengan pesan error jika input tidak valid
        header('Location: beli_produk.php?id=' . htmlspecialchars($product_id) . '&status=error&message=' . urlencode('Input tidak valid untuk pembelian.'));
        exit();
    }

    try {
        $pdo = get_pdo_connection();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 1. Ambil informasi produk dan cek stok
        $stmt = $pdo->prepare("SELECT id, name, price, stock FROM produk WHERE id = ? AND status_persetujuan = 'approved' FOR UPDATE"); // FOR UPDATE untuk mengunci baris
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            header('Location: produk.php?status=error&message=' . urlencode('Produk tidak ditemukan atau tidak tersedia.'));
            exit();
        }

        if ($product['stock'] < $quantity) {
            header('Location: beli_produk.php?id=' . htmlspecialchars($product_id) . '&status=error&message=' . urlencode('Stok tidak mencukupi untuk jumlah yang diminta. Stok tersedia: ' . $product['stock'] . ' kg.'));
            exit();
        }

        // Mulai transaksi database
        $pdo->beginTransaction();

        // 2. Kurangi stok produk
        $new_stock = $product['stock'] - $quantity;
        $stmt_update = $pdo->prepare("UPDATE produk SET stock = ? WHERE id = ?");
        $stmt_update->execute([$new_stock, $product_id]);

        // 3. (Opsional/Lanjutan) Catat transaksi/pesanan
        // Di sini Anda akan memasukkan data ke tabel 'orders' atau 'order_items'
        // Untuk demo ini, kita hanya akan mengurangi stok.
        // Contoh sederhana penambahan ke tabel orders (Anda perlu membuat tabel ini):
        // $total_price = $product['price'] * $quantity;
        // $stmt_order = $pdo->prepare("INSERT INTO orders (user_id, product_id, quantity, total_price, order_date, status) VALUES (?, ?, ?, ?, NOW(), 'pending')");
        // $stmt_order->execute([$user_id, $product_id, $quantity, $total_price]);

        // Commit transaksi
        $pdo->commit();

        // Redirect ke halaman sukses atau detail pesanan
        header('Location: konfirmasi_pembelian.php?status=success&message=' . urlencode('Pembelian berhasil! Stok telah diperbarui.'));
        exit();

    } catch (PDOException $e) {
        // Rollback transaksi jika terjadi kesalahan
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Redirect dengan pesan error
        header('Location: beli_produk.php?id=' . htmlspecialchars($product_id) . '&status=error&message=' . urlencode('Terjadi kesalahan saat memproses pembelian: ' . $e->getMessage()));
        exit();
    }
} else {
    // Jika akses langsung ke halaman ini tanpa POST
    header('Location: produk.php?status=error&message=' . urlencode('Akses tidak diizinkan.'));
    exit();
}
?>