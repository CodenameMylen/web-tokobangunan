b<?php
session_start();
include __DIR__ . '/koneksi/koneksi.php';
require_once __DIR__ . '/user/auth.php';

if (!is_logged_in()) {
  header("Location: user/login.php");
  exit;
}

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) die("Order tidak valid.");

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) die("User tidak valid.");

// pastikan order milik user dan masih pending
$sql = "SELECT id, status FROM orders WHERE id = ? AND user_id = ? LIMIT 1";
$stmt = mysqli_prepare($koneksi, $sql);
if (!$stmt) die("Prepare gagal: " . mysqli_error($koneksi));
mysqli_stmt_bind_param($stmt, 'ii', $orderId, $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($res);

if (!$order) die("Order tidak ditemukan / bukan milik anda.");

if (strtolower($order['status']) !== 'pending') {
  die("Pesanan tidak bisa dibatalkan karena status sudah: " . htmlspecialchars($order['status']));
}

// ambil item untuk restore stok
$sqlItems = "SELECT produk_id, qty FROM order_items WHERE order_id = ?";
$stmt2 = mysqli_prepare($koneksi, $sqlItems);
if (!$stmt2) die("Prepare gagal: " . mysqli_error($koneksi));
mysqli_stmt_bind_param($stmt2, 'i', $orderId);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);

$items = [];
while ($row = mysqli_fetch_assoc($res2)) {
  $items[] = ['produk_id' => (int)$row['produk_id'], 'qty' => (int)$row['qty']];
}

mysqli_begin_transaction($koneksi);

try {
  // update status order
  $sqlUp = "UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'";
  $stmtUp = mysqli_prepare($koneksi, $sqlUp);
  if (!$stmtUp) throw new Exception("Prepare gagal: " . mysqli_error($koneksi));
  mysqli_stmt_bind_param($stmtUp, 'ii', $orderId, $userId);
  mysqli_stmt_execute($stmtUp);

  if (mysqli_stmt_affected_rows($stmtUp) <= 0) {
    throw new Exception("Gagal cancel (status sudah berubah).");
  }

  // restore stok ke inventory
  $sqlStok = "UPDATE inventory SET stok = stok + ?, updated_at = NOW() WHERE produk_id = ?";
  $stmtStok = mysqli_prepare($koneksi, $sqlStok);
  if (!$stmtStok) throw new Exception("Prepare stok gagal: " . mysqli_error($koneksi));

  foreach ($items as $it) {
    mysqli_stmt_bind_param($stmtStok, 'ii', $it['qty'], $it['produk_id']);
    mysqli_stmt_execute($stmtStok);
  }

  mysqli_commit($koneksi);

  header("Location: selesai_belanja.php?order_id=" . $orderId);
  exit;

} catch (Exception $e) {
  mysqli_rollback($koneksi);
  die("Cancel gagal: " . htmlspecialchars($e->getMessage()));
}
