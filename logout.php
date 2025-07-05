<?php
session_start();

// Hapus semua variabel session
$_SESSION = array();

// Hancurkan session
session_destroy();

// Redirect ke halaman login
// UBAH BARIS INI:
header("Location: login.php"); // Path yang benar, karena login.php ada di folder yang sama
exit;
?>