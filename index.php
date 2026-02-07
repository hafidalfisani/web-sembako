<?php
// Baris 1: Mulai sesi
session_start();
// Memanggil koneksi database yang berada di folder yang sama
include('connection.php'); 

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// -----------------------------------------------------------------
// Catatan: Pastikan fungsi clean_input() didefinisikan 
// (biasanya di file connection.php atau file fungsi terpisah)
// -----------------------------------------------------------------
if (!function_exists('clean_input')) {
    function clean_input($data) {
        // Implementasi sederhana sanitasi
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}


// =================================================================
// LOGIKA TAMBAH KE KERANJANG
// =================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah_keranjang']) && isset($_POST['id_produk']) && isset($_POST['quantity'])) {
    
    // Pastikan koneksi dan fungsi clean_input tersedia dari connection.php
    $id_produk = clean_input($_POST['id_produk']);
    $quantity = (int) clean_input($_POST['quantity']);
    
    if ($quantity > 0) {
        if (isset($_SESSION['keranjang'][$id_produk])) {
            $_SESSION['keranjang'][$id_produk] += $quantity;
        } else {
            $_SESSION['keranjang'][$id_produk] = $quantity;
        }
    }
    
    // REDIRECT KE HALAMAN INI SENDIRI DENGAN STATUS SUCCESS
    // Ini mencegah double submission saat refresh
    header('Location: index.php?status=added');
    exit();
}

// Logika untuk menampilkan pesan sukses (dari redirect di atas)
$status_message = '';
if (isset($_GET['status']) && $_GET['status'] == 'added') {
    $status_message = '<p class="success-alert">Produk berhasil ditambahkan ke keranjang!</p>';
}


// Query untuk mengambil semua produk yang stoknya > 0
$sql = "SELECT id_produk, nama_produk, deskripsi, harga, gambar FROM produk WHERE stok > 0";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Sembako Online</title>

    <style>
        body {
            font-family: sans-serif;
            margin: 0;
            background-color: #f4f4f9;
        }
        header {
            background-color: #388e3c; /* Hijau */
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
        .product-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            padding: 20px;
        }
        .product-card {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px;
            width: 250px;
            text-align: center;
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-card img {
            width: 100%;
            height: 150px; 
            object-fit: cover; 
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .product-card h3 {
            font-size: 1.2em;
            margin: 10px 0 5px 0;
            color: #333;
        }
        .product-card p {
            font-size: 1.1em;
            color: #e64a19; 
            font-weight: bold;
            margin-bottom: 15px;
        }
        .product-card form input[type="number"] {
            width: 40px;
            padding: 5px;
            margin-right: 5px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        .product-card button {
            background-color: #4CAF50; 
            color: white;
            border: none;
            padding: 8px 12px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
        .success-alert {
            max-width: 900px;
            margin: 10px auto;
            text-align: center; 
            color: green; 
            font-weight: bold; 
            padding: 10px; 
            background: #e8f5e9; 
            border: 1px solid #a5d6a7;
            border-radius: 4px;
        }
        @media (max-width: 600px) {
            .product-card {
                width: 90%;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Katalog Sembako</h1>
        <nav>
            <?php if (isset($_SESSION['logged_in'])): ?>
                <a href="keranjang.php">Keranjang</a> | 
                <a href="riwayat_pesanan_admin.php">Riwayat Pesanan</a> | <a href="logout.php">Logout (<?php echo $_SESSION['nama']; ?>)</a>
            <?php else: ?>
                <a href="login.php">Login</a> | 
                <a href="registrasi.php">Registrasi</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php echo $status_message; ?>

    <div class="product-list">
        <?php
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                ?>
                <div class="product-card">
                    <img src="images/<?php echo $row['gambar']; ?>" alt="<?php echo $row['nama_produk']; ?>">
                    <h3><?php echo $row['nama_produk']; ?></h3>
                    <p>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></p>
                    
                    <form action="index.php" method="POST"> 
                        <input type="hidden" name="id_produk" value="<?php echo $row['id_produk']; ?>">
                        <input type="number" name="quantity" value="1" min="1">
                        <button type="submit" name="tambah_keranjang">Masukkan Keranjang</button>
                    </form>
                </div>
                <?php
            }
        } else {
            echo "<p style='text-align: center; width: 100%;'>Stok produk kosong saat ini. Silakan tambahkan data ke tabel 'produk'.</p>";
        }
        $conn->close();
        ?>
    </div>
</body>
</html>