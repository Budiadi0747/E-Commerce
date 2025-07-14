<?php
// D:\xampp\htdocs\harvestly_2\process_address.php
// Script untuk memproses aksi terkait alamat (tambah, set default, hapus)

session_start();
require_once __DIR__ . '/config/db.php'; // Pastikan path ini benar

// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$status = 'error'; // Default status jika terjadi kesalahan

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? ''; // Mengambil aksi dari form (add_address, set_default, delete_address)

    try {
        $pdo = get_pdo_connection();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->beginTransaction(); // Memulai transaksi untuk operasi database yang aman

        switch ($action) {
            case 'add_address':
                $address_line1 = $_POST['address_line1'] ?? '';
                $address_line2 = $_POST['address_line2'] ?? '';
                $city = $_POST['city'] ?? '';
                $province = $_POST['province'] ?? ''; // Ditambahkan 'province'
                $postal_code = $_POST['postal_code'] ?? '';
                $phone_number = $_POST['phone_number'] ?? '';
                $type = $_POST['type'] ?? 'shipping'; // 'shipping' atau 'billing'
                $is_default = isset($_POST['is_default']) ? 1 : 0; // 0 atau 1

                // Validasi minimal input
                if (empty($address_line1) || empty($city) || empty($province) || empty($postal_code) || empty($phone_number)) {
                    throw new Exception("Semua bidang alamat wajib diisi (kecuali Alamat Baris 2).");
                }

                // Jika alamat baru ini akan dijadikan default, set semua alamat lain dengan 'type' yang sama menjadi non-default
                if ($is_default) {
                    $stmt_reset_default = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ? AND type = ?");
                    $stmt_reset_default->execute([$user_id, $type]);
                }

                // Masukkan alamat baru ke tabel addresses
                $stmt = $pdo->prepare("INSERT INTO addresses (user_id, address_line1, address_line2, city, province, postal_code, phone_number, is_default, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $address_line1, $address_line2, $city, $province, $postal_code, $phone_number, $is_default, $type]);
                
                $message = "Alamat berhasil ditambahkan.";
                $status = 'success';
                break;

            case 'set_default':
                $address_id = $_POST['address_id'] ?? null;
                if (!is_numeric($address_id)) {
                    throw new Exception("ID alamat tidak valid.");
                }

                // Dapatkan tipe alamat (shipping/billing) dari alamat yang akan dijadikan default
                $stmt_get_type = $pdo->prepare("SELECT type FROM addresses WHERE id = ? AND user_id = ?");
                $stmt_get_type->execute([$address_id, $user_id]);
                $addr_type = $stmt_get_type->fetchColumn();

                if (!$addr_type) {
                    throw new Exception("Alamat tidak ditemukan atau bukan milik Anda.");
                }

                // Batalkan status default untuk semua alamat dengan 'type' yang sama milik user ini
                $stmt_reset_default = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ? AND type = ?");
                $stmt_reset_default->execute([$user_id, $addr_type]);

                // Set alamat yang dipilih sebagai default
                $stmt_set_default = $pdo->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
                $stmt_set_default->execute([$address_id, $user_id]);
                
                $message = "Alamat berhasil dijadikan default.";
                $status = 'success';
                break;

            case 'delete_address':
                $address_id = $_POST['address_id'] ?? null;
                if (!is_numeric($address_id)) {
                    throw new Exception("ID alamat tidak valid.");
                }

                // Opsional: Periksa apakah alamat ini digunakan dalam pesanan yang sudah ada
                // Ini akan mencegah penghapusan alamat yang terkait dengan pesanan.
                // Jika ingin memungkinkan penghapusan (misal: jika pesanan dihapus), hapus bagian ini.
                $stmt_check_order = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE (shipping_address_id = ? OR billing_address_id = ?) AND user_id = ?");
                $stmt_check_order->execute([$address_id, $address_id, $user_id]);
                if ($stmt_check_order->fetchColumn() > 0) {
                    throw new Exception("Tidak dapat menghapus alamat ini karena masih terkait dengan pesanan yang sudah ada. Silakan hubungi admin.");
                }

                // Pastikan alamat bukan default sebelum dihapus (agar tidak ada kebingungan default)
                $stmt_check_default = $pdo->prepare("SELECT is_default FROM addresses WHERE id = ? AND user_id = ?");
                $stmt_check_default->execute([$address_id, $user_id]);
                $is_default_to_delete = $stmt_check_default->fetchColumn();

                if ($is_default_to_delete) {
                    throw new Exception("Tidak dapat menghapus alamat default. Mohon set alamat lain sebagai default terlebih dahulu untuk jenis alamat ini.");
                }

                // Hapus alamat dari tabel addresses
                $stmt = $pdo->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
                $stmt->execute([$address_id, $user_id]);
                
                $message = "Alamat berhasil dihapus.";
                $status = 'success';
                break;

            default:
                throw new Exception("Aksi tidak dikenal.");
        }

        $pdo->commit(); // Komit transaksi jika semua operasi berhasil

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack(); // Rollback transaksi jika terjadi kesalahan
        }
        $message = "Gagal memproses alamat: " . $e->getMessage();
        $status = 'error';
    }
} else {
    $message = "Permintaan tidak valid.";
    $status = 'error';
}

// Redirect kembali ke halaman profil dengan pesan status
header('Location: profile.php?status=' . urlencode($status) . '&message=' . urlencode($message));
exit();