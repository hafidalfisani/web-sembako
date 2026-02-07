<?php
session_start();
// Pastikan koneksi.php ada di lokasi yang benar
include('connection.php'); 

// ------------------------------------------------------------------
// 1. CEK PRASYARAT DAN AMBIL ID PESANAN
// ------------------------------------------------------------------

// A. Wajib Login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
$id_user = $_SESSION['id_user'];

// B. Wajib ada ID Pesanan di URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Error: ID Pesanan tidak valid.");
}
$id_pesanan = (int)$_GET['id'];

$success_message = '';
$error_message = '';

// ------------------------------------------------------------------
// 2. PROSES UPLOAD BUKTI PEMBAYARAN (DIHILANGKAN TOTAL)
// ------------------------------------------------------------------
// Logika POST dan pemrosesan upload telah DIBUANG.
// Jika ada tombol 'konfirmasi' di HTML, tidak akan ada yang terjadi.

// ------------------------------------------------------------------
// 3. AMBIL DETAIL PESANAN SAAT INI
// ------------------------------------------------------------------
$pesanan = null;
// Asumsi: $conn sudah tersedia dari 'connection.php'
$stmt_detail = $conn->prepare("SELECT 
    p.total_harga, 
    p.status_pesanan, 
    pm.status_verifikasi,
    pm.bukti_pembayaran
FROM pesanan p
JOIN pembayaran pm ON p.id_pesanan = pm.id_pesanan
WHERE p.id_pesanan = ? AND p.id_user = ?");
$stmt_detail->bind_param("ii", $id_pesanan, $id_user);
$stmt_detail->execute();
$result_detail = $stmt_detail->get_result();

if ($result_detail->num_rows === 1) {
    $pesanan = $result_detail->fetch_assoc();
} else {
    // Pesan ini hanya akan ditampilkan jika tidak ada hasil dan belum ada error lain
    if (empty($error_message)) {
        $error_message = "ID Pesanan tidak ditemukan atau bukan milik Anda.";
    }
}
$stmt_detail->close();
// Tutup koneksi di sini (di akhir bagian PHP)
if (isset($conn)) {
    $conn->close();
}


if (!$pesanan && !$error_message) {
    $error_message = "Pesanan tidak ditemukan.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pembayaran</title>
    
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f9; }
        header { background-color: #388e3c; color: white; padding: 15px 30px; }
        header a { color: white; text-decoration: none; margin-left: 15px; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { color: #388e3c; border-bottom: 2px solid #388e3c; padding-bottom: 10px; margin-bottom: 20px; }
        .info-box { border: 1px solid #ddd; padding: 15px; border-radius: 4px; margin-bottom: 20px; background-color: #f9f9f9; }
        .info-box p { margin: 5px 0; }
        .status-badge { display: inline-block; padding: 5px 10px; border-radius: 12px; font-weight: bold; }
        .status-menunggu-verifikasi, .status-menunggu { background-color: #ffeb3b; color: #333; }
        .status-terverifikasi { background-color: #27ae60; color: white; }
        .status-ditolak { background-color: #e74c3c; color: white; }
        .status-dikirim { background-color: #2980b9; color: white; }
        .status-selesai { background-color: #4caf50; color: white; }
        
        .alert-info { color: #0c5460; border: 1px solid #bee5eb; padding: 10px; margin-bottom: 15px; background-color: #d1ecf1; border-radius: 4px; }
        .alert-success { color: green; border: 1px solid green; padding: 10px; margin-bottom: 15px; background-color: #e8f5e9; }
        .alert-error { color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px; background-color: #ffebee; }
        .current-bukti img { max-width: 100px; height: auto; margin-top: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <header>
        <h1>Status Pembayaran</h1>
        <nav>
            <a href="index.php">Katalog</a>
        </nav>
    </header>

    <div class="container">
        <?php if ($error_message): ?>
            <div class="alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if ($pesanan): ?>
            <h2>Status Pesanan #<?php echo $id_pesanan; ?></h2>
            
            <div class="info-box">
                <p>Total Pembayaran: **Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?>**</p>
                <p>Status Pesanan: 
                    <span class="status-badge status-<?php echo str_replace(' ', '-', strtolower($pesanan['status_pesanan'])); ?>">
                        <?php echo strtoupper($pesanan['status_pesanan']); ?>
                    </span>
                </p>
                <p>Status Pembayaran: 
                    <span class="status-badge status-<?php echo str_replace(' ', '-', strtolower($pesanan['status_verifikasi'])); ?>">
                        <?php echo strtoupper($pesanan['status_verifikasi'] ?? 'BELUM BAYAR'); ?>
                    </span>
                </p>
                
                <?php if ($pesanan['bukti_pembayaran']): ?>
                    <p class="current-bukti">Bukti yang terunggah: 
                        <img src="uploads/bukti/<?php echo $pesanan['bukti_pembayaran']; ?>" alt="Bukti Transfer">
                    </p>
                <?php endif; ?>
            </div>
            
            <?php 
            // Bagian untuk menampilkan status pembayaran tanpa form upload
            if ($pesanan['status_verifikasi'] == 'menunggu'): 
            ?>
                    <div class="alert-info">
                        Bukti pembayaran Anda **sedang diproses** oleh Admin. Mohon tunggu notifikasi verifikasi.
                    </div>
            <?php 
            elseif ($pesanan['status_verifikasi'] == 'terverifikasi'): 
            ?>
                    <div class="alert-success">
                        Pembayaran Anda **BERHASIL DIVERIFIKASI**! Pesanan Anda akan segera disiapkan.
                    </div>
            <?php 
            elseif ($pesanan['status_verifikasi'] == 'ditolak'): 
            ?>
                    <div class="alert-error">
                        Konfirmasi pembayaran Anda **DITOLAK**. Harap hubungi Admin untuk petunjuk lebih lanjut.
                    </div>
            <?php 
            else: // Status selain menunggu, terverifikasi, atau ditolak (misal NULL/belum bayar)
            ?>
                    <div class="alert-info">
                        Untuk memproses pesanan, harap lakukan transfer dan hubungi Admin.
                    </div>
            <?php 
            endif;
            ?>

        <?php endif; ?>
        
        <p style="margin-top: 25px;"><a href="index.php">Kembali ke Katalog</a></p>
    </div>
</body>
</html>