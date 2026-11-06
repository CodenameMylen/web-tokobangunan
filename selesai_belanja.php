<?php
session_start();
include __DIR__ . '/koneksi/koneksi.php';
require_once __DIR__ . '/user/auth.php';

// anti-cache agar back tidak menampilkan halaman lama
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// wajib login
if (!is_logged_in()) {
    header("Location: user/login.php");
    exit;
}

// halaman ini hanya boleh muncul setelah checkout sukses
if (empty($_SESSION['checkout_success'])) {
    header("Location: produk.php");
    exit;
}

// order id dari session (lebih aman daripada query string)
$orderId = (int)($_SESSION['last_order_id'] ?? 0);
if ($orderId <= 0) {
    header("Location: produk.php");
    exit;
}

// flag dipakai sekali saja (biar kalau refresh/back jadi tidak bisa akses lagi)
unset($_SESSION['checkout_success']);

// validasi user
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    header("Location: user/login.php");
    exit;
}

// ambil order (pastikan milik user)
$sqlOrder = "SELECT id, user_id, total_harga, alamat, metode_pembayaran, status, created_at, no_resi
             FROM orders
             WHERE id = ? AND user_id = ?
             LIMIT 1";
$stmt = mysqli_prepare($koneksi, $sqlOrder);
if (!$stmt) die("Prepare gagal: " . mysqli_error($koneksi));
mysqli_stmt_bind_param($stmt, 'ii', $orderId, $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$order) {
    header("Location: produk.php");
    exit;
}

// ambil items
$sqlItems = "SELECT oi.produk_id, oi.harga, oi.qty, p.nama_stock
             FROM order_items oi
             JOIN produk p ON p.id_produk = oi.produk_id
             WHERE oi.order_id = ?
             ORDER BY oi.id ASC";
$stmt2 = mysqli_prepare($koneksi, $sqlItems);
if (!$stmt2) die("Prepare gagal: " . mysqli_error($koneksi));
mysqli_stmt_bind_param($stmt2, 'i', $orderId);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);

$items = [];
$totalBarang = 0;
while ($row = mysqli_fetch_assoc($res2)) {
    $row['harga'] = (int)$row['harga'];
    $row['qty']   = (int)$row['qty'];
    $row['subtotal'] = $row['harga'] * $row['qty'];
    $totalBarang += $row['subtotal'];
    $items[] = $row;
}
mysqli_stmt_close($stmt2);

$grandTotal = (int)$order['total_harga'];
$ongkir = max(0, $grandTotal - $totalBarang);

function rupiah($n) {
    return 'Rp ' . number_format((int)$n, 0, ',', '.');
}

$status = strtolower($order['status']);
$metode = strtolower($order['metode_pembayaran']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Selesai Belanja</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>

<body>

  <!-- NAVBAR (ikut style website) -->
  <nav class="fixed w-full z-50 glass py-5">
    <div class="max-w-7xl mx-auto px-8 flex items-center justify-between">
      <div class="flex items-center gap-12">
        <div class="flex items-center gap-3">
          <div class="bg-orange-600 p-2 rounded-xl"><i class="fas fa-hammer text-white"></i></div>
          <a href="index.php" class="font-extrabold text-sm tracking-tight uppercase italic">
            MAJU USAHA BANGUNAN
          </a>
        </div>

        <div class="hidden lg:flex gap-10 text-[10px] font-bold uppercase tracking-widest">
          <a href="index.php" class="text-gray-400 hover:text-orange-500 transition">Beranda</a>
          <a href="produk.php" class="text-gray-400 hover:text-orange-500 transition">Katalog Produk</a>
          <a href="layanan.php" class="text-gray-400 hover:text-orange-500 transition">Layanan</a>
          <a href="cara-pemesanan.php" class="text-gray-400 hover:text-orange-500 transition">Cara Pemesanan</a>
        </div>
      </div>

      <div class="hidden sm:flex items-center gap-4 text-[10px] font-bold uppercase tracking-widest italic">
        <span class="text-gray-500">Kabupaten Kampar, Riau</span>
        <span class="text-white/10">|</span>
        <span class="text-orange-500">Halo, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
        <span class="text-white/10">|</span>
        <a href="user/riwayat.php" class="text-gray-400 hover:text-orange-500 transition">Riwayat</a>
        <span class="text-white/10">|</span>
        <a href="user/logout.php" class="text-gray-400 hover:text-orange-500 transition">Logout</a>
      </div>
    </div>
  </nav>

  <section class="pt-32 pb-20 px-8 max-w-4xl mx-auto min-h-screen">
    <div class="glass rounded-[3rem] p-10 border border-white/10 relative overflow-hidden">
      <div class="absolute -top-24 -right-24 w-72 h-72 bg-orange-500/10 rounded-full blur-3xl"></div>

      <div class="relative z-10">
        <div class="flex items-start justify-between gap-6">
          <div>
            <h1 class="text-3xl md:text-4xl font-black uppercase italic tracking-tight text-orange-500">
              Selesai Belanja
            </h1>
            <p class="text-gray-400 text-xs uppercase tracking-widest italic mt-2">
              Pesanan kamu sudah tercatat. Berikut ringkasan belanja.
            </p>
          </div>

          <div class="text-right">
            <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic">Order ID</p>
            <p class="text-xl font-black"><?php echo (int)$order['id']; ?></p>

            <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic mt-3">Status</p>
            <p class="text-sm font-black <?php echo ($status === 'pending') ? 'text-orange-400' : 'text-gray-300'; ?>">
              <?php echo htmlspecialchars($order['status']); ?>
            </p>
            <?php if (!empty($order['no_resi'])): ?>
              <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic mt-3">
                Nomor Resi
              </p>
              <p class="text-sm font-black text-orange-400">
                <?php echo htmlspecialchars($order['no_resi']); ?>
              </p>
            <?php endif; ?>
          </div>
        </div>

        <div class="mt-10 grid md:grid-cols-2 gap-6">
          <div class="glass-inner rounded-2xl p-6 border border-white/10">
            <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic">Metode</p>
            <p class="text-lg font-black mt-2">
              <?php echo ($metode === 'pickup') ? 'Ambil di Tempat' : 'COD (Bayar di Tempat)'; ?>
            </p>

            <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic mt-5">Alamat</p>
            <p class="text-sm text-gray-200 mt-2 whitespace-pre-line">
              <?php echo htmlspecialchars($order['alamat']); ?>
            </p>
          </div>

          <div class="glass-inner rounded-2xl p-6 border border-white/10">
            <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic">Waktu</p>
            <p class="text-sm font-bold mt-2"><?php echo htmlspecialchars($order['created_at']); ?></p>

            <div class="mt-6 space-y-3">
              <div class="flex justify-between text-sm">
                <span class="text-gray-400">Subtotal Barang</span>
                <span class="font-black"><?php echo rupiah($totalBarang); ?></span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-400">Ongkir</span>
                <span class="font-black text-orange-400"><?php echo rupiah($ongkir); ?></span>
              </div>
              <div class="h-px bg-white/10 my-2"></div>
              <div class="flex justify-between text-lg">
                <span class="text-gray-300 font-black uppercase italic tracking-widest">Total</span>
                <span class="font-black text-orange-500"><?php echo rupiah($grandTotal); ?></span>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-8 glass-inner rounded-2xl p-6 border border-white/10">
          <h2 class="text-sm font-black uppercase italic tracking-widest text-orange-500 mb-4">
            Detail Belanja
          </h2>

          <div class="space-y-3">
            <?php foreach ($items as $it): ?>
              <div class="flex items-center justify-between gap-4 border border-white/10 rounded-xl p-4 bg-white/[0.03]">
                <div class="min-w-0">
                  <p class="font-black uppercase italic tracking-widest text-sm truncate">
                    <?php echo htmlspecialchars($it['nama_stock']); ?>
                  </p>
                  <p class="text-gray-400 text-[10px] uppercase tracking-widest italic mt-2">
                    Harga: <?php echo rupiah($it['harga']); ?>
                    <span class="text-white/10 mx-2">|</span>
                    Qty: <?php echo (int)$it['qty']; ?>
                  </p>
                </div>
                <div class="text-right shrink-0">
                  <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic">Subtotal</p>
                  <p class="font-black text-orange-400"><?php echo rupiah($it['subtotal']); ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- HANYA 2 PILIHAN: CANCEL & KONFIRMASI -->
        <div class="mt-10 flex flex-col sm:flex-row gap-4">
          <?php if ($status === 'pending'): ?>
            <a href="user/cancel_order.php?order_id=<?php echo (int)$orderId; ?>"
              class="flex-1 text-center px-8 py-4 rounded-2xl bg-black/40 border border-red-500/40 hover:border-red-400 transition font-black uppercase tracking-widest text-sm text-red-300">
              <i class="fa-solid fa-ban mr-2"></i> Cancel
            </a>
          <?php else: ?>
            <a href="produk.php"
              class="flex-1 text-center px-8 py-4 rounded-2xl bg-black/30 border border-white/10 transition font-black uppercase tracking-widest text-sm text-gray-300">
              Cancel
            </a>
          <?php endif; ?>

          <a href="produk.php"
             class="flex-1 text-center px-8 py-4 rounded-2xl bg-orange-600 hover:bg-orange-500 transition font-black uppercase tracking-widest text-sm orange-glow">
            <i class="fa-solid fa-check mr-2"></i> Konfirmasi
          </a>
        </div>

      </div>
    </div>
  </section>

  <!-- BACK HARUS KE produk.php -->
  <script>
    // tambah state supaya tombol back bisa kita tangkap
    history.pushState(null, "", location.href);

    window.addEventListener("popstate", function () {
      location.replace("produk.php");
    });

    // kalau halaman muncul dari BFCache (back/forward cache), lempar ke produk
    window.addEventListener("pageshow", function (e) {
      if (e.persisted) {
        location.replace("produk.php");
      }
    });
  </script>

</body>
</html>
