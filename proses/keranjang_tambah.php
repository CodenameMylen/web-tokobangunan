<?php
// =======================
// SESSION dulu
// =======================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =======================
// PROTEKSI LOGIN
// =======================
require_once __DIR__ . '/../user/auth.php';
require_login();

// =======================
// DB
// =======================
require_once __DIR__ . '/../koneksi/koneksi.php';

// pastikan cart array: [id_produk => qty]
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ambil id produk
$produkId = (int)($_GET['id'] ?? 0);
if ($produkId <= 0) {
    header("Location: ../produk.php");
    exit;
}

// =======================
// CEK PRODUK & STOK (pakai inventory.stok)
// =======================
$stmt = mysqli_prepare($koneksi, "
    SELECT p.id_produk, COALESCE(i.stok,0) AS stok
    FROM produk p
    LEFT JOIN inventory i ON i.produk_id = p.id_produk
    WHERE p.id_produk = ?
    LIMIT 1
");
if (!$stmt) {
    header("Location: ../produk.php");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $produkId);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$row) {
    header("Location: ../produk.php");
    exit;
}

$stok = (int)$row['stok'];
if ($stok <= 0) {
    header("Location: ../detail_produk.php?id=" . $produkId);
    exit;
}

// =======================
// TAMBAH KE CART (max stok)
// =======================
$now = (int)($_SESSION['cart'][$produkId] ?? 0);
$new = $now + 1;

if ($new > $stok) $new = $stok;

$_SESSION['cart'][$produkId] = $new;

// =======================
// REDIRECT (untuk tes: ke keranjang dulu)
// =======================
header("Location: ../keranjang.php");
exit;
