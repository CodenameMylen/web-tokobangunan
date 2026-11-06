<?php
require_once __DIR__ . '/_admin_session.php';

if (empty($_SESSION['login_admin'])) {
    header("Location: login.php");
    exit;
}

include '../koneksi/koneksi.php';

$produk = mysqli_query(
    $koneksi,
    "SELECT p.id_produk, p.kode_stock, p.nama_stock, COALESCE(i.stok, p.qty) AS stok_now
     FROM produk p
     LEFT JOIN inventory i ON i.produk_id = p.id_produk
     ORDER BY p.id_produk DESC"
);

if (!$produk) die("Query produk gagal: " . mysqli_error($koneksi));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tambah Stok</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
    body { background:#0F1115; color:white; }
    .card-glass{
      background: rgba(26, 29, 35, 0.85);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255,255,255,0.06);
    }
    .accent-orange { color:#FF6600; }
    .bg-orange-maju { background:#FF6600; }
  </style>
</head>

<body class="min-h-screen flex items-center justify-center px-6 py-12">

  <div class="w-full max-w-2xl card-glass rounded-3xl shadow-[0_25px_80px_-35px_rgba(0,0,0,.85)] overflow-hidden">
    <div class="h-1 w-full bg-gradient-to-r from-orange-500/0 via-orange-500/70 to-orange-500/0"></div>

    <div class="p-10">
      <div class="flex items-center justify-between gap-4 mb-8">
        <h2 class="text-3xl font-extrabold tracking-tight">
          Tambah <span class="accent-orange">Stok</span>
        </h2>

        <a href="index.php"
           class="text-xs text-white/60 hover:text-orange-400 transition border border-white/10 px-4 py-2 rounded-xl bg-white/5">
          Kembali
        </a>
      </div>

      <form action="proses/proses_tambah_stok.php" method="POST" class="space-y-6">

        <div>
          <label class="text-xs text-white/60">Pilih Produk</label>
          <select name="produk_id" id="produk_id" required
                  class="mt-2 w-full bg-[#1A1D23] border border-gray-800 rounded-2xl px-4 py-3.5 outline-none
                         focus:border-orange-500/60 focus:ring-2 focus:ring-orange-500/20 transition">
            <option value="" disabled selected>-- Pilih produk --</option>
            <?php while($p = mysqli_fetch_assoc($produk)): ?>
              <option value="<?= (int)$p['id_produk'] ?>"
                      data-stok="<?= (int)$p['stok_now'] ?>">
                <?= htmlspecialchars($p['kode_stock']) ?> — <?= htmlspecialchars($p['nama_stock']) ?>
                (Stok: <?= (int)$p['stok_now'] ?>)
              </option>
            <?php endwhile; ?>
          </select>
          <p class="mt-2 text-[11px] text-white/40">Stok yang tampil = inventory.stok (kalau ada), kalau tidak ada pakai produk.qty.</p>
        </div>

        <div>
          <label class="text-xs text-white/60">Stok Saat Ini</label>
          <input type="text" id="stok_now" value="-" readonly
                 class="mt-2 w-full bg-[#1A1D23] border border-gray-800 rounded-2xl px-4 py-3.5 outline-none
                        opacity-80 cursor-not-allowed">
        </div>

        <div>
          <label class="text-xs text-white/60">Tambah Stok (Qty)</label>
          <input type="number" name="tambah_qty" required min="1" placeholder="Contoh: 10"
                 class="mt-2 w-full bg-[#1A1D23] border border-gray-800 rounded-2xl px-4 py-3.5 outline-none
                        focus:border-orange-500/60 focus:ring-2 focus:ring-orange-500/20 transition">
        </div>

        <button
          class="w-full bg-orange-maju py-4 rounded-2xl font-extrabold tracking-wide
                 shadow-[0_18px_50px_-25px_rgba(255,102,0,.9)]
                 hover:brightness-110 active:scale-[0.99] transition">
          Simpan Stok
        </button>

      </form>
    </div>
  </div>

  <script>
    const sel = document.getElementById('produk_id');
    const stokNow = document.getElementById('stok_now');

    function updateStok(){
      const opt = sel?.options[sel.selectedIndex];
      const s = opt?.getAttribute('data-stok');
      stokNow.value = (s !== null && s !== undefined) ? s : '-';
    }

    sel?.addEventListener('change', updateStok);
  </script>

</body>
</html>
