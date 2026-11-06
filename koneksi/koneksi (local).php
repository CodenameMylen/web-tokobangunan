<?php
date_default_timezone_set('Asia/Jakarta');

$host = "localhost";
$user = "root";
$password = "";
$db = "db_tokobangunan";

$koneksi = mysqli_connect($host, $user, $password, $db);

if (mysqli_connect_errno()) {
    echo "Koneksi database gagal: " . mysqli_connect_error();
    exit;
}

// ================= SET TIMEZONE MYSQL =================
mysqli_query($koneksi, "SET time_zone = '+07:00'");
?>  