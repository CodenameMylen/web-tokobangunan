<?php
session_name('ADMINSESSID');
session_start();

if (empty($_SESSION['login_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../../koneksi/koneksi.php';

/* =====================
   VALIDASI POST (yang boleh diubah)
   ===================== */
if (!isset($_POST['id_produk'], $_POST['nama_stock'], $_POST['harga'], $_POST['milik'])) {
    die("Data tidak lengkap");
}

$id_produk   = (int)$_POST['id_produk'];
$nama_stock  = trim((string)$_POST['nama_stock']);
$harga       = (int)($_POST['harga'] ?? 0);
$milik       = trim((string)$_POST['milik']); // ✅ kategori dashboard = milik
$gambar_lama = basename(trim((string)($_POST['gambar_lama'] ?? '')));

if ($id_produk <= 0) die("ID produk tidak valid");
if ($nama_stock === '') die("Nama barang tidak boleh kosong");
if ($milik === '') die("Kategori tidak boleh kosong");
if ($harga < 0) $harga = 0;

/* =====================
   PATH UPLOAD
   ===================== */
$projectRoot = realpath(__DIR__ . '/../../');
if ($projectRoot === false) die("Tidak bisa menentukan root project.");

$upload_dir = $projectRoot . '/assets/uploads/';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0775, true)) {
        die("Folder upload gagal dibuat: " . $upload_dir);
    }
}

/* =====================
   AMBIL GAMBAR LAMA DARI DB
   ===================== */
$stmtOld = mysqli_prepare($koneksi, "SELECT gambar FROM produk WHERE id_produk = ? LIMIT 1");
if (!$stmtOld) die("Prepare gagal: " . mysqli_error($koneksi));

mysqli_stmt_bind_param($stmtOld, "i", $id_produk);
mysqli_stmt_execute($stmtOld);
$resOld = mysqli_stmt_get_result($stmtOld);
$oldRow = $resOld ? mysqli_fetch_assoc($resOld) : null;
mysqli_stmt_close($stmtOld);

$gambar_db_lama = isset($oldRow['gambar']) ? basename((string)$oldRow['gambar']) : '';
if ($gambar_db_lama !== '') $gambar_lama = $gambar_db_lama;

/* =====================
   LOGIKA GAMBAR
   ===================== */
$gambar_baru = $gambar_lama;

if (!empty($_FILES['gambar']['name']) && ($_FILES['gambar']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $ext_valid = ['jpg','jpeg','png','webp'];
    $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $ext_valid, true)) die("Format gambar tidak valid (jpg/jpeg/png/webp)");
    if (($_FILES['gambar']['size'] ?? 0) > 2 * 1024 * 1024) die("Ukuran gambar maksimal 2MB");

    $gambar_baru = uniqid('produk_', true) . '.' . $ext;
    $target = $upload_dir . $gambar_baru;

    if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $target)) die("Gagal upload gambar");

    // hapus gambar lama setelah upload sukses
    if (!empty($gambar_lama) && $gambar_lama !== $gambar_baru) {
        $oldPath = $upload_dir . basename($gambar_lama);
        if (file_exists($oldPath)) @unlink($oldPath);
    }
}

/* =====================
   UPDATE PRODUK (HANYA FIELD YANG DIIZINKAN)
   ===================== */
mysqli_begin_transaction($koneksi);

try {
    $sqlUp = "UPDATE produk SET nama_stock=?, harga=?, milik=?, gambar=? WHERE id_produk=? LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $sqlUp);
    if (!$stmt) throw new Exception("Prepare gagal: " . mysqli_error($koneksi));

    mysqli_stmt_bind_param($stmt, "sissi", $nama_stock, $harga, $milik, $gambar_baru, $id_produk);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Gagal update produk: " . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);

    mysqli_commit($koneksi);
    header("Location: ../index.php?status=edit_success");
    exit;

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    die("Error: " . $e->getMessage());
}