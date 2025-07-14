<?php

session_start();
require_once __DIR__ . '/config/db.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message_temp'] = "Anda harus login untuk melanjutkan ke checkout.";
    header('Location: login.php');
    exit();
}

// Pastikan keranjang tidak kosong
if (empty($_SESSION['cart'])) {
    $_SESSION['error_message_temp'] = "Keranjang belanja Anda kosong. Silakan tambahkan produk terlebih dahulu.";
    header('Location: produk.php');
    exit();
}

$cart_items = $_SESSION['cart'];
$total_amount = 0;
$error_message = '';
$success_message = '';

// Hitung ulang total harga dan validasi stok terakhir
try {
    $pdo = get_pdo_connection();
    $stmt = $pdo->prepare("SELECT id, name, price, stock FROM produk WHERE id = ?");

    foreach ($cart_items as $product_id => &$item) { // Pakai & untuk bisa mengubah item di loop
        $stmt->execute([$product_id]);
        $db_product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$db_product || $db_product['stock'] < $item['quantity']) {
            $error_message = "Stok untuk produk '" . htmlspecialchars($item['name']) . "' tidak mencukupi atau produk tidak tersedia lagi. Silakan perbarui keranjang Anda.";
            unset($_SESSION['cart'][$product_id]); // Hapus item dari keranjang
            $_SESSION['error_message_temp'] = $error_message; // Untuk pesan setelah redirect
            header('Location: keranjang_belanja.php'); // Redirect ke keranjang untuk perbaikan
            exit();
        }
        // Update harga, stok_available, dan pastikan product_name ada di item keranjang
        $item['price'] = $db_product['price'];
        $item['stock_available'] = $db_product['stock'];
        $item['name'] = $db_product['name']; // Pastikan nama produk di keranjang up-to-date
        $total_amount += $item['price'] * $item['quantity'];
    }
    unset($item); // Putuskan referensi terakhir

} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan database saat memvalidasi keranjang: " . $e->getMessage();
}

// Inisialisasi data form pengiriman
$delivery_address_line1 = '';
$delivery_address_line2 = '';
$delivery_city = '';
$delivery_province = '';
$delivery_postal_code = '';
$delivery_phone = '';

// Coba ambil alamat default user dari tabel `addresses`
if (isset($_SESSION['user_id'])) {
    try {
        $stmt_address = $pdo->prepare("SELECT address_line1, address_line2, city, province, postal_code, phone_number FROM addresses WHERE user_id = ? AND is_default = 1 AND type IN ('shipping', 'both') LIMIT 1");
        $stmt_address->execute([$_SESSION['user_id']]);
        $default_address = $stmt_address->fetch(PDO::FETCH_ASSOC);

        if ($default_address) {
            $delivery_address_line1 = $default_address['address_line1'];
            $delivery_address_line2 = $default_address['address_line2'] ?? '';
            $delivery_city = $default_address['city'];
            $delivery_province = $default_address['province'] ?? '';
            $delivery_postal_code = $default_address['postal_code'];
            $delivery_phone = $default_address['phone_number'];
        }
    } catch (PDOException $e) {
        // Log error jika diperlukan, tapi jangan tampilkan ke user
        // error_log("Error fetching default address: " . $e->getMessage());
    }
}


// Proses Checkout saat Form Disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($error_message)) {
    $delivery_address_line1 = trim($_POST['delivery_address_line1'] ?? '');
    $delivery_address_line2 = trim($_POST['delivery_address_line2'] ?? '');
    $delivery_city = trim($_POST['delivery_city'] ?? '');
    $delivery_province = trim($_POST['delivery_province'] ?? '');
    $delivery_postal_code = trim($_POST['delivery_postal_code'] ?? '');
    $delivery_phone = trim($_POST['delivery_phone'] ?? '');

    // Validasi form pengiriman
    if (empty($delivery_address_line1) || empty($delivery_city) || empty($delivery_postal_code) || empty($delivery_phone)) {
        $error_message = "Mohon lengkapi semua informasi pengiriman yang wajib diisi (Alamat 1, Kota, Kode Pos, Telepon).";
    } elseif (!is_numeric($delivery_postal_code) || strlen($delivery_postal_code) < 5) {
        $error_message = "Kode pos tidak valid.";
    } elseif (!is_numeric($delivery_phone) || strlen($delivery_phone) < 8) {
        $error_message = "Nomor telepon tidak valid.";
    } else {
        try {
            $pdo->beginTransaction(); // Mulai transaksi

            $shipping_address_id = null;
            $billing_address_id = null; // Asumsi billing address sama dengan shipping address untuk saat ini

            // 1. Simpan atau Dapatkan ID Alamat Pengiriman
            // Cek apakah alamat ini sudah ada untuk user ini sebagai alamat pengiriman
            $stmt_check_address = $pdo->prepare("SELECT id FROM addresses WHERE user_id = ? AND address_line1 = ? AND city = ? AND postal_code = ? AND phone_number = ? AND type IN ('shipping', 'both') LIMIT 1");
            $stmt_check_address->execute([
                $_SESSION['user_id'],
                $delivery_address_line1,
                $delivery_city,
                $delivery_postal_code,
                $delivery_phone
            ]);
            $existing_address = $stmt_check_address->fetch(PDO::FETCH_ASSOC);

            if ($existing_address) {
                $shipping_address_id = $existing_address['id'];
                $billing_address_id = $existing_address['id']; // Jika sama
            } else {
                // Jika alamat baru, insert ke tabel addresses
                $stmt_insert_address = $pdo->prepare("INSERT INTO addresses (user_id, address_line1, address_line2, city, province, postal_code, phone_number, is_default, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_insert_address->execute([
                    $_SESSION['user_id'],
                    $delivery_address_line1,
                    $delivery_address_line2,
                    $delivery_city,
                    $delivery_province,
                    $delivery_postal_code,
                    $delivery_phone,
                    0, // Tidak set sebagai default otomatis saat checkout, bisa disesuaikan nanti
                    'shipping' // Atau 'both' jika Anda ingin ini jadi alamat default billing juga
                ]);
                $shipping_address_id = $pdo->lastInsertId();
                $billing_address_id = $shipping_address_id; // Asumsi billing address sama dengan shipping address
            }


            // 2. Simpan data pesanan ke tabel 'orders'
            $stmt_order = $pdo->prepare("INSERT INTO orders (user_id, total_amount, shipping_address_id, billing_address_id, status) VALUES (?, ?, ?, ?, 'Pending')");
            $stmt_order->execute([
                $_SESSION['user_id'],
                $total_amount,
                $shipping_address_id,
                $billing_address_id
            ]);
            $order_id = $pdo->lastInsertId(); // Dapatkan ID pesanan yang baru dibuat

            // 3. Simpan setiap item keranjang ke tabel 'order_items'
            // Sesuaikan nama kolom price_at_order menjadi price_at_purchase jika Anda tidak mengubah di DB
            $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price_at_order, quantity) VALUES (?, ?, ?, ?, ?)");
            $stmt_update_stock = $pdo->prepare("UPDATE produk SET stock = stock - ? WHERE id = ?");

            foreach ($cart_items as $item) {
                // Simpan item pesanan
                $stmt_item->execute([
                    $order_id,
                    $item['id'],
                    $item['name'], // Gunakan nama produk dari item keranjang yang sudah diupdate
                    $item['price'], // Gunakan harga dari item keranjang yang sudah diupdate (price_at_order)
                    $item['quantity']
                ]);

                // Kurangi stok produk
                $stmt_update_stock->execute([$item['quantity'], $item['id']]);
            }

            $pdo->commit(); // Commit transaksi jika semua berhasil

            // Kosongkan keranjang setelah pesanan berhasil
            unset($_SESSION['cart']);

            $_SESSION['success_message'] = "Pesanan Anda berhasil dibuat! Nomor Pesanan: #" . $order_id . ". Kami akan segera memproses pesanan Anda.";
            header('Location: ' . 'pesanan_sukses.php?order_id=' . $order_id); // Redirect ke halaman sukses
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack(); // Rollback transaksi jika ada error
            $error_message = "Terjadi kesalahan saat memproses pesanan: " . $e->getMessage();
            // Anda bisa logging error $e->getMessage() ke file log
        }
    }
}

$page_title = "Checkout - KangSayur";
include_once __DIR__ . '/include/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="checkout-container"> <h2 class="text-center mb-4">Checkout Pesanan</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message_temp'])): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($_SESSION['error_message_temp']); ?>
            </div>
            <?php unset($_SESSION['error_message_temp']); // Clear temp message after displaying ?>
        <?php endif; ?>

        <?php if (!empty($cart_items)): ?>
            <h4 class="mb-3">Ringkasan Pesanan</h4>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga per Kg</th>
                            <th>Kuantitas</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?> Kg</td>
                                <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total Belanja:</th>
                            <th>Rp <?php echo number_format($total_amount, 0, ',', '.'); ?></th>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-end">Biaya Pengiriman:</th>
                            <th>Rp 0 (Gratis)</th> </tr>
                        <tr>
                            <th colspan="3" class="text-end">Total Pembayaran:</th>
                            <th>Rp <?php echo number_format($total_amount, 0, ',', '.'); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <h4 class="mb-3">Informasi Pengiriman</h4>
            <form action="checkout.php" method="POST">
                <div class="mb-3">
                    <label for="delivery_address_line1" class="form-label">Alamat Lengkap (Baris 1)</label>
                    <input type="text" class="form-control" id="delivery_address_line1" name="delivery_address_line1" required value="<?php echo htmlspecialchars($delivery_address_line1); ?>">
                </div>
                <div class="mb-3">
                    <label for="delivery_address_line2" class="form-label">Alamat Lengkap (Baris 2, Opsional)</label>
                    <input type="text" class="form-control" id="delivery_address_line2" name="delivery_address_line2" value="<?php echo htmlspecialchars($delivery_address_line2); ?>">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="delivery_city" class="form-label">Kota</label>
                        <input type="text" class="form-control" id="delivery_city" name="delivery_city" required value="<?php echo htmlspecialchars($delivery_city); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="delivery_province" class="form-label">Provinsi (Opsional)</label>
                        <input type="text" class="form-control" id="delivery_province" name="delivery_province" value="<?php echo htmlspecialchars($delivery_province); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="delivery_postal_code" class="form-label">Kode Pos</label>
                        <input type="text" class="form-control" id="delivery_postal_code" name="delivery_postal_code" required value="<?php echo htmlspecialchars($delivery_postal_code); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="delivery_phone" class="form-label">Nomor Telepon</label>
                        <input type="tel" class="form-control" id="delivery_phone" name="delivery_phone" required value="<?php echo htmlspecialchars($delivery_phone); ?>">
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg">Konfirmasi Pesanan</button>
                    <a href="keranjang_belanja.php" class="btn btn-secondary btn-lg">Kembali ke Keranjang</a>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-info text-center" role="alert">
                Keranjang belanja Anda kosong.
                <p class="mt-2"><a href="produk.php" class="btn btn-primary">Mulai Belanja</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include_once __DIR__ . '/include/footer.php';
?>