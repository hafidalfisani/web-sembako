<?php
// Password yang ingin Anda gunakan untuk login Admin
$password_asli = "min123"; 

// Hasilkan hash password
$hashed_password = password_hash($password_asli, PASSWORD_DEFAULT);

echo "Password Asli: " . $password_asli . "<br>";
echo "Password Hash (Inilah yang Anda Masukkan ke Database): <br>";
echo "<strong>" . $hashed_password . "</strong>";
?>