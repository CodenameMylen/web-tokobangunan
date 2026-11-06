<?php
session_start();
include __DIR__ . '/../koneksi/koneksi.php';
require_once __DIR__ . '/../user/auth.php';

if (!is_logged_in()) {
    header("Location: ../user/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../checkout.php");
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    die("Keranjang kosong.");
}

// ambil input
$metode = strtolower($_POST['metode'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');

$lat      = $_POST['lat'] ?? '';
$lng      = $_POST['lng'] ?? '';
$jarak_km = $_POST['jarak_km'] ?? '0';
$ongkir   = $_POST['ongkir'] ?? '0';

if (!in_array($metode, ['cod', 'pickup'], true)) {
    die("Metode pembayaran tidak valid.");
}

if ($metode === 'pickup') {
    $alamat   = 'Ambil di Toko';
    $lat      = '';
    $lng      = '';
    $jarak_km = '0';
    $ongkir   = '0';
} else {
    if ($alamat === '') {
        die("Alamat wajib diisi untuk pengiriman (COD).");
    }
}

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) die("User ID tidak ditemukan di session.");

// ambil id produk dari cart
$productIds = array_map('intval', array_keys($cart));
$productIds = array_values(array_filter($productIds, fn($v) => $v > 0));
if (empty($productIds)) die("Keranjang tidak valid.");

$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$types = str_repeat('i', count($productIds));

mysqli_begin_transaction($koneksi);

try {
    // =========================================================
    // 1) PASTIKAN INVENTORY ADA (kalau belum ada → buat dari produk.qty)
    //    inventory.produk_id harus PK/UNIQUE supaya ON DUPLICATE jalan
    // =========================================================
    $sqlEnsureInv = "INSERT INTO inventory (produk_id, stok, updated_at)
                     SELECT p.id_produk, p.qty, NOW()
                     FROM produk p
                     WHERE p.id_produk IN ($placeholders)
                     ON DUPLICATE KEY UPDATE stok = stok";
    $stmtEnsure = mysqli_prepare($koneksi, $sqlEnsureInv);
    if (!$stmtEnsure) throw new Exception("Prepare ensure inventory gagal: " . mysqli_error($koneksi));

    $params = [];
    $params[] = $types;
    foreach ($productIds as $k => $v) $params[] = &$productIds[$k];
    call_user_func_array([$stmtEnsure, 'bind_param'], $params);

    mysqli_stmt_execute($stmtEnsure);
    mysqli_stmt_close($stmtEnsure);

    // =========================================================
    // 2) AMBIL DATA PRODUK + LOCK ROW INVENTORY (FOR UPDATE)
    // =========================================================
    $sql = "SELECT p.id_produk, p.nama_stock, p.harga, i.stok
            FROM produk p
            JOIN inventory i ON i.produk_id = p.id_produk
            WHERE p.id_produk IN ($placeholders)
            FOR UPDATE";
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) throw new Exception("Prepare ambil produk gagal: " . mysqli_error($koneksi));

    $params2 = [];
    $params2[] = $types;
    foreach ($productIds as $k => $v) $params2[] = &$productIds[$k];
    call_user_func_array([$stmt, 'bind_param'], $params2);

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $produkMap = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $produkMap[(int)$row['id_produk']] = $row;
    }
    mysqli_stmt_close($stmt);

    // =========================================================
    // 3) HITUNG TOTAL + VALIDASI STOK (pakai inventory.stok)
    // =========================================================
    $totalBarang = 0;
    $itemsToInsert = [];

    foreach ($cart as $id_produk => $qty_beli) {
        $id_produk = (int)$id_produk;
        $qty_beli  = (int)$qty_beli;

        if ($qty_beli < 1) continue;
        if (!isset($produkMap[$id_produk])) continue;

        $harga = (int)$produkMap[$id_produk]['harga'];
        $stok  = (int)$produkMap[$id_produk]['stok'];

        if ($stok < 1) continue;
        if ($qty_beli > $stok) $qty_beli = $stok;
        if ($qty_beli < 1) continue;

        $totalBarang += ($harga * $qty_beli);

        $itemsToInsert[] = [
            'produk_id' => $id_produk,
            'harga'     => $harga,
            'qty'       => $qty_beli
        ];
    }

    if (empty($itemsToInsert)) {
        throw new Exception("Tidak ada item valid (stok mungkin habis).");
    }

    $ongkirInt = (int)$ongkir;
    if ($metode === 'pickup') $ongkirInt = 0;

    $totalAkhir = $totalBarang + $ongkirInt;

    // =========================================================
    // 4) INSERT ORDER
    // =========================================================
    $status  = 'pending';
    $created = date('Y-m-d H:i:s');

    $sqlOrder = "INSERT INTO orders (user_id, total_harga, alamat, metode_pembayaran, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?)";
    $stmtOrder = mysqli_prepare($koneksi, $sqlOrder);
    if (!$stmtOrder) throw new Exception("Prepare order gagal: " . mysqli_error($koneksi));

    mysqli_stmt_bind_param($stmtOrder, 'idssss', $userId, $totalAkhir, $alamat, $metode, $status, $created);
    mysqli_stmt_execute($stmtOrder);

    $orderId = mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmtOrder);

    if (!$orderId) throw new Exception("Gagal membuat order.");

    // =========================================================
    // 4.5) GENERATE NOMOR RESI (BARU)
    // =========================================================
    $no_resi = "TKB-" . date("Ymd") . "-" . str_pad($orderId, 5, "0", STR_PAD_LEFT);

    $sqlResi = "UPDATE orders SET no_resi = ? WHERE id = ?";
    $stmtResi = mysqli_prepare($koneksi, $sqlResi);
    if (!$stmtResi) throw new Exception("Prepare resi gagal: " . mysqli_error($koneksi));

    mysqli_stmt_bind_param($stmtResi, 'si', $no_resi, $orderId);
    mysqli_stmt_execute($stmtResi);
    mysqli_stmt_close($stmtResi);

    // =========================================================
    // 5) INSERT ITEMS + UPDATE STOK (harus sukses karena row inventory sudah ada)
    // =========================================================
    $sqlItem = "INSERT INTO order_items (order_id, produk_id, harga, qty) VALUES (?, ?, ?, ?)";
    $stmtItem = mysqli_prepare($koneksi, $sqlItem);
    if (!$stmtItem) throw new Exception("Prepare item gagal: " . mysqli_error($koneksi));

    $sqlUpdateStok = "UPDATE inventory
                      SET stok = stok - ?, updated_at = NOW()
                      WHERE produk_id = ? AND stok >= ?";
    $stmtStok = mysqli_prepare($koneksi, $sqlUpdateStok);
    if (!$stmtStok) throw new Exception("Prepare stok gagal: " . mysqli_error($koneksi));

    foreach ($itemsToInsert as $it) {
        $pid       = (int)$it['produk_id'];
        $hargaItem = (int)$it['harga'];
        $qtyItem   = (int)$it['qty'];

        mysqli_stmt_bind_param($stmtItem, 'iiii', $orderId, $pid, $hargaItem, $qtyItem);
        mysqli_stmt_execute($stmtItem);

        mysqli_stmt_bind_param($stmtStok, 'iii', $qtyItem, $pid, $qtyItem);
        mysqli_stmt_execute($stmtStok);

        if (mysqli_stmt_affected_rows($stmtStok) <= 0) {
            throw new Exception("Stok tidak cukup / berubah saat proses. Produk ID: " . $pid);
        }
    }

    mysqli_stmt_close($stmtItem);
    mysqli_stmt_close($stmtStok);

    mysqli_commit($koneksi);

    unset($_SESSION['cart']);
    $_SESSION['checkout_success'] = 1;
    $_SESSION['last_order_id'] = (int)$orderId;

    header("Location: ../selesai_belanja.php");
    exit;

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    die("Checkout gagal: " . htmlspecialchars($e->getMessage()));
}
