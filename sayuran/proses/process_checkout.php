<?php
// D:\xampp\htdocs\harvestly_2\process_checkout.php

session_start(); // Mulai sesi PHP untuk mengakses status login

// Sertakan file koneksi database Anda
require_once __DIR__ . '/config/db.php';

// Pastikan user sudah login sebelum bisa mengakses halaman ini
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Arahkan ke halaman login jika belum login
    exit();
}

// Pastikan request adalah POST dari form checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $payment_method = $_POST['payment_method'] ?? null; // Ambil metode pembayaran yang dipilih

    // Validasi input metode pembayaran
    if (empty($payment_method)) {
        header('Location: checkout.php?status=error&message=' . urlencode('Metode pembayaran belum dipilih.'));
        exit();
    }

    try {
        $pdo = get_pdo_connection(); // Dapatkan koneksi PDO dari db.php
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Aktifkan mode error untuk PDO

        $pdo->beginTransaction(); // Mulai transaksi database untuk memastikan atomisitas operasi

        // 1. Ambil semua item dari keranjang user yang sedang login
        // Gunakan FOR UPDATE untuk mengunci baris produk yang akan diubah stoknya
        $stmt_cart = $pdo->prepare("
            SELECT 
                k.product_id, 
                k.quantity, 
                p.stock, 
                p.price, 
                p.name AS product_name
            FROM keranjang k
            JOIN produk p ON k.product_id = p.id
            WHERE k.user_id = ?
            FOR UPDATE
        ");
        $stmt_cart->execute([$user_id]);
        $cart_items = $stmt_cart->fetchAll(PDO::FETCH_ASSOC);

        // Periksa apakah keranjang kosong
        if (empty($cart_items)) {
            $pdo->rollBack(); // Batalkan transaksi
            header('Location: keranjang.php?status=error&message=' . urlencode('Keranjang Anda kosong, tidak ada yang bisa di-checkout.'));
            exit();
        }

        // Inisialisasi total harga pesanan
        $total_order_price = 0;

        // 2. Verifikasi stok dan kurangi stok untuk setiap item di keranjang
        // Anda mungkin perlu tabel 'orders' dan 'order_items' di database Anda
        // CREATE TABLE orders (
        //    id INT AUTO_INCREMENT PRIMARY KEY,
        //    user_id INT NOT NULL,
        //    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        //    total_amount DECIMAL(10, 2) NOT NULL,
        //    payment_method VARCHAR(50) NOT NULL,
        //    status VARCHAR(50) DEFAULT 'pending',
        //    FOREIGN KEY (user_id) REFERENCES users(id)
        // );
        //
        // CREATE TABLE order_items (
        //    id INT AUTO_INCREMENT PRIMARY KEY,
        //    order_id INT NOT NULL,
        //    product_id INT NOT NULL,
        //    quantity INT NOT NULL,
        //    price_at_order DECIMAL(10, 2) NOT NULL,
        //    FOREIGN KEY (order_id) REFERENCES orders(id),
        //    FOREIGN KEY (product_id) REFERENCES produk(id)
        // );

        // (Opsional) Buat entri pesanan utama di tabel 'orders'
        // Anda perlu membuat tabel 'orders' terlebih dahulu jika belum ada
        // Untuk saat ini, kita bisa melewati langkah ini jika Anda belum punya tabel orders
        // Tetapi ini sangat direkomendasikan untuk pencatatan transaksi yang benar.

        // Contoh: Memasukkan ke tabel orders (jika ada)
        // $stmt_insert_order = $pdo->prepare("INSERT INTO orders (user_id, total_amount, payment_method, status) VALUES (?, ?, ?, 'pending')");
        // // total_amount akan dihitung di bawah, lalu di-update atau disisipkan dengan total_amount langsung

        $order_items_to_insert = []; // Untuk menyimpan data item pesanan

        foreach ($cart_items as $item) {
            // Periksa ulang stok
            if ($item['quantity'] > $item['stock']) {
                $pdo->rollBack(); // Batalkan transaksi jika stok tidak mencukupi
                header('Location: checkout.php?status=error&message=' . urlencode('Stok tidak mencukupi untuk ' . htmlspecialchars($item['product_name']) . '. Mohon sesuaikan jumlahnya di keranjang Anda.'));
                exit();
            }

            // Hitung harga subtotal untuk item ini
            $subtotal = $item['quantity'] * $item['price'];
            $total_order_price += $subtotal; // Tambahkan ke total harga pesanan

            // Kurangi stok produk di tabel 'produk'
            $new_stock = $item['stock'] - $item['quantity'];
            $stmt_update_stock = $pdo->prepare("UPDATE produk SET stock = ? WHERE id = ?");
            $stmt_update_stock->execute([$new_stock, $item['product_id']]);

            // Siapkan data untuk tabel order_items
            $order_items_to_insert[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price_at_order' => $item['price'] // Harga produk saat dipesan
            ];
        }

        // 3. Masukkan pesanan ke tabel 'orders' dan 'order_items'
        // Jika Anda belum memiliki tabel orders, baris ini akan menyebabkan error
        // Jika Anda telah membuat tabel 'orders' dan 'order_items', aktifkan kode ini
        /*
        $stmt_insert_order = $pdo->prepare("INSERT INTO orders (user_id, total_amount, payment_method, status) VALUES (?, ?, ?, 'pending')");
        $stmt_insert_order->execute([$user_id, $total_order_price, $payment_method]);
        $order_id = $pdo->lastInsertId(); // Dapatkan ID pesanan yang baru dibuat

        $stmt_insert_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_order) VALUES (?, ?, ?, ?)");
        foreach ($order_items_to_insert as $item) {
            $stmt_insert_item->execute([$order_id, $item['product_id'], $item['quantity'], $item['price_at_order']]);
        }
        */

        // 4. Kosongkan keranjang setelah semua stok berhasil dikurangi dan pesanan dicatat
        $stmt_clear_cart = $pdo->prepare("DELETE FROM keranjang WHERE user_id = ?");
        $stmt_clear_cart->execute([$user_id]);

        $pdo->commit(); // Commit transaksi jika semua operasi berhasil

        // Redirect ke halaman konfirmasi sukses
        header('Location: konfirmasi_pembelian.php?status=success&message=' . urlencode('Checkout berhasil! Pesanan Anda telah diproses.'));
        exit();

    } catch (PDOException $e) {
        // Rollback transaksi jika terjadi kesalahan
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Redirect dengan pesan error ke halaman checkout
        header('Location: checkout.php?status=error&message=' . urlencode('Terjadi kesalahan saat memproses checkout: ' . $e->getMessage()));
        exit();
    }
} else {
    // Jika diakses langsung tanpa POST request
    header('Location: produk.php?status=error&message=' . urlencode('Akses tidak diizinkan.'));
    exit();
}
?>