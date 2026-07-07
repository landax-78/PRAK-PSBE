<?php
// ============================================================
// FILE KONEKSI DATABASE - ShoeShoeGaze
// Untuk digunakan dengan XAMPP (localhost)
// ============================================================

$host     = "localhost";
$user     = "root";
$password = "";        // XAMPP default: kosong
$database = "shoeshoegaze";
$port     = 3306;      // Port default MySQL XAMPP

// Buat koneksi
$conn = new mysqli($host, $user, $password, $database, $port);

// Cek koneksi
if ($conn->connect_error) {
    die("<b style='color:red;'>Koneksi Gagal:</b> " . $conn->connect_error);
}

// Set charset agar karakter Indonesia (huruf khusus) tampil benar
$conn->set_charset("utf8mb4");

// Uncomment baris di bawah untuk test koneksi:
// echo "<b style='color:green;'>Koneksi ke database '$database' berhasil!</b>";
?>