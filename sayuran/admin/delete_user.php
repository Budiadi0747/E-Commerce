<?php


session_start();
require_once __DIR__ . '/../config/db.php';

// Pastikan user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_GET['user_id'] ?? null;
$error_message = '';
$success_message = '';

if (!$user_id) {
    $error_message = "ID Pengguna tidak ditemukan untuk dihapus.";
} else {
    try {
        $pdo = get_pdo_connection();

        // Pencegahan: Jangan biarkan admin menghapus dirinya sendiri
        if ($user_id == $_SESSION['user_id']) {
            $error_message = "Anda tidak bisa menghapus akun Anda sendiri.";
        } else {
            // Cek apakah pengguna ada sebelum mencoba menghapus
            $stmt_check = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt_check->execute([$user_id]);
            if (!$stmt_check->fetch()) {
                $error_message = "Pengguna dengan ID tersebut tidak ditemukan.";
            } else {
                // Hapus pengguna dari database
                $stmt_delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
                if ($stmt_delete->execute([$user_id])) {
                    $success_message = "Pengguna berhasil dihapus.";
                } else {
                    $error_message = "Gagal menghapus pengguna.";
                }
            }
        }

    } catch (PDOException $e) {
        $error_message = "Terjadi kesalahan database saat menghapus pengguna: " . $e->getMessage();
    }
}

// Redirect kembali ke halaman manajemen pengguna setelah proses selesai
// Anda bisa menambahkan parameter pesan di URL jika ingin menampilkannya di admin_users.php
if (!empty($success_message)) {
    header('Location: admin_users.php?status=success&message=' . urlencode($success_message));
} elseif (!empty($error_message)) {
    header('Location: admin_users.php?status=error&message=' . urlencode($error_message));
} else {
    header('Location: admin_users.php'); // Redirect tanpa pesan jika tidak ada error/success spesifik
}
exit();
?>