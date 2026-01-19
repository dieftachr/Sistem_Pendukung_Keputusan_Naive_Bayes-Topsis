<?php
// config.php

define('DB_SERVER', 'localhost'); // Biasanya 'localhost'
define('DB_USERNAME', 'root');   // Ganti dengan username database Anda
define('DB_PASSWORD', '');       // Ganti dengan password database Anda
define('DB_NAME', 'metisys_db'); // Nama database yang akan Anda buat

// Coba koneksi ke database MySQL
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
?>
