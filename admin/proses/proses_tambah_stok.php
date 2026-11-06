<?php
session_name('ADMINSESSID');
session_start();

if (empty($_SESSION['login_admin'])) {
    http_response_code(403);
    die('BELUM LOGIN');
}
require_once __DIR__ . '/../../koneksi/koneksi.php';

$produk_id  = isset($_POST['produk_id']) ? (int)$_POST['produk_id'] : 0;
$tambah_qty = isset($_POST['tambah_qty']) ? (int)$_POST['tambah_qty'] : 0;

if ($produk_id <= 0 || $tambah_qty <= 0) {
    die("Input tidak valid.");
}

/*
  Update stok di inventory (utama) dan juga produk.qty (biar konsisten dengan data lama).
  inventory.produk_id adalah PK, jadi bisa pakai ON DUPLICATE KEY UPDATE.
*/
mysqli_begin_transaction($koneksi);

try {
    // 1) inventory: insert kalau belum ada, kalau sudah ada -> tambah
    $sqlInv = "
        INSERT INTO inventory (produk_id, stok, updated_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            stok = COALESCE(stok,0) + VALUES(stok),
            updated_at = NOW()
    ";
    $stmt = mysqli_prepare($koneksi, $sqlInv);
    if (!$stmt) throw new Exception("Prepare inventory gagal: " . mysqli_error($koneksi));
    mysqli_stmt_bind_param($stmt, "ii", $produk_id, $tambah_qty);
    if (!mysqli_stmt_execute($stmt)) throw new Exception("Execute inventory gagal: " . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);

    // 2) produk.qty ikut ditambah (opsional tapi bagus biar tidak beda)
    $sqlP = "UPDATE produk SET qty = COALESCE(qty,0) + ? WHERE id_produk = ?";
    $stmt2 = mysqli_prepare($koneksi, $sqlP);
    if (!$stmt2) throw new Exception("Prepare produk gagal: " . mysqli_error($koneksi));
    mysqli_stmt_bind_param($stmt2, "ii", $tambah_qty, $produk_id);
    if (!mysqli_stmt_execute($stmt2)) throw new Exception("Execute produk gagal: " . mysqli_stmt_error($stmt2));
    mysqli_stmt_close($stmt2);

    mysqli_commit($koneksi);

    header("Location: ../index.php");
    exit;

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    die("Gagal tambah stok: " . $e->getMessage());
}
