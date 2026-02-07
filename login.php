<?php
session_start();
// Memanggil koneksi database (sesuai nama file Anda)
include('connection.php'); 

// Inisialisasi pesan error
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Membersihkan input
    $email = clean_input($_POST['email']);
    $password = clean_input($_POST['password']);

    // Menggunakan Prepared Statement untuk keamanan SQL Injection
    $stmt = $conn->prepare("SELECT id_user, nama, password, role FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verifikasi password yang di-hash
        if (password_verify($password, $user['password'])) {
            // Login berhasil, set Session
            $_SESSION['logged_in'] = true;
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role']; // Session Role disimpan

            // Arahkan pengguna berdasarkan role
            if ($user['role'] == 'admin') {
                // REDIRECT ADMIN KE HALAMAN VERIFIKASI
                header("Location: verifikasi.php"); 
            } else {
                header("Location: index.php"); // Redirect ke Halaman Utama Pelanggan
            }
            exit();
        } else {
            $error_message = "Password salah.";
        }
    } else {
        $error_message = "Email tidak terdaftar.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login E-Commerce Sembako</title>

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
        form input[type="email"],
        form input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Agar padding tidak menambah lebar */
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
        .error-message {
            color: red; 
            border: 1px solid red; 
            padding: 10px; 
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login Pelanggan / Admin</h2>
        <?php if (!empty($error_message)): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>
        
        <form action="login.php" method="post">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>
        <p>Belum punya akun? <a href="registrasi.php">Registrasi di sini</a></p>
    </div>
</body>
</html>