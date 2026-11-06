<?php
session_name('ADMINSESSID');
session_start();

if (empty($_SESSION['login_admin'])) {
    http_response_code(403);
    die('BELUM LOGIN');
}
require_once __DIR__ . '/../../koneksi/koneksi.php';

/* =====================
   Helper: cek kolom tabel produk
   ===================== */
function getTableColumns(mysqli $koneksi, string $table): array {
    $cols = [];
    $res = mysqli_query($koneksi, "SHOW COLUMNS FROM `$table`");
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $cols[] = strtolower($r['Field']);
        }
    }
    return $cols;
}

function pickColumn(array $cols, array $candidates): ?string {
    foreach ($candidates as $c) {
        if (in_array(strtolower($c), $cols, true)) return $c;
    }
    return null;
}

/* =====================
   Kode auto: isi nomor kosong
   ===================== */
function getNextKode(mysqli $koneksi): string {
    $res = mysqli_query($koneksi, "SELECT kode_stock FROM produk");
    $used = [];

    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $k = (string)($r['kode_stock'] ?? '');
            if (preg_match('/(\d+)/', $k, $m)) {
                $used[(int)$m[1]] = true;
            }
        }
    }

    $n = 1;
    while (isset($used[$n])) $n++;

    return 'BRG-' . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
}

/* =====================
   VALIDASI INPUT
   ===================== */
$nama_stock = trim($_POST['nama_stock'] ?? '');
$milik      = trim($_POST['milik'] ?? '');

// dari form kamu:
$qtyInput   = (int)($_POST['qty'] ?? 0);
$hargaInput = (int)($_POST['merk'] ?? 0); // merk kamu anggap harga

if ($nama_stock === '' || $milik === '') die('NAMA / KATEGORI WAJIB DIISI');
if ($qtyInput < 0 || $hargaInput < 0) die('QTY / HARGA TIDAK BOLEH MINUS');

/* =====================
   VALIDASI FILE
   ===================== */
if (empty($_FILES['gambar']['name'])) die('FILE TIDAK ADA');

$allowed = ['jpg','jpeg','png','webp'];
$ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed, true)) die('FORMAT FILE TIDAK DIDUKUNG');

/* =====================
   UPLOAD DIR (HOSTING FRIENDLY)
   rootProject = folder project (selevel admin, assets, koneksi, dll)
   ===================== */
$rootProject = realpath(__DIR__ . '/../../'); // admin/proses -> root
$uploadDir   = $rootProject . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        die('GAGAL MEMBUAT FOLDER UPLOAD: ' . $uploadDir);
    }
}
if (!is_writable($uploadDir)) {
    die('FOLDER UPLOAD TIDAK BISA DITULIS. CEK PERMISSION: ' . $uploadDir);
}

$filename = uniqid('produk_', true) . '.' . $ext;
$target   = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $target)) {
    die('UPLOAD GAGAL');
}

/* =====================
   AUTO DETECT KOLOM STOK & HARGA DI DB
   ===================== */
$cols = getTableColumns($koneksi, 'produk');

// kandidat stok & harga (tambahkan kalau kamu punya nama lain)
$stokCol  = pickColumn($cols, ['qty','stok','stock','jumlah','sisa','persediaan']);
$hargaCol = pickColumn($cols, ['harga','price','hargajual','biaya','nilai','nominal','merk']); // merk ditaruh terakhir

// pastikan kolom wajib ada:
if (!in_array('kode_stock', $cols, true)) die("Tabel produk tidak punya kolom kode_stock");
if (!in_array('nama_stock', $cols, true)) die("Tabel produk tidak punya kolom nama_stock");
if (!in_array('milik', $cols, true))      die("Tabel produk tidak punya kolom milik");
if (!in_array('gambar', $cols, true))     die("Tabel produk tidak punya kolom gambar");

// stok/harga boleh tidak ada, tapi kalau tidak ada ya tidak kita insert
$kode_stock = getNextKode($koneksi);

/* =====================
   BUILD INSERT DINAMIS (tanpa kolom yang tidak ada)
   ===================== */
$fields = ['kode_stock','nama_stock','milik','gambar'];
$params = [$kode_stock, $nama_stock, $milik, $filename];
$types  = "ssss";

if ($stokCol !== null) {
    $fields[] = $stokCol;
    $params[] = $qtyInput;
    $types   .= "i";
}

if ($hargaCol !== null) {
    $fields[] = $hargaCol;
    $params[] = $hargaInput;
    $types   .= "i";
}

$fieldList = implode(',', array_map(fn($f) => "`$f`", $fields));
$placeholders = rtrim(str_repeat('?,', count($fields)), ',');

$sql = "INSERT INTO `produk` ($fieldList) VALUES ($placeholders)";

$stmt = mysqli_prepare($koneksi, $sql);
if (!$stmt) die('PREPARE GAGAL: ' . mysqli_error($koneksi));

/* bind_param butuh reference */
$bind_names = [];
$bind_names[] = $types;
for ($i=0; $i<count($params); $i++) {
    $bind_names[] = &$params[$i];
}
call_user_func_array([$stmt, 'bind_param'], $bind_names);

if (!mysqli_stmt_execute($stmt)) {
    die('INSERT GAGAL: ' . mysqli_stmt_error($stmt));
}

header("Location: ../index.php");
exit;
