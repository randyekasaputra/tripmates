<?php
$host = 'localhost';
$dbname = 'traveling';
$username = 'your_username';  // Ganti dengan username MySQL Anda
$password = 'your_password';  // Ganti dengan password MySQL Anda

try {
    $db = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    error_log("Connection Error: " . $e->getMessage());
    die("Koneksi database gagal: " . $e->getMessage());
}
