<?php
date_default_timezone_set('Asia/Jakarta');

$host = "sql305.infinityfree.com";
$user = "if0_40883936";
$password = "kpenoputri23";
$db = "if0_40883936_toko_bangunan";

$koneksi = mysqli_connect($host, $user, $password, $db);

if (mysqli_connect_errno()) {
    echo "Koneksi database gagal: " . mysqli_connect_error();
    exit;
}

// ================= SET TIMEZONE MYSQL =================
mysqli_query($koneksi, "SET time_zone = '+07:00'");
?>  