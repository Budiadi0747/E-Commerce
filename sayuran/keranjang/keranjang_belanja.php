<?php


session_start();

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    
    header('Location: ../login/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$status_type = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $product_id = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? null;
    $cart_item_id = $_POST['cart_item_id'] ?? null;
    $selected_payment_method = $_POST['payment_method'] ?? 'COD';

    try {
        $pdo = get_pdo_connection();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($action === 'add_to_cart' || $action === 'buy_now') {
            if (empty($product_id) || empty($quantity) || !is_numeric($product_id) || !is_numeric($quantity) || $quantity <= 0) {
                // Path ke produk.php: dari keranjang/, naik satu tingkat ke harvestly_2/, lalu masuk produk/
                header('Location: ../produk/produk.php?status=error&message=' . urlencode('Input produk atau jumlah tidak valid.'));
                exit();
            }

            $stmt_check_stock = $pdo->prepare("SELECT id, name, price, stock FROM produk WHERE id = ? AND status_persetujuan = 'approved' FOR UPDATE");
            $stmt_check_stock->execute([$product_id]);
            $product_data = $stmt_check_stock->fetch(PDO::FETCH_ASSOC);

            if (!$product_data) {
                // Path ke produk.php: dari keranjang/, naik satu tingkat ke harvestly_2/, lalu masuk produk/
                header('Location: ../produk/produk.php?status=error&message=' . urlencode('Produk tidak ditemukan atau tidak tersedia untuk dibeli.'));
                exit();
            }

            if ($product_data['stock'] < $quantity) {
                // Path ke beli_produk.php: dari keranjang/, naik satu tingkat ke harvestly_2/, lalu masuk produk/
                header('Location: ../produk/beli_produk.php?id=' . htmlspecialchars($product_id) . '&status=error&message=' . urlencode('Stok tidak mencukupi untuk jumlah yang diminta. Stok tersedia: ' . $product_data['stock'] . ' kg.'));
                exit();
            }

            $pdo->beginTransaction();

            if ($action === 'add_to_cart') {
                $stmt_check_cart = $pdo->prepare("SELECT quantity FROM keranjang WHERE user_id = ? AND product_id = ?");
                $stmt_check_cart->execute([$user_id, $product_id]);
                $existing_item = $stmt_check_cart->fetch(PDO::FETCH_ASSOC);

                if ($existing_item) {
                    $new_cart_quantity = $existing_item['quantity'] + $quantity;
                    if ($new_cart_quantity > $product_data['stock']) {
                        $pdo->rollBack();
                        // Path ke beli_produk.php: dari keranjang/, naik satu tingkat ke harvestly_2/, lalu masuk produk/
                        header('Location: ../produk/beli_produk.php?id=' . htmlspecialchars($product_id) . '&status=error&message=' . urlencode('Jumlah total di keranjang melebihi stok yang tersedia.'));
                        exit();
                    }
                    $stmt_update_cart = $pdo->prepare("UPDATE keranjang SET quantity = ? WHERE user_id = ? AND product_id = ?");
                    $stmt_update_cart->execute([$new_cart_quantity, $user_id, $product_id]);
                    $message = 'Jumlah produk di keranjang telah diperbarui.';
                } else {
                    $stmt_add_cart = $pdo->prepare("INSERT INTO keranjang (user_id, product_id, quantity) VALUES (?, ?, ?)");
                    $stmt_add_cart->execute([$user_id, $product_id, $quantity]);
                    $message = 'Produk berhasil ditambahkan ke keranjang.';
                }
                $pdo->commit();
                // Path ke keranjang.php: keranjang.php berada di folder yang sama (keranjang/)
                header('Location: keranjang.php?status=success&message=' . urlencode($message));
                exit();

            } elseif ($action === 'buy_now') {
                $stmt_shipping_address_id = $pdo->prepare("SELECT id FROM addresses WHERE user_id = ? AND is_default = 1 AND type = 'shipping'");
                $stmt_shipping_address_id->execute([$user_id]);
                $shipping_address_id = $stmt_shipping_address_id->fetchColumn();

                if (!$shipping_address_id) {
                    $pdo->rollBack();
                    // Path ke profile.php: dari keranjang/, naik satu tingkat ke harvestly_2/, lalu masuk profile/
                    header('Location: ../profile/profile.php?status=error&message=' . urlencode('Mohon tambahkan atau pilih alamat pengiriman default di profil Anda.'));
                    exit();
                }

                $stmt_billing_address_id = $pdo->prepare("SELECT id FROM addresses WHERE user_id = ? AND is_default = 1 AND type = 'billing'");
                $stmt_billing_address_id->execute([$user_id]);
                $billing_address_id = $stmt_billing_address_id->fetchColumn();
                if (!$billing_address_id) {
                    $billing_address_id = $shipping_address_id;
                }

                $new_stock = $product_data['stock'] - $quantity;
                $stmt_update_stock = $pdo->prepare("UPDATE produk SET stock = ? WHERE id = ?");
                $stmt_update_stock->execute([$new_stock, $product_id]);

                $total_price_for_this_item = $product_data['price'] * $quantity;
                
                $stmt_insert_order = $pdo->prepare("
                    INSERT INTO orders 
                    (user_id, total_amount, payment_method, status, shipping_address_id, billing_address_id) 
                    VALUES (?, ?, ?, 'pending', ?, ?)
                "); 
                $stmt_insert_order->execute([
                    $user_id, 
                    $total_price_for_this_item, 
                    $selected_payment_method,
                    $shipping_address_id, 
                    $billing_address_id  
                ]);
                $order_id = $pdo->lastInsertId();

                $stmt_insert_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_order) VALUES (?, ?, ?, ?)");
                $stmt_insert_item->execute([$order_id, $product_id, $quantity, $product_data['price']]);

                $pdo->commit();
                // **PERBAIKAN DI SINI:** Path ke konfirmasi_pembelian.php: dari keranjang/, naik satu tingkat ke harvestly_2/, lalu masuk proses/
                header('Location: ../proses/konfirmasi_pembelian.php?status=success&message=' . urlencode('Pembelian langsung berhasil! Pesanan Anda telah dibuat dengan ID: ' . $order_id));
                exit();
            }
        } elseif ($action === 'update_cart_quantity') {
            if (empty($cart_item_id) || !is_numeric($cart_item_id) || empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
                // Path ke keranjang.php: keranjang.php berada di folder yang sama (keranjang/)
                header('Location: keranjang.php?status=error&message=' . urlencode('Input update keranjang tidak valid.'));
                exit();
            }

            $pdo->beginTransaction();

            $stmt_get_product_id = $pdo->prepare("SELECT product_id FROM keranjang WHERE id = ? AND user_id = ? FOR UPDATE");
            $stmt_get_product_id->execute([$cart_item_id, $user_id]);
            $item_product_id = $stmt_get_product_id->fetchColumn();

            if (!$item_product_id) {
                $pdo->rollBack();
                // Path ke keranjang.php: keranjang.php berada di folder yang sama (keranjang/)
                header('Location: keranjang.php?status=error&message=' . urlencode('Item keranjang tidak ditemukan.'));
                exit();
            }

            $stmt_check_stock = $pdo->prepare("SELECT stock FROM produk WHERE id = ? FOR UPDATE");
            $stmt_check_stock->execute([$item_product_id]);
            $product_stock = $stmt_check_stock->fetchColumn();

            if ($product_stock < $quantity) {
                $pdo->rollBack();
                // Path ke keranjang.php: keranjang.php berada di folder yang sama (keranjang/)
                header('Location: keranjang.php?status=error&message=' . urlencode('Jumlah melebihi stok yang tersedia. Stok: ' . $product_stock . ' kg.'));
                exit();
            }

            $stmt_update = $pdo->prepare("UPDATE keranjang SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt_update->execute([$quantity, $cart_item_id, $user_id]);
            
            $pdo->commit();
            // Path ke keranjang.php: keranjang.php berada di folder yang sama (keranjang/)
            header('Location: keranjang.php?status=success&message=' . urlencode('Jumlah produk di keranjang berhasil diperbarui.'));
            exit();

        } elseif ($action === 'remove_from_cart') {
            if (empty($cart_item_id) || !is_numeric($cart_item_id)) {
                // Path ke keranjang.php: keranjang.php berada di folder yang sama (keranjang/)
                header('Location: keranjang.php?status=error&message=' . urlencode('ID item keranjang tidak valid.'));
                exit();
            }
            $stmt_delete = $pdo->prepare("DELETE FROM keranjang WHERE id = ? AND user_id = ?");
            $stmt_delete->execute([$cart_item_id, $user_id]);
            // Path ke keranjang.php: keranjang.php berada di folder yang sama (keranjang/)
            header('Location: keranjang.php?status=success&message=' . urlencode('Produk berhasil dihapus dari keranjang.'));
            exit();

        } elseif ($action === 'checkout_cart') {
            $pdo->beginTransaction();

            $stmt_cart_items = $pdo->prepare("
                SELECT 
                    k.product_id, 
                    k.quantity, 
                    p.price, 
                    p.stock 
                FROM keranjang k
                JOIN produk p ON k.product_id = p.id
                WHERE k.user_id = ? FOR UPDATE
            ");
            $stmt_cart_items->execute([$user_id]);
            $items_to_checkout = $stmt_cart_items->fetchAll(PDO::FETCH_ASSOC);

            if (empty($items_to_checkout)) {
                $pdo->rollBack();
                // Path ke keranjang.php: keranjang.php berada di folder yang sama (keranjang/)
                header('Location: keranjang.php?status=error&message=' . urlencode('Keranjang Anda kosong. Tidak ada yang bisa di-checkout.'));
                exit();
            }

            $total_order_amount = 0;
            $products_to_update = [];

            foreach ($items_to_checkout as $item) {
                if ($item['stock'] < $item['quantity']) {
                    $pdo->rollBack();
                    // Path ke keranjang.php: keranjang.php berada di folder yang sama (keranjang/)
                    header('Location: keranjang.php?status=error&message=' . urlencode('Stok tidak mencukupi untuk produk: ' . $item['product_id'] . '. Stok tersedia: ' . $item['stock'] . ' kg.'));
                    exit();
                }
                $total_order_amount += $item['quantity'] * $item['price'];
                
                $products_to_update[] = [
                    'id' => $item['product_id'],
                    'new_stock' => $item['stock'] - $item['quantity']
                ];
            }

            $stmt_shipping_address_id = $pdo->prepare("SELECT id FROM addresses WHERE user_id = ? AND is_default = 1 AND type = 'shipping'");
            $stmt_shipping_address_id->execute([$user_id]);
            $shipping_address_id = $stmt_shipping_address_id->fetchColumn();

            if (!$shipping_address_id) {
                $pdo->rollBack();
                // Path ke profile.php: dari keranjang/, naik satu tingkat ke harvestly_2/, lalu masuk profile/
                header('Location: ../profile/profile.php?status=error&message=' . urlencode('Mohon tambahkan atau pilih alamat pengiriman default di profil Anda sebelum checkout.'));
                exit();
            }

            $stmt_billing_address_id = $pdo->prepare("SELECT id FROM addresses WHERE user_id = ? AND is_default = 1 AND type = 'billing'");
            $stmt_billing_address_id->execute([$user_id]);
            $billing_address_id = $stmt_billing_address_id->fetchColumn();
            if (!$billing_address_id) {
                $billing_address_id = $shipping_address_id;
            }
            
            $stmt_insert_order = $pdo->prepare("
                INSERT INTO orders 
                (user_id, total_amount, payment_method, status, shipping_address_id, billing_address_id) 
                VALUES (?, ?, ?, 'pending', ?, ?)
            ");
            $stmt_insert_order->execute([
                $user_id, 
                $total_order_amount, 
                $selected_payment_method,
                $shipping_address_id, 
                $billing_address_id
            ]);
            $order_id = $pdo->lastInsertId();

            $stmt_insert_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_order) VALUES (?, ?, ?, ?)");
            $stmt_update_product_stock = $pdo->prepare("UPDATE produk SET stock = ? WHERE id = ?");

            foreach ($items_to_checkout as $item) {
                $stmt_insert_item->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                $stmt_update_product_stock->execute([($item['stock'] - $item['quantity']), $item['product_id']]);
            }

            $stmt_clear_cart = $pdo->prepare("DELETE FROM keranjang WHERE user_id = ?");
            $stmt_clear_cart->execute([$user_id]);

            $pdo->commit();
            // **PERBAIKAN DI SINI:** Path ke konfirmasi_pembelian.php: dari keranjang/, naik satu tingkat ke harvestly_2/, lalu masuk proses/
            header('Location: ../proses/konfirmasi_pembelian.php?status=success&message=' . urlencode('Checkout berhasil! Pesanan Anda telah dibuat dengan ID: ' . $order_id));
            exit();

        } else {
            // Path ke produk.php: dari keranjang/, naik satu tingkat ke harvestly_2/, lalu masuk produk/
            header('Location: ../produk/produk.php?status=error&message=' . urlencode('Aksi tidak dikenal.'));
            exit();
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "Terjadi kesalahan database: " . $e->getMessage();
        
        if ($action === 'buy_now' || $action === 'add_to_cart') {
            // Path ke beli_produk.php: dari keranjang/, naik satu tingkat ke harvestly_2/, lalu masuk produk/
            header('Location: ../produk/beli_produk.php?id=' . htmlspecialchars($product_id) . '&status=error&message=' . urlencode($message));
        } elseif ($action === 'checkout_cart' || $action === 'update_cart_quantity' || $action === 'remove_from_cart') {
            // Path ke keranjang.php: keranjang.php berada di folder yang sama (keranjang/)
            header('Location: keranjang.php?status=error&message=' . urlencode($message));
        } else {
            // Path ke produk.php: dari keranjang/, naik satu tingkat ke harvestly_2/, lalu masuk produk/
            header('Location: ../produk/produk.php?status=error&message=' . urlencode($message));
        }
        exit();
    }
} else {
    // Path ke produk.php: dari keranjang/, naik satu tingkat ke harvestly_2/, lalu masuk produk/
    header('Location: ../produk/produk.php?status=error&message=' . urlencode('Akses tidak diizinkan.'));
    exit();
}