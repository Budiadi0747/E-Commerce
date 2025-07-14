<?php
// D:\xampp\htdocs\harvestly_2\register.php

// Sertakan file koneksi database Anda
require_once __DIR__ . '/config/db.php';

$error_message = '';
$success_message = '';

// Inisialisasi variabel untuk mempertahankan nilai di form setelah submit (jika ada error)
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$nama = $_POST['nama'] ?? '';
$phone_number = $_POST['phone_number'] ?? '';

// Cek apakah form telah disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil dan bersihkan data dari form
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi input di sisi server
    if (empty($username) || empty($email) || empty($nama) || empty($password) || empty($confirm_password) || empty($phone_number)) {
        $error_message = "Semua field harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid.";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone_number)) {
        $error_message = "Nomor telepon tidak valid. Hanya angka, minimal 10 digit, maksimal 15 digit.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Konfirmasi password tidak cocok.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password minimal 6 karakter.";
    } else {
        try {
            // Cek apakah username atau email sudah ada
            $stmt = get_pdo_connection()->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error_message = "Username atau email sudah terdaftar.";
            } else {
                // Hashing password sebelum disimpan ke database
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert data user baru ke tabel 'users'
                // Pastikan kolom 'phone_number' ada di tabel users Anda
                // Role default adalah 'pembeli'
                $stmt = get_pdo_connection()->prepare("INSERT INTO users (username, password, email, nama, phone_number, role) VALUES (?, ?, ?, ?, ?, 'pembeli')");

                // Eksekusi prepared statement dengan binding parameter
                $stmt->execute([$username, $hashed_password, $email, $nama, $phone_number]);

                $success_message = "Registrasi berhasil! Anda bisa login sekarang.";
                // Opsional: Redirect ke halaman login setelah registrasi berhasil
                // header('Location: login/login.php'); // Jika ingin langsung redirect
                // exit();
            }
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan database: " . $e->getMessage();
            // Di lingkungan produksi, jangan tampilkan $e->getMessage() ke user, cukup log saja
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Akun - KangSayur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <h2 class="text-center mb-4">Registrasi Akun KangSayur</h2>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama" name="nama" required value="<?php echo htmlspecialchars($nama ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="phone_number" class="form-label">Nomor Telepon</label> <input type="tel" class="form-control" id="phone_number" name="phone_number" required value="<?php echo htmlspecialchars($phone_number ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-success w-100">Daftar</button>
            </form>
            <p class="text-center mt-3">Sudah punya akun? <a href="login/login.php">Login di sini</a></p> </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>