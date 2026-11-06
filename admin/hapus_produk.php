<?php
include '../koneksi/koneksi.php';

// Cek apakah ID ada
if (isset($_GET['id'])) {

    $id = (int)$_GET['id'];

    if ($id <= 0) {
        header("Location: index.php");
        exit;
    }

    // Hapus data di inventory dulu (karena ada FK)
    $stmtInv = mysqli_prepare($koneksi, "DELETE FROM inventory WHERE produk_id = ?");
    mysqli_stmt_bind_param($stmtInv, "i", $id);
    mysqli_stmt_execute($stmtInv);
    mysqli_stmt_close($stmtInv);

    // Hapus produk
    $stmt = mysqli_prepare($koneksi, "DELETE FROM produk WHERE id_produk = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    $hapus = mysqli_stmt_execute($stmt);

    if ($hapus) {
        mysqli_stmt_close($stmt);
        header("Location: index.php?pesan=terhapus");
        exit;
    } else {
        die("Gagal Menghapus Data: " . mysqli_error($koneksi));
    }

} else {
    header("Location: index.php");
    exit;
}
?>
