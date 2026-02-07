<?php
session_start();

// ======================================================================
// 1. AKTIFKAN DEBUGGING & CEK KONEKSI KRITIS
// ======================================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('connection.php'); 

// Cek koneksi database secara eksplisit
if (!isset($conn) || $conn->connect_error) {
    die("❌ KONEKSI DATABASE GAGAL. Pesan Error: " . ($conn->connect_error ?? "Variabel \$conn tidak terdefinisi."));
}

// ------------------------------------------------------------------
// Otoritas Admin (ADMIN GUARD)
// Hanya user dengan role 'admin' yang boleh mengakses halaman ini
// ------------------------------------------------------------------
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    // Redirect ke halaman login jika tidak login atau bukan admin
    header("Location: login.php"); 
    exit();
}

// ------------------------------------------------------------------
// Fungsi clean_input
// ------------------------------------------------------------------
if (!function_exists('clean_input')) {
    function clean_input($data) {
        global $conn;
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        if ($conn) {
            $data = $conn->real_escape_string($data);
        }
        return $data;
    }
}


// ------------------------------------------------------------------
// 2. LOGIKA POST Aksi Verifikasi/Tolak
// ------------------------------------------------------------------
$status_message = null; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_pesanan']) && isset($_POST['action'])) {
    $id_pesanan = clean_input($_POST['id_pesanan']);
    $action = clean_input($_POST['action']);
    
    $conn->begin_transaction();
    try {
        if ($action == 'verifikasi') {
            $status_pembayaran = 'terverifikasi';
            $status_pesanan = 'dikirim'; 
            // Tidak perlu cek bukti_pembayaran di sini, karena admin langsung melakukan verifikasi
            
        } elseif ($action == 'tolak') {
            $status_pembayaran = 'ditolak';
            $status_pesanan = 'ditolak'; 
            
        } else {
            throw new Exception("Aksi tidak valid.");
        }

        // A. Update Status di Tabel Pembayaran
        $sql_pay = "UPDATE pembayaran SET status_verifikasi = ? WHERE id_pesanan = ?";
        $stmt_pay = $conn->prepare($sql_pay);
        $stmt_pay->bind_param("si", $status_pembayaran, $id_pesanan);
        if (!$stmt_pay->execute()) {
            throw new Exception("Gagal update status pembayaran: " . $conn->error); 
        }
        $stmt_pay->close();

        // B. Update Status di Tabel Pesanan
        $sql_order = "UPDATE pesanan SET status_pesanan = ? WHERE id_pesanan = ?";
        $stmt_order = $conn->prepare($sql_order);
        $stmt_order->bind_param("si", $status_pesanan, $id_pesanan);
        if (!$stmt_order->execute()) {
            throw new Exception("Gagal update status pesanan: " . $conn->error);
        }
        $stmt_order->close();

        // C. Update Status di Tabel Pengantaran
        if ($action == 'verifikasi') {
            $status_kirim = 'selesai';  // Ubah menjadi selesai saat verifikasi
            $sql_kirim = "UPDATE pengantaran SET status_kirim = ? WHERE id_pesanan = ?";
            $stmt_kirim = $conn->prepare($sql_kirim);
            $stmt_kirim->bind_param("si", $status_kirim, $id_pesanan);
            if (!$stmt_kirim->execute()) {
                throw new Exception("Gagal update status pengiriman: " . $conn->error);
            }
            $stmt_kirim->close();
        } elseif ($action == 'tolak') {
            $status_kirim = 'ditolak';  // Ubah menjadi ditolak saat tolak
            $sql_kirim = "UPDATE pengantaran SET status_kirim = ? WHERE id_pesanan = ?";
            $stmt_kirim = $conn->prepare($sql_kirim);
            $stmt_kirim->bind_param("si", $status_kirim, $id_pesanan);
            if (!$stmt_kirim->execute()) {
                throw new Exception("Gagal update status pengiriman: " . $conn->error);
            }
            $stmt_kirim->close();
        }
        
        $conn->commit();
        
        // Redirect setelah POST sukses dengan parameter aksi
        header("Location: verifikasi.php?status=success&action=" . $action);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $status_message = ['type' => 'error', 'text' => "Gagal memproses. Error: " . $e->getMessage()];
    }
}

// ------------------------------------------------------------------
// 3. AMBIL DATA PESANAN YANG PERLU DIVERIFIKASI
// ------------------------------------------------------------------
$sql = "SELECT 
    p.id_pesanan, 
    u.nama AS nama_pelanggan, 
    p.total_harga, 
    pm.bukti_pembayaran, -- Tetap ambil bukti_pembayaran untuk logika aksi
    pm.status_verifikasi,
    p.status_pesanan
FROM pesanan p
JOIN user u ON p.id_user = u.id_user
JOIN pembayaran pm ON p.id_pesanan = pm.id_pesanan
WHERE pm.status_verifikasi = 'menunggu'
ORDER BY p.tanggal_pesan DESC";

$result = $conn->query($sql);

// --- Penanganan Error Query SQL ---
if (!$result) {
    die("❌ QUERY SQL GAGAL. Pesan Error: " . $conn->error . "<br>Query yang gagal: " . $sql);
}

// Tutup koneksi setelah selesai mengambil data
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Verifikasi Pembayaran</title>
    
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f9; }
        .sidebar { width: 200px; height: 100vh; background-color: #2c3e50; color: white; padding: 20px; box-sizing: border-box; position: fixed; }
        .sidebar h3 { text-align: center; margin-bottom: 30px; color: #388e3c; }
        .sidebar a { display: block; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; margin-bottom: 5px; }
        .sidebar a:hover { background-color: #34495e; }
        .content { margin-left: 220px; padding: 20px; }
        h2 { color: #2c3e50; border-bottom: 2px solid #388e3c; padding-bottom: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background-color: white; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #388e3c; color: white; }
        tr:hover { background-color: #f1f1f1; }
        .btn { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; color: white; margin-right: 5px; }
        .btn-verifikasi { background-color: #4CAF50; }
        .btn-tolak { background-color: #e74c3c; }
        .status-badge { padding: 5px 10px; border-radius: 4px; font-weight: bold; font-size: 0.8em; }
        .status-menunggu { background-color: #f39c12; color: white; }
        .status-ditolak { background-color: #e74c3c; color: white; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        /* .bukti-img { max-width: 150px; height: auto; display: block; margin-top: 5px; border: 1px solid #ccc; padding: 2px; } */ /* Dihapus */
    </style>
</head>
<body>
    <div class="sidebar">
        <h3>Admin Panel</h3>
        <a href="verifikasi.php" style="background-color: #34495e;">Verifikasi Pembayaran</a>
        <a href="riwayat_pesanan_admin.php">Riwayat Pesanan</a>
        <a href="logout.php">Logout</a> 
    </div>

    <div class="content">
        <h2>Verifikasi Pembayaran Manual</h2>

        <?php 
        // Tampilkan pesan sukses dari redirect
        if (isset($_GET['status']) && $_GET['status'] == 'success') {
            if (isset($_GET['action']) && $_GET['action'] == 'tolak') {
                echo '<div class="alert-error">✗ Pembayaran Ditolak</div>';
            } else {
                echo '<div class="alert-success">✓ Pembayaran Terverifikasi | Pesanan Terkirim | Status Selesai</div>';
            }
        }
        
        if ($status_message): ?>
            <div class="alert-<?php echo $status_message['type']; ?>"><?php echo $status_message['text']; ?></div>
        <?php endif; ?>

        <?php if ($result && $result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Pelanggan</th>
                        <th>Total Bayar</th>
                        <th>Status Bayar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['id_pesanan']; ?></td>
                            <td><?php echo $row['nama_pelanggan']; ?></td>
                            <td>Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $row['status_verifikasi']; ?>">
                                    <?php echo strtoupper($row['status_verifikasi']); ?>
                                </span>
                            </td>
                            
                            <td>
                                <?php if ($row['status_verifikasi'] != 'terverifikasi'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="id_pesanan" value="<?php echo $row['id_pesanan']; ?>">
                                        <button type="submit" name="action" value="verifikasi" class="btn btn-verifikasi">Verifikasi</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="id_pesanan" value="<?php echo $row['id_pesanan']; ?>">
                                        <button type="submit" name="action" value="tolak" class="btn btn-tolak">Tolak</button>
                                    </form>
                                <?php else: ?>
                                    <p>Selesai</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Tidak ada pembayaran yang perlu diverifikasi saat ini.</p>
        <?php endif; ?>
    </div>
</body>
</html>