<?php
// =================================================================
// KONFIGURASI KONEKSI DATABASE
// Pastikan nama database sudah dibuat dan sesuai
// =================================================================
$servername = "localhost";
$username = "root";       
$password = "";           
$dbname = "ecommerce_sembako_db"; 

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi Database Gagal: " . $conn->connect_error);
}

// Fungsi untuk membersihkan dan mengamankan input data
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data);
    return $data;
}
?>