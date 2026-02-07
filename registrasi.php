<?php
// GANTI MENJADI INI, SESUAI NAMA FILE ANDA
include('connection.php'); 

// Inisialisasi pesan
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = clean_input($_POST['nama']);
    $email = clean_input($_POST['email']);
    
    // Gunakan password_hash untuk keamanan
    $password = password_hash(clean_input($_POST['password']), PASSWORD_DEFAULT);
    $role = 'pelanggan'; 

    // Query untuk memasukkan data ke tabel user
    $sql = "INSERT INTO user (nama, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nama, $email, $password, $role);

    if ($stmt->execute()) {
        $success_message = "Registrasi berhasil! Silakan Login.";
    } else {
        // Cek error, biasanya karena email UNIQUE
        $error_message = "Error: Email sudah terdaftar atau terjadi kesalahan saat menyimpan data.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Pelanggan</title>

    <style>
        body {
            font-family: sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 380px;
        }
        h2 {
            text-align: center;
            color: #388e3c; /* Hijau */
            margin-bottom: 25px;
        }
        form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        form input[type="text"],
        form input[type="email"],
        form input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; 
        }
        form button {
            width: 100%;
            background-color: #e64a19; /* Oranye */
            color: white;
            border: none;
            padding: 12px;
            border-radius: 4px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        form button:hover {
            background-color: #d84315;
        }
        p {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9em;
        }
        p a {
            color: #388e3c;
            text-decoration: none;
            font-weight: bold;
        }
        /* Styling untuk pesan */
        .success-message {
            color: green; 
            border: 1px solid green; 
            padding: 10px; 
            border-radius: 4px; 
            margin-bottom: 15px;
            text-align: center;
        }
        .error-message {
            color: red; 
            border: 1px solid red; 
            padding: 10px; 
            border-radius: 4px; 
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Registrasi Akun</h2>
        <?php if (!empty($success_message)): ?>
            <p class="success-message"><?php echo $success_message; ?></p>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>
        
        <form action="registrasi.php" method="post">
            <label for="nama">Nama:</label>
            <input type="text" id="nama" name="nama" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Registrasi</button>
        </form>
        <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</body>
</html>