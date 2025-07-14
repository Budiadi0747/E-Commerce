-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 14 Jul 2025 pada 16.16
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sayuran`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `type` enum('shipping','billing') DEFAULT 'shipping',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `addresses`
--

INSERT INTO `addresses` (`id`, `user_id`, `address_line1`, `address_line2`, `city`, `province`, `postal_code`, `phone_number`, `is_default`, `type`, `created_at`) VALUES
(1, 2, 'perum GKA', '', 'karawang', 'jawabarat', '17114', '081310921215', 1, 'shipping', '2025-05-31 06:20:12'),
(2, 2, 'kondang', '', 'jakarta', 'jakarta', '17114', '0817761111', 0, 'shipping', '2025-06-01 01:10:46'),
(3, 2, 'perum GKA', '', 'jakarta', 'jawabarat', '17114', '081310921215', 0, 'shipping', '2025-06-01 01:16:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Sayuran', 'Berbagai jenis sayuran segar'),
(2, 'Buah-buahan', 'Buah-buahan lokal dan impor'),
(3, 'Daging & Unggas', 'Produk daging segar dan olahan'),
(4, 'Biji-bijian', 'Beras, kacang-kacangan, dan biji-bijian lainnya');

-- --------------------------------------------------------

--
-- Struktur dari tabel `keranjang`
--

CREATE TABLE `keranjang` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_pembelian_produk`
--

CREATE TABLE `log_pembelian_produk` (
  `id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `jumlah_beli` int(11) NOT NULL,
  `tanggal_beli` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_tambah_produk`
--

CREATE TABLE `log_tambah_produk` (
  `id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `stok_ditambahkan` int(11) NOT NULL,
  `tanggal_tambah` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','completed','cancelled') DEFAULT 'pending',
  `order_date` datetime DEFAULT current_timestamp(),
  `shipping_address_id` int(11) DEFAULT NULL,
  `billing_address_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `payment_method` varchar(100) NOT NULL DEFAULT '''COD''' COMMENT 'Metode pembayaran pesanan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `order_date`, `shipping_address_id`, `billing_address_id`, `notes`, `payment_method`) VALUES
(1, 2, 10000.00, 'completed', '2025-05-31 13:20:12', 1, 1, NULL, '\'COD\''),
(2, 2, 55000.00, 'shipped', '2025-06-01 08:10:46', 2, 2, NULL, '\'COD\''),
(3, 2, 13000.00, 'completed', '2025-06-01 08:16:40', 3, 3, NULL, '\'COD\''),
(4, 2, 2000.00, 'completed', '2025-06-01 13:52:48', 1, 1, NULL, 'COD'),
(5, 2, 2000.00, 'shipped', '2025-06-01 13:59:10', 1, 1, NULL, 'COD'),
(6, 2, 2000.00, 'shipped', '2025-06-01 14:00:06', 1, 1, NULL, 'Transfer Bank'),
(7, 2, 2000.00, 'completed', '2025-06-01 14:08:08', 1, 1, NULL, 'COD'),
(8, 2, 2000.00, 'completed', '2025-06-01 14:08:18', 1, 1, NULL, 'COD'),
(9, 2, 22000.00, 'pending', '2025-06-01 14:14:21', 1, 1, NULL, 'COD'),
(10, 2, 2000.00, 'pending', '2025-06-01 14:14:33', 1, 1, NULL, 'Transfer Bank'),
(11, 2, 2000.00, 'pending', '2025-06-01 14:14:49', 1, 1, NULL, 'Transfer Bank'),
(12, 2, 6000.00, 'pending', '2025-06-01 14:15:12', 1, 1, NULL, 'Transfer Bank'),
(13, 2, 45000.00, 'pending', '2025-06-01 14:33:01', 1, 1, NULL, 'COD'),
(14, 2, 15000.00, 'pending', '2025-06-13 20:34:21', 1, 1, NULL, 'COD'),
(15, 2, 45000.00, 'pending', '2025-07-04 05:55:28', 1, 1, NULL, 'COD'),
(16, 2, 2000.00, 'pending', '2025-07-04 05:58:59', 1, 1, NULL, 'COD'),
(17, 2, 15000.00, 'pending', '2025-07-04 06:14:39', 1, 1, NULL, 'COD'),
(18, 2, 2000.00, 'pending', '2025-07-04 06:15:11', 1, 1, NULL, 'Transfer Bank'),
(19, 2, 20000.00, 'pending', '2025-07-04 06:16:07', 1, 1, NULL, 'COD'),
(20, 2, 2000.00, 'pending', '2025-07-04 06:16:26', 1, 1, NULL, 'Transfer Bank'),
(21, 2, 15000.00, 'pending', '2025-07-04 21:01:11', 1, 1, NULL, 'COD'),
(22, 2, 2000.00, 'pending', '2025-07-04 21:01:26', 1, 1, NULL, 'COD'),
(23, 2, 1500.00, 'completed', '2025-07-10 06:02:02', 1, 1, NULL, 'COD'),
(24, 2, 1500.00, 'completed', '2025-07-10 06:03:00', 1, 1, NULL, 'COD');

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_order` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price_at_order`) VALUES
(5, 4, 12, '', 1, 2000.00),
(6, 5, 12, '', 1, 2000.00),
(7, 6, 12, '', 1, 2000.00),
(8, 7, 12, '', 1, 2000.00),
(9, 8, 12, '', 1, 2000.00),
(10, 9, 12, '', 11, 2000.00),
(11, 10, 12, '', 1, 2000.00),
(12, 11, 12, '', 1, 2000.00),
(13, 12, 12, '', 3, 2000.00),
(14, 13, 13, '', 3, 15000.00),
(15, 14, 13, '', 1, 15000.00),
(16, 15, 13, '', 3, 15000.00),
(17, 16, 12, '', 1, 2000.00),
(18, 17, 18, '', 1, 15000.00),
(19, 18, 16, '', 1, 2000.00),
(20, 19, 15, '', 2, 10000.00),
(21, 20, 12, '', 1, 2000.00),
(22, 21, 13, '', 1, 15000.00),
(23, 22, 12, '', 1, 2000.00),
(24, 23, 23, '', 1, 1500.00),
(25, 24, 23, '', 1, 1500.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_persetujuan` enum('pending','approved','rejected') DEFAULT 'pending' COMMENT 'Status persetujuan produk oleh admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id`, `name`, `description`, `price`, `stock`, `category_id`, `seller_id`, `image_url`, `created_at`, `status_persetujuan`) VALUES
(12, 'kangkung', 'hijau', 2000.00, 171, 1, 3, 'assets/img/produk/prod_683be12659ff8_kangkung.jpg', '2025-06-01 05:12:06', 'approved'),
(13, 'Beras', 'RojoLele', 15000.00, 992, 4, 3, 'assets/img/produk/prod_683c00f84388a_beras.png', '2025-06-01 07:27:52', 'approved'),
(14, 'Pete', 'Tua', 500.00, 100, 1, 3, 'assets/img/produk/prod_683c011066d6b_pete.jpg', '2025-06-01 07:28:16', 'approved'),
(15, 'Salak Pondoh', 'manis', 10000.00, 898, 2, 3, 'assets/img/produk/prod_683c012ad3021_salak.png', '2025-06-01 07:28:42', 'approved'),
(16, 'Terong Ungu', 'Segar MAnis', 2000.00, 399, 1, 3, 'assets/img/produk/prod_683c01413d22b_terong.jpeg', '2025-06-01 07:29:05', 'approved'),
(18, 'nanas', 'manis', 15000.00, 999, 2, 3, 'assets/img/produk/prod_684c29930727a_Nanas.jpg', '2025-06-13 13:37:23', 'approved'),
(23, 'daun bawang', 'segar', 1500.00, 98, 1, 3, 'assets/img/produk/prod_686804c191518.jpg', '2025-07-04 16:43:45', 'approved');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `role` enum('admin','penjual','pembeli') DEFAULT 'pembeli',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `phone_number`, `nama`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$SFmuuGmOk0VPF6uWEvGQ2OZbXi5KuSBOthENV3lYQQBSrcXMwUEs6', 'diecampuss@gmail.com', '081310921215', 'Administrator', 'admin', '2025-05-31 00:46:03'),
(2, 'budipembelibanyak', '$2y$10$ZrJ/sYlRIaoThirMKBS3J.Dzjb99FnL2mWZtrfkom3NaW7vyPiCRC', 'budiadi171116@gmail.com', '081210921215', 'budi adi saputra', 'pembeli', '2025-05-31 00:55:42'),
(3, 'simba', '$2y$10$2c6fvGP.mBF8IN/t8e6bHehcQtNmFrHYfNBWVnnP72fntuwxReTXe', 'naylarhmadina270605@gmail.com', '0811112222', 'simba the lion king', 'penjual', '2025-06-01 02:47:09'),
(4, 'maman1', '$2y$10$q4TSkq6znWYmDmNzaFr9SOjjm/L2DujK6/WibFpf1izT0dtyI0Sx.', 'maman@gmail.com', '081176625111', 'maman', 'pembeli', '2025-06-01 07:17:47');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeks untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `log_pembelian_produk`
--
ALTER TABLE `log_pembelian_produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produk_id` (`produk_id`);

--
-- Indeks untuk tabel `log_tambah_produk`
--
ALTER TABLE `log_tambah_produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produk_id` (`produk_id`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `shipping_address_id` (`shipping_address_id`),
  ADD KEY `billing_address_id` (`billing_address_id`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `log_pembelian_produk`
--
ALTER TABLE `log_pembelian_produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `log_tambah_produk`
--
ALTER TABLE `log_tambah_produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  ADD CONSTRAINT `keranjang_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `keranjang_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `log_pembelian_produk`
--
ALTER TABLE `log_pembelian_produk`
  ADD CONSTRAINT `log_pembelian_produk_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `log_tambah_produk`
--
ALTER TABLE `log_tambah_produk`
  ADD CONSTRAINT `log_tambah_produk_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`shipping_address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`billing_address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `produk` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
