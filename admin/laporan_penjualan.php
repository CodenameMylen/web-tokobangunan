<?php
require_once __DIR__ . '/_admin_session.php';

if (empty($_SESSION['login_admin'])) {
    header("Location: login.php");
    exit;
}

include '../koneksi/koneksi.php';

$username = $_SESSION['username'] ?? 'Administrator';
$current = basename($_SERVER['PHP_SELF']);

// default range: 30 hari terakhir
$to = $_GET['to'] ?? date('Y-m-d');
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Penjualan</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<style>
  body { background:#0F1115; color:white; }
  .card-glass { background: rgba(26,29,35,.8); backdrop-filter: blur(10px); border:1px solid rgba(255,255,255,.05); }
  .accent-orange { color:#FF6600; }
  .bg-orange-maju { background:#FF6600; }
  input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1) brightness(1.5);
    opacity: 0.8;
    cursor: pointer;
    }

    input[type="date"]::-webkit-calendar-picker-indicator:hover {
        opacity: 1;
    }
</style>
</head>
<body class="font-sans">
<div class="flex h-screen">

<!-- SIDEBAR (paste sidebar kamu di sini) -->
<aside class="w-72 bg-[#1A1D23] border-r border-gray-800 flex flex-col p-6">
  <div class="flex items-center gap-3 mb-10">
    <div class="bg-orange-maju p-2 rounded-lg"><i data-lucide="hammer"></i></div>
    <h1 class="font-bold text-xl italic">MAJU <span class="accent-orange">USAHA</span></h1>
  </div>

  <nav class="space-y-2 flex-1">
    <div class="space-y-2">
      <p class="text-[10px] text-gray-500 uppercase tracking-widest px-3">Manajemen Produk</p>
      <a href="index.php" class="flex items-center gap-3 p-3 rounded-xl <?= $current==='index.php'?'bg-orange-maju/10 text-orange-500 font-bold':'text-gray-400 hover:bg-gray-800' ?>">
        <i data-lucide="layout-dashboard"></i> Dashboard
      </a>
      <a href="tambah_produk.php" class="flex items-center gap-3 p-3 rounded-xl <?= $current==='tambah_produk.php'?'bg-orange-maju/10 text-orange-500 font-bold':'text-gray-400 hover:bg-gray-800' ?>">
        <i data-lucide="plus-square"></i> Tambah Barang
      </a>
    </div>

    <div class="space-y-2">
      <p class="text-[10px] text-gray-500 uppercase tracking-widest px-3">Manajemen Website</p>
      <a href="users.php" class="flex items-center gap-3 p-3 rounded-xl <?= $current==='users.php'?'bg-orange-maju/10 text-orange-500 font-bold':'text-gray-400 hover:bg-gray-800' ?>">
        <i data-lucide="users"></i> Kelola Users
      </a>
      <a href="page_edit.php" class="flex items-center gap-3 p-3 rounded-xl <?= $current==='page_edit.php'?'bg-orange-maju/10 text-orange-500 font-bold':'text-gray-400 hover:bg-gray-800' ?>">
        <i data-lucide="file-text"></i> Kelola Page User
      </a>
    </div>

    <div class="space-y-2">
      <p class="text-[10px] text-gray-500 uppercase tracking-widest px-3">Pemesanan Produk</p>
      <a href="pengelolaan_pesanan.php" class="flex items-center gap-3 p-3 rounded-xl <?= $current==='pengelolaan_pesanan.php'?'bg-orange-maju/10 text-orange-500 font-bold':'text-gray-400 hover:bg-gray-800' ?>">
        <i data-lucide="shopping-cart"></i> Pesanan
      </a>
      <a href="laporan_penjualan.php" class="flex items-center gap-3 p-3 rounded-xl <?= $current==='laporan_penjualan.php'?'bg-orange-maju/10 text-orange-500 font-bold':'text-gray-400 hover:bg-gray-800' ?>">
        <i data-lucide="download"></i> Laporan
      </a>
    </div>
  </nav>

  <div class="border-t border-gray-800 pt-4">
    <a href="logout.php" class="flex items-center gap-3 text-red-500 p-3 rounded-xl hover:bg-red-500/10">
      <i data-lucide="log-out"></i> Keluar
    </a>
  </div>
</aside>

<!-- MAIN -->
<main class="flex-1 overflow-y-auto p-10">
  <header class="flex justify-between items-center mb-8">
    <div>
      <h2 class="text-2xl font-bold">Laporan <span class="accent-orange">Penjualan</span></h2>
    </div>
    <div class="text-right">
      <p class="font-bold"><?= htmlspecialchars($username) ?></p>
      <p class="text-[10px] text-orange-500 uppercase">Administrator</p>
    </div>
  </header>

  <div class="card-glass rounded-3xl p-6">
    <form method="get" class="flex flex-wrap items-end gap-4">
      <div>
        <label class="block text-xs text-gray-400 mb-2">Dari</label>
        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"
               class="bg-[#0F1115] border border-gray-800 rounded-2xl px-4 py-3 text-sm outline-none">
      </div>

      <div>
        <label class="block text-xs text-gray-400 mb-2">Sampai</label>
        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"
               class="bg-[#0F1115] border border-gray-800 rounded-2xl px-4 py-3 text-sm outline-none">
      </div>

      <button class="px-6 py-3 rounded-2xl bg-orange-maju/10 text-orange-400 border border-orange-500/20 hover:bg-orange-maju/20 text-sm flex items-center gap-2">
        <i data-lucide="filter" size="16"></i> Terapkan
      </button>

      <div class="flex gap-3 ml-auto">
        <a class="px-6 py-3 rounded-2xl bg-[#1A1D23] border border-gray-800 hover:bg-gray-800 text-sm flex items-center gap-2"
           href="export_laporan.php?format=xls&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">
          <i data-lucide="file-spreadsheet" size="16"></i> Download Excel
        </a>

        <a class="px-6 py-3 rounded-2xl bg-[#1A1D23] border border-gray-800 hover:bg-gray-800 text-sm flex items-center gap-2"
           href="export_laporan.php?format=pdf&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">
          <i data-lucide="file-text" size="16"></i> Download PDF
        </a>
      </div>
    </form>
  </div>
</main>
</div>

<script>lucide.createIcons();</script>
</body>
</html>
