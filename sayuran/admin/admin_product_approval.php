<?php


session_start();
require_once __DIR__ . '/../config/db.php'; // Path ini sudah benar

// PERBAIKAN DI SINI: admin_header.php ada di folder yang sama (admin/)
require_once __DIR__ . '/admin_header.php'; 

// DEBUGGING START: Ini akan menampilkan semua error PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// DEBUGGING END

// Pastikan user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // PERBAIKAN DI SINI: Arahkan ke login/login.php
    header('Location: ../login/login.php');
    exit();
}

$page_title = "Persetujuan Produk - Admin KangSayur";

$error_message = '';
$success_message = '';

// Tangani perubahan status produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $product_id = $_POST['product_id'] ?? null;
    $new_status = $_POST['new_status'] ?? null;

    if ($product_id && in_array($new_status, ['approved', 'rejected'])) {
        try {
            $pdo = get_pdo_connection();
            // Penting untuk debugging PDO: Aktifkan mode error untuk melempar PDOException
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
            
            $stmt = $pdo->prepare("UPDATE produk SET status_persetujuan = ? WHERE id = ?");
            
            // DEBUG: Log parameter yang dikirim untuk update
            // echo "Attempting to update product ID: " . htmlspecialchars($product_id) . " to status: " . htmlspecialchars($new_status) . "<br>"; 

            if ($stmt->execute([$new_status, $product_id])) {
                $success_message = "Status produk berhasil diperbarui.";
            } else {
                // Tambahkan detail error jika eksekusi gagal tanpa melempar exception (jarang terjadi jika ATTR_ERRMODE = EXCEPTION)
                $error_info = $stmt->errorInfo();
                $error_message = "Gagal memperbarui status produk. SQLSTATE: " . $error_info[0] . ", Code: " . $error_info[1] . ", Message: " . $error_info[2];
            }
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan database saat update: " . $e->getMessage();
            // DEBUG: Tampilkan SQL query jika error (hati-hati di produksi)
            // echo "SQL Error (Update): " . $stmt->queryString . "<br>"; 
        }
    } else {
        $error_message = "Input tidak valid untuk pembaruan status.";
    }
}

// Ambil daftar produk
$products = [];
try {
    $pdo = get_pdo_connection();
    // Penting untuk debugging PDO: Aktifkan mode error untuk melempar PDOException
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

    // Query untuk mengambil SEMUA produk dengan JOIN ke users
    $sql_query = "
        SELECT p.id, p.name, p.description, p.price, p.stock, p.status_persetujuan, p.image_url, u.username AS seller_username
        FROM produk p
        INNER JOIN users u ON p.seller_id = u.id
        ORDER BY p.created_at DESC
    ";
    
    $stmt_products = $pdo->prepare($sql_query);
    $stmt_products->execute();
    $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

    // DEBUG: Tampilkan jumlah produk yang ditemukan
    //echo "Jumlah produk ditemukan: " . count($products) . "<br>"; 
    // DEBUG: Tampilkan semua data produk yang ditemukan
    //echo "<pre>"; print_r($products); echo "</pre>"; 

} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan database saat mengambil produk: " . $e->getMessage();
    // DEBUG: Tampilkan SQL yang menyebabkan error (hati-hati di produksi)
    // echo "SQL Error (Select): " . $sql_query . "<br>";
}
?>

<div class="container mt-5">
    <h2 class="mb-4">Persetujuan Produk</h2>

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

    <?php if (empty($products)): ?>
        <div class="alert alert-info text-center" role="alert">
            Tidak ada produk yang perlu persetujuan atau semua produk sudah disetujui.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Gambar</th>
                        <th>Nama Produk</th>
                        <th>Penjual</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['id']); ?></td>
                            <td>
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                <?php else: ?>
                                    Tidak ada gambar
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['seller_username']); ?></td>
                            <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($product['stock']); ?></td>
                            <td>
                                <span class="badge 
                                    <?php 
                                        if ($product['status_persetujuan'] == 'pending') echo 'bg-warning text-dark';
                                        elseif ($product['status_persetujuan'] == 'approved') echo 'bg-success';
                                        elseif ($product['status_persetujuan'] == 'rejected') echo 'bg-danger';
                                        else echo 'bg-secondary';
                                    ?>">
                                    <?php echo htmlspecialchars(ucfirst($product['status_persetujuan'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($product['status_persetujuan'] == 'pending'): ?>
                                    <form action="admin_product_approval.php" method="POST" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                        <select name="new_status" class="form-select form-select-sm d-inline-block w-auto me-2">
                                            <option value="approved">Setujui</option>
                                            <option value="rejected">Tolak</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                                    </form>
                                <?php else: ?>
                                    Sudah diproses
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../include/footer.php'; ?>