<?php
// D:\xampp\htdocs\harvestly_2\login\logout.php
// (Pastikan file ini berada di lokasi yang benar ini)

// Mulai sesi PHP
session_start();

// Hapus semua variabel sesi
$_SESSION = array();

// Hapus cookie sesi
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan sesi
session_destroy();

// Arahkan kembali ke halaman beranda utama
// KOREKSI PENTING: Menggunakan '../index.php' karena file logout.php berada di dalam subfolder 'login/'
header('Location: ../index.php');
exit();
?>