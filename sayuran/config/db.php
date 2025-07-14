<?php


// Pastikan error reporting diatur dengan benar untuk pengembangan
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Konfigurasi Database Baru Anda
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sayuran');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        )
    );
    // AKTIFKAN INI UNTUK DEBUGGING KONEKSI
    //echo "Koneksi database berhasil!<br>"; // <-- Pastikan ini TIDAK dikomentari
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

function get_pdo_connection() {
    global $pdo;
    return $pdo;
}
?>