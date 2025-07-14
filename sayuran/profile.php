<?php
// D:\xampp\htdocs\harvestly_2\profile.php
// Halaman Profil Pengguna untuk Melihat dan Mengelola Alamat

session_start();
require_once __DIR__ . '/config/db.php'; // Pastikan path ini benar

// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = "Profil Pengguna - KangSayur";

// Include header (pastikan header.php ada dan path-nya benar)
include_once __DIR__ . '/include/header.php';

// Variabel untuk menyimpan data user dan pesan status
$user_name = '';
$user_email = '';
$user_phone = '';
$status_message = '';
$status_type = ''; // 'success' atau 'error'

// Ambil pesan status dari URL jika ada (setelah redirect dari process_address.php)
if (isset($_GET['status']) && isset($_GET['message'])) {
    $status_type = htmlspecialchars($_GET['status']);
    $status_message = htmlspecialchars($_GET['message']);
}

try {
    $pdo = get_pdo_connection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- Mengambil Data Pengguna (Untuk Tampilan Informasi Umum) ---
    // PERBAIKAN DI SINI: Mengubah 'name' menjadi 'nama'
    $stmt_user = $pdo->prepare("SELECT nama, email, phone_number FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        $user_name = $user_data['nama']; // PERBAIKAN DI SINI: Mengubah 'name' menjadi 'nama'
        $user_email = $user_data['email'];
        $user_phone = $user_data['phone_number'] ?? 'N/A'; 
    } else {
        $status_type = 'error';
        $status_message = 'Data pengguna tidak ditemukan.';
    }

    // --- Mengambil Alamat-alamat Pengguna dari Tabel Addresses ---
    // Mengambil semua alamat user, diurutkan agar default muncul lebih dulu
    $stmt_addresses = $pdo->prepare("SELECT id, address_line1, address_line2, city, province, postal_code, phone_number, is_default, type FROM addresses WHERE user_id = ? ORDER BY is_default DESC, type ASC, id ASC");
    $stmt_addresses->execute([$user_id]);
    $addresses = $stmt_addresses->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $status_type = 'error';
    $status_message = "Terjadi kesalahan database: " . $e->getMessage();
}
?>

<div class="container mt-5">
    <h2 class="mb-4">Profil Pengguna</h2>

    <?php if (!empty($status_message)): ?>
        <div class="alert <?php echo ($status_type === 'success') ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
            <?php echo $status_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            Informasi Dasar Akun
        </div>
        <div class="card-body">
            <p><strong>Nama:</strong> <?php echo htmlspecialchars($user_name); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
            <p><strong>Nomor Telepon:</strong> <?php echo htmlspecialchars($user_phone); ?></p>
            </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            Alamat Pengiriman & Penagihan
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                <i class="bi bi-plus-circle"></i> Tambah Alamat Baru
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($addresses)): ?>
                <div class="alert alert-warning" role="alert">
                    Anda belum memiliki alamat tersimpan. Mohon tambahkan alamat untuk kelancaran transaksi.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($addresses as $addr): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 <?php echo ($addr['is_default']) ? 'border-primary' : ''; ?>">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        Alamat <?php echo ucfirst(htmlspecialchars($addr['type'])); ?>
                                        <?php echo ($addr['is_default']) ? '<span class="badge bg-primary ms-2">Default</span>' : ''; ?>
                                    </h6>
                                    <p class="card-text">
                                        <?php echo htmlspecialchars($addr['address_line1']); ?><br>
                                        <?php echo !empty($addr['address_line2']) ? htmlspecialchars($addr['address_line2']) . '<br>' : ''; ?>
                                        <?php echo htmlspecialchars($addr['city']); ?>, <?php echo htmlspecialchars($addr['province']); ?> - <?php echo htmlspecialchars($addr['postal_code']); ?><br>
                                        Telp: <?php echo htmlspecialchars($addr['phone_number']); ?>
                                    </p>
                                    <div class="mt-3">
                                        <?php if (!$addr['is_default']): ?>
                                            <form action="process_address.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="set_default">
                                                <input type="hidden" name="address_id" value="<?php echo $addr['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary me-2">Set Default</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form action="process_address.php" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus alamat ini? Ini mungkin mempengaruhi pesanan yang sudah ada jika alamat ini digunakan.');">
                                            <input type="hidden" name="action" value="delete_address">
                                            <input type="hidden" name="address_id" value="<?php echo $addr['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="process_address.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addAddressModalLabel">Tambah Alamat Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_address">
                        <div class="mb-3">
                            <label for="address_line1" class="form-label">Alamat Baris 1</label>
                            <input type="text" class="form-control" id="address_line1" name="address_line1" required>
                        </div>
                        <div class="mb-3">
                            <label for="address_line2" class="form-label">Alamat Baris 2 (Opsional)</label>
                            <input type="text" class="form-control" id="address_line2" name="address_line2">
                        </div>
                        <div class="mb-3">
                            <label for="city" class="form-label">Kota</label>
                            <input type="text" class="form-control" id="city" name="city" required>
                        </div>
                        <div class="mb-3">
                            <label for="province" class="form-label">Provinsi</label>
                            <input type="text" class="form-control" id="province" name="province" required>
                        </div>
                        <div class="mb-3">
                            <label for="postal_code" class="form-label">Kode Pos</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone_number_addr" class="form-label">Nomor Telepon Alamat</label>
                            <input type="text" class="form-control" id="phone_number_addr" name="phone_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="address_type" class="form-label">Jenis Alamat</label>
                            <select class="form-select" id="address_type" name="type" required>
                                <option value="shipping">Pengiriman</option>
                                <option value="billing">Penagihan</option>
                            </select>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="is_default" name="is_default">
                            <label class="form-check-label" for="is_default">
                                Jadikan Alamat Default
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Alamat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/include/footer.php'; ?>
</div>