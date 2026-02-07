<?php
session_start();
// Memanggil koneksi database
include('connection.php'); 

// ------------------------------------------------------------------
// 1. CEK PRASYARAT
// ------------------------------------------------------------------

// A. Wajib Login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
$id_user = $_SESSION['id_user'];

// B. Wajib Keranjang Terisi
if (empty($_SESSION['keranjang'])) {
    header("Location: keranjang.php");
    exit();
}

// ------------------------------------------------------------------
// 2. AMBIL DATA PRODUK UNTUK RINGKASAN & HITUNG TOTAL
// ------------------------------------------------------------------
$keranjang_data = [];
$total_bayar = 0;
$ids = array_keys($_SESSION['keranjang']);
// Membuat string ID yang aman untuk digunakan dalam query IN
$id_string = implode(',', array_map('intval', $ids)); 

$sql_produk = "SELECT id_produk, nama_produk, harga FROM produk WHERE id_produk IN ($id_string)";
$result_produk = $conn->query($sql_produk);

if ($result_produk && $result_produk->num_rows > 0) {
    while ($row = $result_produk->fetch_assoc()) {
        $id = $row['id_produk'];
        $qty = $_SESSION['keranjang'][$id];
        $subtotal = $qty * $row['harga'];
        $total_bayar += $subtotal;

        $keranjang_data[] = [
            'id_produk' => $id,
            'nama_produk' => $row['nama_produk'],
            'harga' => $row['harga'],
            'quantity' => $qty,
            'subtotal' => $subtotal
        ];
    }
} else {
    // Jika tidak ada data produk, hapus keranjang dan kembali
    unset($_SESSION['keranjang']);
    header("Location: keranjang.php");
    exit();
}

// ------------------------------------------------------------------
// 3. PROSES SUBMISSION CHECKOUT (Simpan ke DB)
// ------------------------------------------------------------------
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['proses_checkout'])) {
    $alamat_kirim = clean_input($_POST['alamat_kirim']);
    $metode_bayar = clean_input($_POST['metode_bayar']);
    $tanggal_pesan = date('Y-m-d H:i:s');
    $status_pesanan = 'baru'; // status awal pesanan
    $status_kirim = 'menunggu'; // status awal pengantaran
    
    // Mulai Transaksi Database (penting untuk integritas 4 tabel sekaligus)
    $conn->begin_transaction();
    $is_success = true;

    try {
        // A. INSERT KE TABEL PESANAN
        $sql_pesanan = "INSERT INTO pesanan (id_user, tanggal_pesan, total_harga, status_pesanan) VALUES (?, ?, ?, ?)";
        $stmt_pesanan = $conn->prepare($sql_pesanan);
        $stmt_pesanan->bind_param("isds", $id_user, $tanggal_pesan, $total_bayar, $status_pesanan);
        
        if (!$stmt_pesanan->execute()) {
            $is_success = false;
            throw new Exception("Gagal menyimpan pesanan utama.");
        }
        $id_pesanan = $conn->insert_id;
        $stmt_pesanan->close();

        // B. INSERT KE TABEL DETAIL_PESANAN
        $sql_detail = "INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, subtotal) VALUES (?, ?, ?, ?)";
        $stmt_detail = $conn->prepare($sql_detail);
        
        foreach ($keranjang_data as $item) {
            $stmt_detail->bind_param("iiid", $id_pesanan, $item['id_produk'], $item['quantity'], $item['subtotal']);
            if (!$stmt_detail->execute()) {
                $is_success = false;
                throw new Exception("Gagal menyimpan detail pesanan.");
            }
        }
        $stmt_detail->close();

        // C. INSERT KE TABEL PENGANTARAN
        $sql_pengantaran = "INSERT INTO pengantaran (id_pesanan, alamat, status_kirim) VALUES (?, ?, ?)";
        $stmt_pengantaran = $conn->prepare($sql_pengantaran);
        $stmt_pengantaran->bind_param("iss", $id_pesanan, $alamat_kirim, $status_kirim);
        
        if (!$stmt_pengantaran->execute()) {
            $is_success = false;
            throw new Exception("Gagal menyimpan data pengantaran.");
        }
        $stmt_pengantaran->close();
        
        // D. INSERT KE TABEL PEMBAYARAN 
        // Status verifikasi awal: menunggu (untuk transfer manual)
        $status_verifikasi = 'menunggu';
        $sql_pembayaran = "INSERT INTO pembayaran (id_pesanan, metode, tanggal_bayar, status_verifikasi) VALUES (?, ?, ?, ?)";
        $stmt_pembayaran = $conn->prepare($sql_pembayaran);
        $stmt_pembayaran->bind_param("isss", $id_pesanan, $metode_bayar, $tanggal_pesan, $status_verifikasi);
        
        if (!$stmt_pembayaran->execute()) {
            $is_success = false;
            throw new Exception("Gagal menyimpan data pembayaran.");
        }
        $stmt_pembayaran->close();


        // E. Commit Transaksi & Redirect
        if ($is_success) {
            $conn->commit();
            // Kosongkan keranjang setelah sukses
            unset($_SESSION['keranjang']);
            
            // ✅ REDIRECT KE HALAMAN KONFIRMASI DENGAN ID PESANAN
            header("Location: konfirmasi_pembayaran.php?id=" . $id_pesanan); 
            exit();
        }

    } catch (Exception $e) {
        $conn->rollback(); // Rollback jika ada kegagalan
        $error_message = "Proses Checkout Gagal: " . $e->getMessage();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Konfirmasi Pesanan</title>

    <style>
        body {
            font-family: sans-serif;
            margin: 0;
            background-color: #f4f4f9;
        }
        header {
            background-color: #388e3c;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }
        .container {
            max-width: 700px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #388e3c;
            border-bottom: 2px solid #388e3c;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .summary-table th, .summary-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .summary-table th {
            text-align: left;
            background-color: #f9f9f9;
        }
        .summary-total td {
            font-weight: bold;
            color: #e64a19;
            border-top: 2px solid #388e3c;
            font-size: 1.1em;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-checkout {
            background-color: #e64a19;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-size: 1em;
            cursor: pointer;
            width: 100%;
        }
        .alert-success { color: green; border: 1px solid green; padding: 10px; margin-bottom: 15px; }
        .alert-error { color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <header>
        <h1>Checkout</h1>
        <nav>
            <a href="index.php">Katalog</a>
        </nav>
    </header>

    <div class="container">
        <?php if ($error_message): ?>
            <div class="alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <h2>Ringkasan Pesanan</h2>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($keranjang_data as $item): ?>
                    <tr>
                        <td><?php echo $item['nama_produk']; ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="summary-total">
                    <td colspan="2">Total Pembayaran</td>
                    <td>Rp <?php echo number_format($total_bayar, 0, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>

        <h2>Form Pengiriman & Pembayaran</h2>
        <form action="checkout.php" method="POST">
            
            <div class="form-group">
                <label for="alamat_kirim">Alamat Pengiriman Lengkap:</label>
                <textarea id="alamat_kirim" name="alamat_kirim" rows="4" required 
                          placeholder="Masukkan alamat lengkap (Jalan, No Rumah, RT/RW, Kecamatan)"></textarea>
            </div>

            <div class="form-group">
                <label for="metode_bayar">Metode Pembayaran:</label>
                <select id="metode_bayar" name="metode_bayar" required>
                    <option value="">-- Pilih Metode --</option>
                    <option value="transfer">Transfer Bank (Manual)</option>
                    <option value="manual">Bayar di Tempat (COD) - Manual</option>
                </select>
            </div>
            
            <p style="color: #d32f2f; font-size: 0.9em;">
            </p>

            <button type="submit" name="proses_checkout" class="btn-checkout">
                Konfirmasi & Proses Checkout
            </button>
        </form>
    </div>
</body>
</html>