<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Konfigurasi database
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'simprak'; // Pastikan ini adalah nama database Anda
$charset = 'utf8mb4';

// Koneksi MySQLi (jika kamu masih pakai $conn di bagian lain)
$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi MySQLi
if ($conn->connect_error) {
    // Pesan error ini akan lebih terlihat sekarang
    die("Koneksi ke database gagal: " . $conn->connect_error);
}

// Koneksi PDO (Jika Anda menggunakan PDO di beberapa bagian, pertahankan ini. Jika tidak, bisa dihapus untuk kesederhanaan)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Pesan error ini juga akan lebih terlihat
    die("Database connection failed: " . $e->getMessage());
}
?>