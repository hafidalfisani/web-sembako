-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 09, 2025 at 09:37 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce_sembako_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id_detail` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id_detail`, `id_pesanan`, `id_produk`, `jumlah`, `subtotal`) VALUES
(1, 1, 4, 3, 55500.00),
(2, 2, 3, 1, 16000.00),
(3, 3, 3, 1, 16000.00),
(4, 3, 4, 1, 18500.00),
(5, 3, 7, 3, 46500.00),
(6, 4, 3, 1, 16000.00),
(7, 5, 3, 1, 16000.00),
(8, 6, 2, 1, 30000.00),
(9, 7, 3, 1, 16000.00),
(10, 8, 2, 3, 90000.00),
(11, 8, 3, 1, 16000.00),
(12, 9, 3, 1, 16000.00),
(13, 10, 3, 1, 16000.00),
(14, 11, 2, 1, 30000.00),
(15, 12, 3, 4, 64000.00),
(16, 13, 3, 2, 32000.00),
(17, 14, 2, 1, 30000.00),
(18, 15, 2, 1, 30000.00),
(19, 16, 3, 1, 16000.00),
(20, 17, 4, 1, 18500.00),
(21, 18, 2, 1, 30000.00),
(22, 19, 3, 1, 16000.00);

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id_pembayaran` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `metode` enum('transfer','manual') NOT NULL,
  `bukti` varchar(255) DEFAULT NULL,
  `tanggal_bayar` datetime NOT NULL,
  `status_verifikasi` enum('menunggu','terverifikasi','ditolak') NOT NULL,
  `bukti_pembayaran` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pembayaran`
--

INSERT INTO `pembayaran` (`id_pembayaran`, `id_pesanan`, `metode`, `bukti`, `tanggal_bayar`, `status_verifikasi`, `bukti_pembayaran`) VALUES
(1, 1, 'manual', NULL, '2025-11-23 05:30:03', 'terverifikasi', NULL),
(2, 2, 'manual', NULL, '2025-11-23 06:49:41', 'terverifikasi', NULL),
(3, 3, 'manual', NULL, '2025-11-23 06:52:14', 'terverifikasi', NULL),
(4, 4, 'transfer', NULL, '2025-11-23 06:56:12', 'terverifikasi', '4_1763878000.jpg'),
(5, 5, 'transfer', NULL, '2025-11-23 07:09:52', 'terverifikasi', '5_1763878201.png'),
(6, 6, 'transfer', NULL, '2025-11-23 07:15:20', 'terverifikasi', '6_1763878680.png'),
(7, 7, 'transfer', NULL, '2025-11-23 07:38:57', 'terverifikasi', NULL),
(8, 8, 'transfer', NULL, '2025-11-23 07:49:43', 'terverifikasi', NULL),
(9, 9, 'transfer', NULL, '2025-11-24 08:25:59', 'terverifikasi', NULL),
(10, 10, 'transfer', NULL, '2025-11-24 14:13:17', 'terverifikasi', NULL),
(11, 11, 'manual', NULL, '2025-11-26 06:31:28', 'terverifikasi', NULL),
(12, 12, 'manual', NULL, '2025-11-26 08:36:57', 'terverifikasi', NULL),
(13, 13, 'manual', NULL, '2025-11-26 08:44:45', 'terverifikasi', NULL),
(14, 14, 'manual', NULL, '2025-11-26 08:45:45', 'terverifikasi', NULL),
(15, 15, 'manual', NULL, '2025-11-26 08:47:54', 'terverifikasi', NULL),
(16, 16, 'manual', NULL, '2025-11-26 08:52:12', 'terverifikasi', NULL),
(17, 17, 'transfer', NULL, '2025-11-26 08:57:27', 'terverifikasi', NULL),
(18, 18, 'transfer', NULL, '2025-11-26 08:57:40', 'ditolak', NULL),
(19, 19, 'transfer', NULL, '2025-11-28 14:46:42', 'menunggu', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pengantaran`
--

CREATE TABLE `pengantaran` (
  `id_pengantaran` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `alamat` text NOT NULL,
  `tanggal_kirim` datetime DEFAULT NULL,
  `status_kirim` enum('menunggu','diproses','dikirim','selesai') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengantaran`
--

INSERT INTO `pengantaran` (`id_pengantaran`, `id_pesanan`, `alamat`, `tanggal_kirim`, `status_kirim`) VALUES
(1, 1, 'gotong royong', NULL, 'menunggu'),
(2, 2, 'batu', NULL, 'menunggu'),
(3, 3, 'gang', NULL, 'menunggu'),
(4, 4, 'dfges', NULL, 'menunggu'),
(5, 5, 'df', NULL, ''),
(6, 6, 'aef', NULL, 'menunggu'),
(7, 7, 'wf', NULL, 'menunggu'),
(8, 8, 'liouiytdfg', NULL, 'menunggu'),
(9, 9, 'weewer', NULL, 'menunggu'),
(10, 10, 'jfer', NULL, ''),
(11, 11, 'jalan jalan', NULL, ''),
(12, 12, 'ghjk', NULL, ''),
(13, 13, 'baturaja', NULL, 'menunggu'),
(14, 14, 'hhh', NULL, 'menunggu'),
(15, 15, 'wwww', NULL, ''),
(16, 16, 'ddd', NULL, 'selesai'),
(17, 17, 'ff', NULL, 'selesai'),
(18, 18, 'ttt', NULL, ''),
(19, 19, 'asd', NULL, 'menunggu');

-- --------------------------------------------------------

--
-- Table structure for table `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `tanggal_pesan` datetime NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `status_pesanan` enum('baru','diproses','dikirim','selesai','dibatalkan') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `id_user`, `tanggal_pesan`, `total_harga`, `status_pesanan`) VALUES
(1, 1, '2025-11-23 05:30:03', 55500.00, 'dikirim'),
(2, 1, '2025-11-23 06:49:41', 16000.00, 'dikirim'),
(3, 1, '2025-11-23 06:52:14', 81000.00, 'dikirim'),
(4, 1, '2025-11-23 06:56:12', 16000.00, 'selesai'),
(5, 1, '2025-11-23 07:09:52', 16000.00, 'dikirim'),
(6, 1, '2025-11-23 07:15:20', 30000.00, 'selesai'),
(7, 1, '2025-11-23 07:38:57', 16000.00, 'dikirim'),
(8, 1, '2025-11-23 07:49:43', 106000.00, 'dikirim'),
(9, 1, '2025-11-24 08:25:59', 16000.00, 'dikirim'),
(10, 1, '2025-11-24 14:13:17', 16000.00, 'dikirim'),
(11, 1, '2025-11-26 06:31:28', 30000.00, 'dikirim'),
(12, 1, '2025-11-26 08:36:57', 64000.00, 'dikirim'),
(13, 1, '2025-11-26 08:44:45', 32000.00, 'dikirim'),
(14, 1, '2025-11-26 08:45:45', 30000.00, 'dikirim'),
(15, 1, '2025-11-26 08:47:54', 30000.00, 'dikirim'),
(16, 1, '2025-11-26 08:52:12', 16000.00, 'dikirim'),
(17, 1, '2025-11-26 08:57:27', 18500.00, 'dikirim'),
(18, 1, '2025-11-26 08:57:40', 30000.00, ''),
(19, 1, '2025-11-28 14:46:42', 16000.00, 'baru');

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `id_produk` int(11) NOT NULL,
  `nama_produk` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) NOT NULL,
  `satuan` varchar(20) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id_produk`, `nama_produk`, `deskripsi`, `harga`, `stok`, `satuan`, `gambar`) VALUES
(1, 'Beras Premium 5kg', 'Beras kualitas terbaik, pulen.', 65000.00, 50, 'kg', 'beras.jpg'),
(2, 'Minyak Goreng 2L', 'Minyak kelapa sawit kemasan.', 30000.00, 100, 'liter', 'minyak.jpg'),
(3, 'Gula Pasir 1kg', 'Gula murni kristal putih.', 16000.00, 80, 'kg', 'gula.jpg'),
(4, 'Kopi Bubuk 200g', 'Kopi robusta bubuk murni.', 18500.00, 60, 'bungkus', 'kopi.jpg'),
(5, 'Tepung Terigu 1kg', 'Tepung terigu serbaguna.', 12000.00, 120, 'kg', 'tepung.jpg'),
(6, 'Telur Ayam 1kg', 'Telur ayam negeri segar kualitas A.', 28000.00, 75, 'kg', 'telur.jpg'),
(7, 'Susu Kental Manis', 'Susu kental manis kaleng 490g.', 15500.00, 90, 'kaleng', 'susu.jpg'),
(8, 'Mie Instan Kuah (5 pcs)', 'Satu paket mie instan rasa kuah.', 14000.00, 200, 'paket', 'mie.jpg'),
(109, 'Kecap Manis Botol', 'Kecap manis kental terbuat dari gula kelapa pilihan. Wajib ada untuk masakan Indonesia.', 22500.00, 55, NULL, 'kecap.jpg'),
(110, 'Ikan Sarden Kaleng 425g', 'Ikan sarden segar dalam saus tomat yang kaya rasa. Praktis dan bergizi.', 18000.00, 35, NULL, 'sarden.jpg'),
(111, 'Susu Kental Manis sachet', 'Susu kental manis, cocok untuk campuran minuman, membuat kue, dan toping makanan.', 15500.00, 40, NULL, 'skm_sachet.jpg'),
(112, 'Margarin Serbaguna 200g', 'Margarin dengan aroma butter yang harum, baik untuk olesan dan memasak.', 7000.00, 80, NULL, 'margarin.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pelanggan') NOT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `tanggal_registrasi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `nama`, `email`, `password`, `role`, `no_hp`, `alamat`, `tanggal_registrasi`) VALUES
(1, 'jaki', 'jaki@gmail.com', '$2y$10$hGNjf7JsWZjwuvsjCe21kuiEkCNsnroxskXAnY0n6jgcr/HotU45q', 'pelanggan', NULL, NULL, '2025-11-23 11:13:52'),
(2, 'Admin Utama', 'admin@emailanda.com', '$2y$10$kpNJj0tbHDdKojiGkJb9t.K9nYW6ipdagQPfh6ZOUZ1iywGgBKbpq', 'admin', NULL, NULL, '2025-11-24 20:09:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `id_pesanan` (`id_pesanan`);

--
-- Indexes for table `pengantaran`
--
ALTER TABLE `pengantaran`
  ADD PRIMARY KEY (`id_pengantaran`),
  ADD KEY `id_pesanan` (`id_pesanan`);

--
-- Indexes for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id_produk`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `pengantaran`
--
ALTER TABLE `pengantaran`
  MODIFY `id_pengantaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `id_produk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`),
  ADD CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`);

--
-- Constraints for table `pengantaran`
--
ALTER TABLE `pengantaran`
  ADD CONSTRAINT `pengantaran_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`);

--
-- Constraints for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
