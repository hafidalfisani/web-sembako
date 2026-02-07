<?php
session_start();
// Memanggil koneksi database
include('connection.php'); 

// Pastikan pengguna sudah login jika ingin mengakses keranjang (Opsional, tapi disarankan)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Header("Location: login.php");
    // exit(); 
    // Catatan: Jika Anda ingin membiarkan keranjang diisi tanpa login, abaikan redirect ini.
}


// =================================================================
// 1. LOGIKA UTAMA KERANJANG
// =================================================================

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// Logika Menambah Produk dari index.php (Jika diakses langsung dari index)
if (isset($_POST['tambah_keranjang']) && isset($_POST['id_produk']) && isset($_POST['quantity'])) {
    $id_produk = clean_input($_POST['id_produk']);
    $quantity = (int) clean_input($_POST['quantity']);
    
    if ($quantity > 0) {
        if (isset($_SESSION['keranjang'][$id_produk])) {
            $_SESSION['keranjang'][$id_produk] += $quantity;
        } else {
            $_SESSION['keranjang'][$id_produk] = $quantity;
        }
    }
    // Redirect untuk mencegah double submission saat refresh
    header('Location: keranjang.php');
    exit();
}

// Logika Mengupdate Kuantitas (dari form di bawah)
if (isset($_POST['update_keranjang'])) {
    foreach ($_POST['qty'] as $id_produk => $quantity) {
        $id_produk = (int) $id_produk;
        $quantity = (int) clean_input($quantity);

        if ($quantity > 0) {
            $_SESSION['keranjang'][$id_produk] = $quantity;
        } elseif ($quantity == 0 && isset($_SESSION['keranjang'][$id_produk])) {
            unset($_SESSION['keranjang'][$id_produk]); // Hapus jika kuantitas 0
        }
    }
    header('Location: keranjang.php');
    exit();
}

// Logika Menghapus Item
if (isset($_GET['hapus']) && isset($_SESSION['keranjang'][$_GET['hapus']])) {
    unset($_SESSION['keranjang'][$_GET['hapus']]);
    header('Location: keranjang.php');
    exit();
}

// =================================================================
// 2. MENGAMBIL DATA PRODUK DARI DATABASE
// =================================================================

$keranjang_data = [];
$total_bayar = 0;

if (!empty($_SESSION['keranjang'])) {
    $ids = array_keys($_SESSION['keranjang']);
    // Membuat string ID yang aman untuk digunakan dalam query IN
    $id_string = implode(',', array_map('intval', $ids)); 

    $sql = "SELECT id_produk, nama_produk, harga, gambar FROM produk WHERE id_produk IN ($id_string)";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = $row['id_produk'];
            $qty = $_SESSION['keranjang'][$id];
            $subtotal = $qty * $row['harga'];
            $total_bayar += $subtotal;

            $keranjang_data[] = [
                'id_produk' => $id,
                'nama_produk' => $row['nama_produk'],
                'harga' => $row['harga'],
                'gambar' => $row['gambar'],
                'quantity' => $qty,
                'subtotal' => $subtotal
            ];
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja Anda</title>

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
            max-width: 900px;
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
        }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .cart-table th, .cart-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        .cart-table th {
            background-color: #f2f2f2;
        }
        .cart-table img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        .total-summary {
            margin-top: 20px;
            text-align: right;
            border-top: 2px solid #388e3c;
            padding-top: 15px;
        }
        .total-summary strong {
            font-size: 1.5em;
            color: #e64a19;
        }
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .action-buttons a, .action-buttons button {
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-checkout {
            background-color: #e64a19; /* Oranye */
            color: white;
            border: none;
        }
        .btn-continue {
            background-color: #388e3c; /* Hijau */
            color: white;
        }
        .btn-remove {
            color: #d32f2f; /* Merah */
            text-decoration: none;
        }
    </style>
</head>
<body>
    <header>
        <h1>Keranjang Belanja</h1>
        <nav>
            <a href="index.php">Lanjut Belanja</a>
        </nav>
    </header>

    <div class="container">
        <h2>Detail Keranjang Anda</h2>

        <?php if (empty($keranjang_data)): ?>
            <p style="text-align: center; padding: 50px; font-size: 1.2em;">
                Keranjang belanja Anda kosong. <a href="index.php">Ayo mulai belanja!</a>
            </p>
        <?php else: ?>
            <form action="keranjang.php" method="POST">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Gambar</th>
                            <th>Produk</th>
                            <th>Harga Satuan</th>
                            <th>Kuantitas</th>
                            <th>Subtotal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($keranjang_data as $item): ?>
                            <tr>
                                <td>
                                    <img src="images/<?php echo $item['gambar']; ?>" alt="<?php echo $item['nama_produk']; ?>">
                                </td>
                                <td><?php echo $item['nama_produk']; ?></td>
                                <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                <td>
                                    <input type="number" name="qty[<?php echo $item['id_produk']; ?>]" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="0" style="width: 50px;">
                                </td>
                                <td>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                <td>
                                    <a href="keranjang.php?hapus=<?php echo $item['id_produk']; ?>" class="btn-remove">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="text-align: right; margin-top: 15px;">
                    <button type="submit" name="update_keranjang" class="btn-continue">Perbarui Keranjang</button>
                </div>
            </form>

            <div class="total-summary">
                <p>Total Belanja: <strong>Rp <?php echo number_format($total_bayar, 0, ',', '.'); ?></strong></p>
            </div>

            <div class="action-buttons">
                <a href="index.php" class="btn-continue">« Lanjut Belanja</a>
                <a href="checkout.php" class="btn-checkout">Proses Checkout »</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>