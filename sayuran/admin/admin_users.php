<?php


session_start();
require_once __DIR__ . '/../config/db.php';

// Pastikan user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // PERBAIKAN DI SINI: Path redirect ke login
    header('Location: ../login/login.php');
    exit();
}

$page_title = "Manajemen Pengguna - Admin KangSayur";
// PERBAIKAN DI SINI: Path include admin_header.php
include_once __DIR__ . '/admin_header.php';

$users = [];
$error_message = '';

try {
    $pdo = get_pdo_connection();
    // KOREKSI: Tambahkan phone_number di SELECT
    $stmt = $pdo->prepare("SELECT id, username, email, nama, phone_number, role, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan database: " . $e->getMessage();
}
?>

<div class="container mt-5">
    <h2 class="mb-4">Manajemen Pengguna</h2>

    <?php
    // Tangani pesan status dari redirect (setelah delete, add, atau edit)
    if (isset($_GET['status']) && isset($_GET['message'])) {
        $status_type = $_GET['status'] == 'success' ? 'alert-success' : 'alert-danger';
        echo '<div class="alert ' . $status_type . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($_GET['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    ?>

    <div class="mb-3">
        <a href="add_user.php" class="btn btn-success"><i class="fas fa-plus"></i> Tambah Pengguna Baru</a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($users)): ?>
        <div class="alert alert-info text-center" role="alert">
            Tidak ada pengguna terdaftar.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Nama Lengkap</th>
                        <th>Nomor Telepon</th>
                        <th>Role</th>
                        <th>Terdaftar Sejak</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['nama'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($user['phone_number'] ?? '-'); ?></td>
                            <td>
                                <span class="badge 
                                    <?php 
                                        if ($user['role'] == 'admin') echo 'bg-danger';
                                        elseif ($user['role'] == 'penjual') echo 'bg-info';
                                        else echo 'bg-secondary';
                                    ?>">
                                    <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="edit_user.php?user_id=<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-sm btn-warning me-2">Edit</a> 
                                <a href="delete_user.php?user_id=<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini? Tindakan ini tidak bisa dibatalkan!');">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../include/footer.php'; ?>