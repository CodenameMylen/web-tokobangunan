<?php
require_once __DIR__ . '/_admin_session.php';

if (empty($_SESSION['login_admin'])) {
    header("Location: login.php");
    exit;
}

include '../koneksi/koneksi.php';

/**
 * Ambil kode paling kecil yang belum dipakai.
 * Format: BRG-0001, BRG-0002, dst
 */
function getNextKode(mysqli $koneksi): string {
    $res = mysqli_query($koneksi, "SELECT kode_stock FROM produk");
    $used = [];

    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $k = (string)($r['kode_stock'] ?? '');
            // ambil angka dari kode (BRG-0006 => 6)
            if (preg_match('/(\d+)/', $k, $m)) {
                $used[(int)$m[1]] = true;
            }
        }
    }

    $n = 1;
    while (isset($used[$n])) $n++;

    return 'BRG-' . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
}

$kode_auto = getNextKode($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tambah Produk</title>
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
          Tambah <span class="accent-orange">Produk</span>
        </h2>

        <a href="index.php"
           class="text-xs text-white/60 hover:text-orange-400 transition border border-white/10 px-4 py-2 rounded-xl bg-white/5">
          Kembali
        </a>
      </div>

      <form action="proses/proses_tambah.php"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-6">

        <!-- Kode AUTO -->
        <div>
          <label class="text-xs text-white/60">Kode Barang (Otomatis)</label>
          <input type="text" name="kode_stock" value="<?= htmlspecialchars($kode_auto) ?>" readonly
                 class="mt-2 w-full bg-[#1A1D23] border border-gray-800 rounded-2xl px-4 py-3.5 outline-none
                        opacity-80 cursor-not-allowed">
          <p class="mt-2 text-[11px] text-white/40">Kode dibuat otomatis & mengisi nomor yang kosong.</p>
        </div>

        <!-- Nama -->
        <div>
          <label class="text-xs text-white/60">Nama Barang</label>
          <input type="text" name="nama_stock" required
                 placeholder="Contoh: Semen Tiga Roda 50kg"
                 class="mt-2 w-full bg-[#1A1D23] border border-gray-800 rounded-2xl px-4 py-3.5 outline-none
                        focus:border-orange-500/60 focus:ring-2 focus:ring-orange-500/20 transition">
        </div>

        <!-- Kategori -->
        <div>
          <label class="text-xs text-white/60">Kategori</label>
          <input type="text" name="milik" required
                 placeholder="Contoh: Semen / Cat / Besi"
                 class="mt-2 w-full bg-[#1A1D23] border border-gray-800 rounded-2xl px-4 py-3.5 outline-none
                        focus:border-orange-500/60 focus:ring-2 focus:ring-orange-500/20 transition">
        </div>

        <!-- Qty & Harga -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="text-xs text-white/60">Stok (Qty)</label>
            <input type="number" name="qty" required min="0"
                   placeholder="0"
                   class="mt-2 w-full bg-[#1A1D23] border border-gray-800 rounded-2xl px-4 py-3.5 outline-none
                          focus:border-orange-500/60 focus:ring-2 focus:ring-orange-500/20 transition">
          </div>

          <div>
            <label class="text-xs text-white/60">Harga</label>
            <input type="number" name="merk" required min="0"
                   placeholder="Contoh: 50000"
                   class="mt-2 w-full bg-[#1A1D23] border border-gray-800 rounded-2xl px-4 py-3.5 outline-none
                          focus:border-orange-500/60 focus:ring-2 focus:ring-orange-500/20 transition">
            <p class="mt-2 text-[11px] text-white/40">*Kamu pakai kolom <b>merk</b> untuk harga (biar tidak error).</p>
          </div>
        </div>

        <!-- Upload gambar -->
        <div>
          <label class="text-xs text-white/60">Gambar Produk</label>

          <label class="mt-2 block cursor-pointer rounded-2xl border border-dashed border-white/15 bg-white/5 p-6
                        hover:border-orange-500/40 hover:bg-orange-500/5 transition">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-2xl bg-orange-500/10 border border-orange-500/20 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M4 12l4-4a3 3 0 014 0l4 4M16 12l2-2a3 3 0 014 0l2 2"/>
                </svg>
              </div>

              <div>
                <p class="font-semibold">Klik untuk pilih gambar</p>
                <p class="text-xs text-white/50">JPG/PNG/WebP, rekomendasi 800×800</p>
              </div>
            </div>

            <input id="gambar" type="file" name="gambar" accept="image/*" required class="hidden">
          </label>

          <p id="fileName" class="mt-2 text-xs text-white/50"></p>
        </div>

        <!-- Tombol -->
        <button
          class="w-full bg-orange-maju py-4 rounded-2xl font-extrabold tracking-wide
                 shadow-[0_18px_50px_-25px_rgba(255,102,0,.9)]
                 hover:brightness-110 active:scale-[0.99] transition">
          Simpan Produk
        </button>
      </form>
    </div>
  </div>

  <script>
    const input = document.getElementById('gambar');
    const fileName = document.getElementById('fileName');
    input?.addEventListener('change', () => {
      fileName.textContent = input.files?.[0] ? `File dipilih: ${input.files[0].name}` : '';
    });
  </script>

</body>
</html>
