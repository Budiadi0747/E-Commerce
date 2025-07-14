<?php


session_start();
require_once __DIR__ . '/../config/db.php';

// Pastikan user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$page_title = "Edit Pengguna - Admin KangSayur";
include_once __DIR__ . '/admin_header.php';

$user_id = $_GET['user_id'] ?? null;
$user = null;
$error_message = '';
$success_message = '';

if (!$user_id) {
    $error_message = "ID Pengguna tidak ditemukan.";
} else {
    try {
        $pdo = get_pdo_connection();

        // Ambil data pengguna berdasarkan ID
        // KOREKSI: Tambahkan phone_number di SELECT
        $stmt = $pdo->prepare("SELECT id, username, email, nama, phone_number, role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error_message = "Pengguna tidak ditemukan.";
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
            $new_username = trim($_POST['username'] ?? '');
            $new_email = trim($_POST['email'] ?? '');
            $new_nama = trim($_POST['nama'] ?? '');
            $new_phone_number = trim($_POST['phone_number'] ?? ''); // KOREKSI: Ambil phone_number dari POST
            $new_role = trim($_POST['role'] ?? '');
            $new_password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            // Validasi input
            if (empty($new_username) || empty($new_email) || empty($new_role)) {
                $error_message = "Username, email, dan role wajib diisi.";
            } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Format email tidak valid.";
            } elseif (!empty($new_password) && $new_password !== $confirm_password) {
                $error_message = "Konfirmasi password tidak cocok.";
            } elseif (!empty($new_password) && strlen($new_password) < 6) {
                $error_message = "Password minimal 6 karakter.";
            } elseif (!in_array($new_role, ['admin', 'penjual', 'pembeli'])) {
                $error_message = "Role tidak valid.";
            } else {
                // Cek apakah username atau email sudah terdaftar oleh pengguna lain
                $stmt_check = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $stmt_check->execute([$new_username, $new_email, $user_id]);
                if ($stmt_check->fetch()) {
                    $error_message = "Username atau Email sudah terdaftar oleh pengguna lain. Mohon gunakan yang lain.";
                } else {
                    // Update pengguna di database
                    // KOREKSI: Tambahkan phone_number di UPDATE
                    $sql = "UPDATE users SET username = ?, email = ?, nama = ?, phone_number = ?, role = ?";
                    $params = [$new_username, $new_email, $new_nama, $new_phone_number, $new_role];

                    // Jika password diisi, hash dan update password
                    if (!empty($new_password)) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $sql .= ", password = ?";
                        $params[] = $hashed_password;
                    }

                    $sql .= " WHERE id = ?";
                    $params[] = $user_id;

                    $stmt_update = $pdo->prepare($sql);
                    if ($stmt_update->execute($params)) {
                        $success_message = "Pengguna berhasil diperbarui!";
                        // Refresh data pengguna setelah update berhasil
                        $stmt = $pdo->prepare("SELECT id, username, email, nama, phone_number, role FROM users WHERE id = ?"); // KOREKSI: Refresh data dengan phone_number
                        $stmt->execute([$user_id]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $error_message = "Gagal memperbarui pengguna.";
                    }
                }
            }
        }

    } catch (PDOException $e) {
        $error_message = "Terjadi kesalahan database: " . $e->getMessage();
    }
}
?>

<div class="container mt-5">
    <h2 class="mb-4">Edit Pengguna</h2>

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

    <?php if ($user): ?>
        <div class="card p-4">
            <form action="edit_user.php?user_id=<?php echo htmlspecialchars($user['id']); ?>" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Lengkap:</label>
                    <input type="text" class="form-control" id="nama" name="nama" value="<?php echo htmlspecialchars($user['nama'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="phone_number" class="form-label">Nomor Telepon:</label> <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>"> </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password Baru (kosongkan jika tidak ingin mengubah):</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Konfirmasi Password Baru:</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Role:</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="admin" <?php echo (isset($user['role']) && $user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="penjual" <?php echo (isset($user['role']) && $user['role'] == 'penjual') ? 'selected' : ''; ?>>Penjual</option>
                        <option value="pembeli" <?php echo (isset($user['role']) && $user['role'] == 'pembeli') ? 'selected' : ''; ?>>Pembeli</option>
                    </select>
                </div>
                <button type="submit" name="update_user" class="btn btn-primary me-2">Perbarui Pengguna</button>
                <a href="admin_users.php" class="btn btn-secondary">Kembali ke Daftar Pengguna</a>
            </form>
        </div>
    <?php else: ?>
        <p class="text-danger">Pengguna tidak dapat dimuat. Silakan kembali ke daftar pengguna.</p>
        <a href="admin_users.php" class="btn btn-secondary">Kembali ke Daftar Pengguna</a>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../include/footer.php'; ?>