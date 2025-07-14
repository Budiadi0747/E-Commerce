<?php


session_start();
require_once __DIR__ . '/../config/db.php';

// Pastikan user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // PERBAIKAN DI SINI: Path redirect ke login
    header('Location: ../login/login.php');
    exit();
}

$page_title = "Tambah Pengguna Baru - Admin KangSayur";
// PERBAIKAN DI SINI: Path include admin_header.php
include_once __DIR__ . '/admin_header.php';

$error_message = '';
$success_message = '';

// Inisialisasi variabel untuk value form agar tidak error saat pertama kali dibuka
$username = '';
$email = '';
$nama = '';
$phone_number = ''; // KOREKSI: Gunakan phone_number
$role = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? ''); // KOREKSI: Gunakan phone_number
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = trim($_POST['role'] ?? '');

    // Validasi input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $error_message = "Semua kolom wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Konfirmasi password tidak cocok.";
    } elseif (strlen($password) < 6) { // Contoh: password minimal 6 karakter
        $error_message = "Password minimal 6 karakter.";
    } elseif (!in_array($role, ['admin', 'penjual', 'pembeli'])) {
        $error_message = "Role tidak valid.";
    } else {
        try {
            $pdo = get_pdo_connection();

            // Cek apakah username atau email sudah terdaftar
            $stmt_check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt_check->execute([$username, $email]);
            if ($stmt_check->fetch()) {
                $error_message = "Username atau Email sudah terdaftar. Mohon gunakan yang lain.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert pengguna baru ke database
                // KOREKSI: Tambahkan phone_number di kolom dan nilai
                $stmt_insert = $pdo->prepare("INSERT INTO users (username, email, nama, phone_number, password, role) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt_insert->execute([$username, $email, $nama, $phone_number, $hashed_password, $role])) {
                    $success_message = "Pengguna baru berhasil ditambahkan!";
                    // Kosongkan form setelah berhasil agar bisa menambahkan lagi
                    $username = $email = $nama = $password = $confirm_password = ''; 
                    $phone_number = ''; // KOREKSI: Kosongkan phone_number
                    $role = ''; 
                } else {
                    $error_message = "Gagal menambahkan pengguna baru.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan database: " . $e->getMessage();
        }
    }
}
?>

<div class="container mt-5">
    <h2 class="mb-4">Tambah Pengguna Baru</h2>

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

    <div class="card p-4">
        <form action="add_user.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="mb-3">
                <label for="nama" class="form-label">Nama Lengkap:</label>
                <input type="text" class="form-control" id="nama" name="nama" value="<?php echo htmlspecialchars($nama); ?>">
            </div>
            <div class="mb-3">
                <label for="phone_number" class="form-label">Nomor Telepon:</label> 
                <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>"> 
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Konfirmasi Password:</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role:</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="">Pilih Role</option>
                    <option value="admin" <?php echo ($role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="penjual" <?php echo ($role == 'penjual') ? 'selected' : ''; ?>>Penjual</option>
                    <option value="pembeli" <?php echo ($role == 'pembeli') ? 'selected' : ''; ?>>Pembeli</option>
                </select>
            </div>
            <button type="submit" name="add_user" class="btn btn-primary me-2">Tambah Pengguna</button>
            <a href="admin_users.php" class="btn btn-secondary">Kembali ke Daftar Pengguna</a>
        </form>
    </div>
</div>

<?php include_once __DIR__ . '/../include/footer.php'; ?>