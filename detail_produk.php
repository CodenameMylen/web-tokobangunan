<?php
session_start();
require_once __DIR__ . '/user/auth.php';
$loggedIn = is_logged_in();

require_once 'koneksi/koneksi.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cartCount = count($_SESSION['cart']); // jumlah jenis produk (distinct)

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    die("ID produk tidak valid.");
}

$sql = "
  SELECT p.*, COALESCE(i.stok, p.qty, 0) AS stok
  FROM produk p
  LEFT JOIN inventory i ON i.produk_id = p.id_produk
  WHERE p.id_produk = ?
  LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($result);

if (!$produk) {
    http_response_code(404);
    die("Produk tidak ditemukan.");
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$imgFile = trim($produk['gambar'] ?? '');

/* sesuaikan nama folder project */
$BASE_URL = "/web_tokobangunan";

/* daftar folder yang mungkin untuk gambar */
$CANDIDATE_DIRS = [
  "uploads",
  "upload",
  "assets/uploads",
  "assets/img",
  "assets/images",
  "img",
  "images",
];

$imgPath = $BASE_URL . "/assets/no-image.png"; // default fallback

if ($imgFile !== '') {
  foreach ($CANDIDATE_DIRS as $dir) {
    $abs = __DIR__ . "/" . $dir . "/" . $imgFile;   // cek fisik file di server
    if (file_exists($abs)) {
      $imgPath = $BASE_URL . "/" . $dir . "/" . $imgFile; // URL yang benar
      break;
    }
  }
}

$stokReal = (int)($produk['stok'] ?? 0);

/**
 * ✅ PENTING:
 * Karena kamu minta DETAIL mengikuti PRODUK.PHP (gambar 1),
 * maka "Kategori" di detail ambil dari kolom yang sama seperti di produk.php.
 * Biasanya produk.php kamu pakai p.milik untuk kategori.
 */
$kategori = trim((string)($produk['milik'] ?? '')); // <-- INI YANG DIUBAH (dari merk -> milik)
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Detail Produk - <?= e($produk['kode_stock']) ?></title>

  <!-- Tailwind CDN (buat dev/local) -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Font Awesome (icon) -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <style>
    /* Anti "kedip" / layout shift: kasih tinggi tetap */
    .img-wrap { height: 420px; }
    @media (max-width: 1024px){ .img-wrap { height: 320px; } }
  </style>
</head>

<body class="bg-[#0b1020] text-slate-100">

  <!-- HEADER -->
  <header class="sticky top-0 z-50 border-b border-white/10 bg-[#0b1020]/70 backdrop-blur-xl">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
      <div class="flex items-center gap-3">
        <div class="grid h-10 w-10 place-items-center rounded-2xl bg-orange-500 text-slate-900">
          <i class="fa-solid fa-hammer"></i>
        </div>
        <div class="font-extrabold italic tracking-wide">MAJUUSAHA BANGUNAN</div>
      </div>

      <a href="keranjang.php"
        class="relative grid h-11 w-11 place-items-center rounded-2xl border border-white/10 bg-white/5 hover:border-white/20 transition"
        title="Keranjang">
        <i class="fa-solid fa-cart-shopping"></i>

        <?php if ($cartCount > 0): ?>
          <span class="absolute -top-2 -right-2 bg-orange-500 text-[10px] font-black px-2 py-1 rounded-full shadow-lg border border-orange-500/30">
            <?= (int)$cartCount ?>
          </span>
        <?php endif; ?>
      </a>

    </div>
  </header>

  <main class="mx-auto max-w-6xl px-6 pt-10 pb-20">
    <!-- HERO -->
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <div class="inline-flex items-center gap-2 rounded-full border border-orange-500/30 bg-orange-500/10 px-4 py-2 text-xs font-extrabold tracking-widest text-orange-400 uppercase">
          <span class="h-2 w-2 rounded-full bg-orange-500"></span>
          Detail Produk
        </div>

        <h1 class="mt-4 text-5xl font-black uppercase tracking-tight leading-none">
          PRODUK <span class="text-orange-500">ANDA</span>
        </h1>
        <p class="mt-2 text-xs uppercase tracking-widest text-slate-400">
          Lihat detail, stok, dan informasi produk.
        </p>
      </div>

      <a href="produk.php"
        class="inline-flex items-center gap-3 rounded-2xl border border-white/15 bg-white/5 px-5 py-3 font-extrabold uppercase tracking-wider hover:border-white/25 transition">
        <i class="fa-solid fa-arrow-left"></i>
        Kembali Belanja
      </a>
    </div>

    <!-- GRID -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-12">
      <!-- IMAGE -->
      <div class="lg:col-span-5">
        <div class="rounded-[28px] border border-white/10 bg-white/5 p-4 backdrop-blur-xl">
          <div class="img-wrap overflow-hidden rounded-2xl bg-white/5">
            <img src="<?= e($imgPath) ?>" alt="Gambar Produk" class="h-full w-full object-cover">
          </div>
        </div>
      </div>

      <!-- DETAIL -->
      <div class="lg:col-span-7">
        <div class="h-full rounded-[28px] border border-white/10 bg-white/5 p-7 backdrop-blur-xl">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-3xl font-black"><?= e($produk['nama_stock']) ?></h2>
              <p class="mt-1 text-sm text-slate-300">
                Kategori: <span class="font-extrabold text-slate-100"><?= e($kategori !== '' ? $kategori : '-') ?></span>
              </p>
            </div>

            <div class="shrink-0 rounded-full bg-orange-500 px-5 py-2 text-sm font-black tracking-wider text-white">
              <?= e($produk['kode_stock']) ?>
            </div>
          </div>

          <div class="my-6 h-px w-full bg-white/10"></div>

          <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
              <div class="text-xs uppercase tracking-widest text-slate-400">Qty / Stok</div>
              <div class="mt-1 text-lg font-black"><?= $stokReal ?></div>
            </div>

            <div>
              <div class="text-xs uppercase tracking-widest text-slate-400">Harga</div>
              <div class="mt-1 text-3xl font-black tracking-tight">
                Rp <?= number_format((int)$produk['harga'], 0, ',', '.') ?>
              </div>
            </div>

            <div>
              <div class="text-xs uppercase tracking-widest text-slate-400">Status</div>
              <div class="mt-1 text-lg font-black">
                <?php if ($stokReal > 0 && (int)($produk['status'] ?? 1) === 1): ?>
                  <span class="text-green-400">Tersedia</span>
                <?php else: ?>
                  <span class="text-red-400">Tidak Tersedia</span>
                <?php endif; ?>
              </div>
            </div>

            <div class="md:col-span-2">
              <div class="text-xs uppercase tracking-widest text-slate-400">Tanggal ditambahkan</div>
              <div class="mt-1 font-bold text-slate-200"><?= e($produk['created_at'] ?? '-') ?></div>
            </div>
          </div>

          <div class="mt-7 flex flex-wrap gap-3">
            <?php if ($stokReal > 0): ?>
              <a href="proses/keranjang_tambah.php?id=<?= (int)$produk['id_produk'] ?>"
                id="btnTambahDetail"
                class="inline-flex items-center gap-3 rounded-2xl bg-orange-500 px-6 py-3 font-extrabold text-white hover:opacity-90 transition">
                <i class="fa-solid fa-cart-plus"></i> Tambah
              </a>
            <?php else: ?>
              <button type="button"
                class="inline-flex items-center gap-3 rounded-2xl bg-white/10 px-6 py-3 font-extrabold text-white/50 cursor-not-allowed">
                <i class="fa-solid fa-ban"></i> Stok Habis
              </button>
            <?php endif; ?>

            <a href="produk.php"
              class="inline-flex items-center gap-3 rounded-2xl border border-white/15 bg-white/5 px-6 py-3 font-extrabold hover:border-white/25 transition">
              <i class="fa-solid fa-list"></i> Kembali ke daftar
            </a>
          </div>

        </div>
      </div>
    </div>
  </main>

  <!-- MODAL WARNING LOGIN -->
  <div id="loginWarnModal" class="fixed inset-0 z-[999] hidden">
    <div id="loginWarnBackdrop" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

    <div class="relative mx-auto mt-24 w-[92%] max-w-md">
      <div class="rounded-[28px] border border-white/10 bg-[#0F1115]/95 shadow-2xl p-6">
        <div class="flex items-start justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-orange-500/15 border border-orange-500/20 flex items-center justify-center">
              <i class="fa-solid fa-triangle-exclamation text-orange-500 text-xl"></i>
            </div>
            <div>
              <h3 class="text-white font-black uppercase tracking-widest text-sm">Peringatan</h3>
              <p class="text-white/70 text-xs mt-1">
                Anda belum login, silahkan login untuk melanjutkan belanja.
              </p>
            </div>
          </div>

          <button id="loginWarnClose"
            class="w-10 h-10 rounded-2xl border border-white/10 hover:border-orange-500/40 text-white/70 hover:text-orange-500 transition flex items-center justify-center"
            aria-label="Tutup">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="mt-6 flex gap-3">
          <a href="user/login.php"
            class="flex-1 bg-orange-600 px-5 py-3 rounded-2xl font-black uppercase text-[11px] tracking-widest hover:scale-[1.01] transition text-center">
            Login
          </a>
          <a href="user/daftar.php"
            class="flex-1 bg-[#1f2937] px-5 py-3 rounded-2xl font-black uppercase text-[11px] tracking-widest border border-white/10 hover:scale-[1.01] transition text-center">
            Daftar
          </a>
        </div>
      </div>
    </div>
  </div>

  <script>
  (function () {
    const loggedIn = <?php echo json_encode($loggedIn); ?>;
    const modal = document.getElementById('loginWarnModal');
    const closeBtn = document.getElementById('loginWarnClose');
    const backdrop = document.getElementById('loginWarnBackdrop');
    const btnTambah = document.getElementById('btnTambahDetail');

    function openModal() {
      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }
    function closeModal() {
      modal.classList.add('hidden');
      document.body.style.overflow = '';
    }

    btnTambah?.addEventListener('click', function (e) {
      if (!loggedIn) {
        e.preventDefault();
        openModal();
      }
    });

    closeBtn?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeModal();
    });
  })();
  </script>

</body>
</html>