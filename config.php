<?php
$host = 'localhost';  // Ganti dengan host database Anda, biasanya 'localhost'
$username = 'root';   // Ganti dengan username database Anda
$password = '';       // Ganti dengan password database Anda
$dbname = 'aditya_herlambang';  // Ganti dengan nama database Anda

// Membuat koneksi
$conn = new mysqli($host, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

echo "";
?>
