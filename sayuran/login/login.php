<?php


// Mulai sesi PHP
session_start();

// Sertakan file koneksi database Anda
// Path ini benar dari 'login/' ke 'config/'
require_once __DIR__ . '/../config/db.php';

// Jika user sudah login, arahkan ke halaman dashboard yang sesuai
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role'])) {
        // PERBAIKAN DI SINI: Sesuaikan 'admin' menjadi 'Administrator'
        if ($_SESSION['role'] === 'Administrator') {
            // Path dari 'login/' ke 'admin/' (naik satu level, lalu masuk ke admin)
            header('Location: ../admin/admin_dashboard.php');
            exit();
        // PERBAIKAN DI SINI: Sesuaikan 'seller' menjadi 'penjual'
        } elseif ($_SESSION['role'] === 'penjual') { // Sesuaikan nama role jika di DB Anda 'penjual' atau 'seller'
            // Path dari 'login/' ke 'seller/' (naik satu level, lalu masuk ke seller)
            header('Location: ../seller/seller_dashboard.php');
            exit();
        // TAMBAHAN UNTUK PEMBELI
        } elseif ($_SESSION['role'] === 'pembeli') {
            // Path dari 'login/' ke 'pembeli/'
            header('Location: ../pembeli/dashboard.php');
            exit();
        } else { // Role lain yang tidak memiliki dashboard khusus
            // Path dari 'login/' ke 'index.php' (naik satu level ke root)
            header('Location: ../index.php');
            exit();
        }
    } else {
        // Fallback jika role tidak diset di sesi (misal, sesi lama)
        header('Location: ../index.php');
        exit();
    }
}

$error_message = '';

// Cek apakah form telah disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil dan bersihkan data dari form
    $username_or_email = trim($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi input di sisi server
    if (empty($username_or_email) || empty($password)) {
        $error_message = "Username/Email dan Password harus diisi.";
    } else {
        try {
            $pdo = get_pdo_connection();
            // Persiapkan query untuk mencari user berdasarkan username ATAU email
            $stmt = $pdo->prepare("SELECT id, username, password, email, nama, phone_number, role FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username_or_email, $username_or_email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Cek apakah user ditemukan dan verifikasi password
            if ($user && password_verify($password, $user['password'])) {
                // Login berhasil
                // Set variabel sesi
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['phone_number'] = $user['phone_number'];
                $_SESSION['role'] = $user['role'];

                // Arahkan user ke halaman yang sesuai berdasarkan role
                // PERBAIKAN DI SINI: Sesuaikan 'admin' menjadi 'Administrator'
                if ($user['role'] === 'Administrator') {
                    // Path dari 'login/' ke 'admin/'
                    header('Location: ../admin/admin_dashboard.php');
                // PERBAIKAN DI SINI: Sesuaikan 'seller' menjadi 'penjual'
                } elseif ($user['role'] === 'penjual') { // Pastikan role ini sesuai dengan nilai di database (misal: 'seller' bukan 'penjual')
                    // Path dari 'login/' ke 'seller/'
                    header('Location: ../seller/seller_dashboard.php');
                // TAMBAHAN UNTUK PEMBELI
                } elseif ($user['role'] === 'pembeli') {
                    // Path dari 'login/' ke 'pembeli/'
                    header('Location: ../pembeli/dashboard_pembeli.php');
                } else { // Termasuk role lain yang tidak memiliki dashboard khusus
                    // Path dari 'login/' ke 'index.php'
                    header('Location: ../index.php');
                }
                exit();
            } else {
                // Login gagal (user tidak ditemukan atau password salah)
                $error_message = "Username/Email atau Password salah.";
            }
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan database: " . $e->getMessage();
            // Di lingkungan produksi, log error ini, jangan tampilkan ke user
        }
    }
}

// ==== SERTAKAN HEADER DAN FOOTER ====
$page_title = "Login Akun - KangSayur";
// Path ini benar dari 'login/' ke 'include/'
include_once __DIR__ . '/../include/header.php';
?>

<div class="container">
    <div class="register-container">
        <h2 class="text-center mb-4">Login ke Akun KangSayur</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label for="username_or_email" class="form-label">Username atau Email</label>
                <input type="text" class="form-control" id="username_or_email" name="username_or_email" required value="<?php echo htmlspecialchars($_POST['username_or_email'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Login</button>
        </form>
        <p class="text-center mt-3">Belum punya akun? <a href="../register.php">Daftar di sini</a></p>
    </div>
</div>

<?php
// Path ini benar dari 'login/' ke 'include/'
include_once __DIR__ . '/../include/footer.php';
?>
