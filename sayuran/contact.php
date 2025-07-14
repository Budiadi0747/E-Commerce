<?php
// D:\xampp\htdocs\harvestly_2\contact.php

session_start(); // Jika halaman kontak memerlukan sesi

// Path ini akan benar jika contact.php ada di root harvestly_2
require_once __DIR__ . '/config/db.php'; // Jika perlu koneksi database

$page_title = "Hubungi Kami";
// Path ini akan benar jika contact.php ada di root harvestly_2
include_once __DIR__ . '/include/header.php';
?>

<div class="container mt-5">
    <h2 class="mb-4">Hubungi Kami</h2>
    <p>Ini adalah halaman kontak. Anda bisa menambahkan form kontak di sini.</p>
    <p>Silakan hubungi kami melalui email: info@kangsayur.com</p>
    </div>

<?php
// Path ini akan benar jika contact.php ada di root harvestly_2
include_once __DIR__ . '/include/footer.php';
?>