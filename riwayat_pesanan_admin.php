<?php
session_start();

// --- DEBUGGING: AKTIFKAN TAMPILAN ERROR PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --------------------------------------------------

// Akses koneksi
include('connection.php'); 

// Pastikan koneksi berhasil sebelum melanjutkan
if (!isset($conn) || $conn->connect_error) {
    die("❌ KONEKSI DATABASE GAGAL. Pesan Error: " . ($conn->connect_error ?? "Variabel koneksi (\$conn) tidak ditemukan."));
}

// ------------------------------------------------------------------
// 1. CEK LOGIN
// ------------------------------------------------------------------
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$id_user = $_SESSION['id_user'];

// ------------------------------------------------------------------
// 2. LOGIKA PERUBAHAN STATUS PENGIRIMAN (Aksi Tombol "Kirim") - HANYA UNTUK ADMIN
// ------------------------------------------------------------------
$status_message = '';
if ($is_admin && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_pesanan']) && isset($_POST['action_kirim'])) {
    $id_pesanan = clean_input($_POST['id_pesanan']);
    $action_kirim = clean_input($_POST['action_kirim']);
    
    $status_kirim = '';
    $status_pesanan = '';

    if ($action_kirim == 'kirim') {
        $status_kirim = 'terkirim';
        $status_pesanan = 'dikirim';
    }

    if ($status_kirim) {
        $conn->begin_transaction();
        try {
            $sql_kirim = "UPDATE pengantaran SET status_kirim = ? WHERE id_pesanan = ?";
            $stmt_kirim = $conn->prepare($sql_kirim);
            if (!$stmt_kirim) {
                throw new Exception("Error preparing statement (pengantaran): " . $conn->error);
            }
            $stmt_kirim->bind_param("si", $status_kirim, $id_pesanan);
            if (!$stmt_kirim->execute()) {
                throw new Exception("Gagal update status pengiriman: " . $stmt_kirim->error);
            }
            $stmt_kirim->close();

            $sql_order = "UPDATE pesanan SET status_pesanan = ? WHERE id_pesanan = ?";
            $stmt_order = $conn->prepare($sql_order);
            if (!$stmt_order) {
                throw new Exception("Error preparing statement (pesanan): " . $conn->error);
            }
            $stmt_order->bind_param("si", $status_pesanan, $id_pesanan);
            if (!$stmt_order->execute()) {
                throw new Exception("Gagal update status pesanan: " . $stmt_order->error);
            }
            $stmt_order->close();

            $conn->commit();
            
            header('Location: riwayat_pesanan_admin.php?status=success'); 
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $status_message = ['type' => 'error', 'text' => "Gagal memproses. Error: " . $e->getMessage()];
        }
    }
}

// ------------------------------------------------------------------
// 3. AMBIL DATA PESANAN (SESUAI ROLE)
// ------------------------------------------------------------------
if ($is_admin) {
    // ADMIN: Lihat semua pesanan
    $sql = "SELECT 
        p.id_pesanan, 
        p.tanggal_pesan, 
        p.total_harga, 
        u.nama AS nama_pelanggan, 
        p.status_pesanan,
        pm.status_verifikasi,
        pg.status_kirim,
        pg.alamat      
    FROM pesanan p
    JOIN user u ON p.id_user = u.id_user
    LEFT JOIN pembayaran pm ON p.id_pesanan = pm.id_pesanan
    LEFT JOIN pengantaran pg ON p.id_pesanan = pg.id_pesanan
    ORDER BY p.tanggal_pesan DESC";
} else {
    // USER: Lihat hanya pesanan mereka sendiri
    $sql = "SELECT 
        p.id_pesanan, 
        p.tanggal_pesan, 
        p.total_harga, 
        u.nama AS nama_pelanggan,
        p.status_pesanan,
        pm.status_verifikasi,
        pm.bukti_pembayaran,
        pg.status_kirim
    FROM pesanan p
    JOIN user u ON p.id_user = u.id_user
    LEFT JOIN pembayaran pm ON p.id_pesanan = pm.id_pesanan
    LEFT JOIN pengantaran pg ON p.id_pesanan = pg.id_pesanan
    WHERE p.id_user = ?
    ORDER BY p.tanggal_pesan DESC";
}

if ($is_admin) {
    $result = $conn->query($sql);
} else {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
}

if (!$result) {
    die("Query SQL Gagal: " . $conn->error . "<br>Query yang gagal: " . $sql);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan</title>
    
    <style>
        /* CSS styling */
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f9; }
        
        /* Sidebar untuk Admin */
        .sidebar { width: 200px; height: 100vh; background-color: #2c3e50; color: white; padding: 20px; box-sizing: border-box; position: fixed; display: none; }
        .sidebar.show { display: block; }
        .sidebar h3 { text-align: center; margin-bottom: 30px; color: #388e3c; }
        .sidebar a { display: block; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; margin-bottom: 5px; }
        .sidebar a:hover { background-color: #34495e; }
        
        /* Header untuk User */
        header { background-color: #388e3c; color: white; padding: 15px 30px; display: none; justify-content: space-between; align-items: center; }
        header.show { display: flex; }
        header a { color: white; text-decoration: none; margin-left: 15px; }
        
        /* Content */
        .content { margin-left: 220px; padding: 20px; }
        .content.full { margin-left: 0; max-width: 1000px; margin: 20px auto; background: white; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); padding: 20px; }
        
        h2 { color: #2c3e50; border-bottom: 2px solid #388e3c; padding-bottom: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background-color: white; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #388e3c; color: white; }
        tr:hover { background-color: #f1f1f1; }
        
        .btn { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; color: white; margin-right: 5px; font-size: 0.9em; }
        .btn-kirim { background-color: #2980b9; }
        .btn-konfirmasi { background-color: #e64a19; color: white; text-decoration: none; display: inline-block; }
        
        .status-badge { padding: 5px 10px; border-radius: 4px; font-weight: bold; font-size: 0.8em; }
        .status-baru { background-color: #bdc3c7; color: #333; }
        .status-diproses { background-color: #f39c12; color: white; }
        .status-dikirim { background-color: #2980b9; color: white; }
        .status-selesai { background-color: #27ae60; color: white; }
        .status-ditolak { background-color: #e74c3c; color: white; }
        .status-menunggu { background-color: #f1c40f; color: #333; }
        .status-terverifikasi { background-color: #27ae60; color: white; }
        .status-terkirim { background-color: #2980b9; color: white; }
        .status-belum_ada { background-color: #95a5a6; color: white; }
        .status-menunggu-verifikasi { background-color: #ffeb3b; color: #333; }

        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
    </style>
</head>
<body>
    <?php if ($is_admin): ?>
        <!-- Sidebar Admin -->
        <div class="sidebar show">
            <h3>Admin Panel</h3>
            <a href="verifikasi.php">Verifikasi Pembayaran</a> 
            <a href="riwayat_pesanan_admin.php" style="background-color: #34495e;">Riwayat Pesanan</a>
            <a href="logout.php">Logout</a> 
        </div>
        <div class="content">
            <h2>Riwayat Seluruh Pesanan</h2>
    <?php else: ?>
        <!-- Header User -->
        <header class="show">
            <h1>Riwayat Pesanan Anda</h1>
            <nav>
                <a href="index.php">Katalog</a>
                <a href="logout.php">Logout</a> 
            </nav>
        </header>
        <div class="content full">
            <h2>Daftar Pesanan</h2>
    <?php endif; ?>

        <?php 
        // Tampilkan pesan sukses dari redirect
        if (isset($_GET['status']) && $_GET['status'] == 'success') {
             echo '<div class="alert-success">Status pengiriman berhasil diperbarui.</div>';
        }
        // Tampilkan pesan error dari POST
        if ($status_message): ?>
            <div class="alert-<?php echo $status_message['type']; ?>"><?php echo $status_message['text']; ?></div>
        <?php endif; ?>

        <?php if ($result && $result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <?php if ($is_admin): ?>
                            <th>Pelanggan</th>
                        <?php endif; ?>
                        <th>Total</th>
                        <th>Status Pesanan</th>
                        <th>Status Pembayaran</th>
                        <th>Status Kirim</th>
                        <?php if ($is_admin): ?>
                            <th>Alamat</th>
                        <?php else: ?>
                            <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['id_pesanan']; ?></td>
                            <td><?php echo date('d M Y H:i', strtotime($row['tanggal_pesan'])); ?></td>
                            <?php if ($is_admin): ?>
                                <td><?php echo htmlspecialchars($row['nama_pelanggan']); ?></td>
                            <?php endif; ?>
                            <td>Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></td>
                            <td>
                                <?php 
                                $status_pesanan_clean = str_replace(' ', '-', strtolower($row['status_pesanan'] ?? 'belum_ada'));
                                ?>
                                <span class="status-badge status-<?php echo $status_pesanan_clean; ?>">
                                    <?php echo strtoupper($row['status_pesanan'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $status_verifikasi_clean = str_replace(' ', '-', strtolower($row['status_verifikasi'] ?? 'belum_ada'));
                                ?>
                                <span class="status-badge status-<?php echo $status_verifikasi_clean; ?>">
                                    <?php echo strtoupper($row['status_verifikasi'] ?? 'BELUM ADA'); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $status_kirim_clean = str_replace(' ', '-', strtolower($row['status_kirim'] ?? 'belum_ada'));
                                ?>
                                <span class="status-badge status-<?php echo $status_kirim_clean; ?>">
                                    <?php echo strtoupper($row['status_kirim'] ?? 'BELUM ADA'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($is_admin): ?>
                                    <?php echo htmlspecialchars(substr($row['alamat'] ?? 'N/A', 0, 30)); ?>...
                                <?php else: ?>
                                    <?php 
                                    $can_confirm = ($row['status_verifikasi'] == 'menunggu' || $row['status_verifikasi'] == 'ditolak');

                                    if ($can_confirm): 
                                    ?>
                                        <a href="konfirmasi_pembayaran.php?id=<?php echo $row['id_pesanan']; ?>" class="btn-konfirmasi">
                                            Konfirmasi Bayar
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <?php if ($is_admin): ?>
                <p>Tidak ada riwayat pesanan yang ditemukan.</p>
            <?php else: ?>
                <p>Anda belum memiliki riwayat pesanan.</p>
                <p><a href="index.php">Mulai Belanja Sekarang</a></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>