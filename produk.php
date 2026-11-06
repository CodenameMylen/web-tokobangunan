<?php
session_start();
require_once __DIR__ . '/user/auth.php';
$loggedIn = is_logged_in();

// pastikan cart selalu array
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// normalisasi cart jika format lama berupa list
if ($_SESSION['cart'] !== [] && array_keys($_SESSION['cart']) === range(0, count($_SESSION['cart']) - 1)) {
    $fixed = [];
    foreach ($_SESSION['cart'] as $pid) {
        $pid = (int)$pid;
        if ($pid > 0) $fixed[$pid] = ($fixed[$pid] ?? 0) + 1;
    }
    $_SESSION['cart'] = $fixed;
}

// jumlah jenis produk (distinct)
$cartCount = count($_SESSION['cart']);

include 'koneksi/koneksi.php';

$brandShort = "Maju Usaha Bangunan";

function get_table_columns(mysqli $db, string $table): array {
    $cols = [];
    $res = mysqli_query($db, "SHOW COLUMNS FROM `$table`");
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) $cols[] = $r['Field'];
    }
    return $cols;
}

$produkCols    = get_table_columns($koneksi, 'produk');
$inventoryCols = get_table_columns($koneksi, 'inventory');

// Deteksi kolom ID pada tabel produk
$produkIdCandidates = ['id_produk','produk_id','id','idbarang','id_barang'];
$produkIdCol = null;
foreach ($produkIdCandidates as $c) {
    if (in_array($c, $produkCols, true)) { $produkIdCol = $c; break; }
}
if (!$produkIdCol) {
    die("Tidak menemukan kolom ID di tabel produk. Kolom yang ada: " . htmlspecialchars(json_encode($produkCols)));
}

// Deteksi kolom relasi di tabel inventory (yang menunjuk ke produk)
$invKeyCandidates = ['produk_id','id_produk','id','idbarang','id_barang'];
$inventoryKeyCol = null;
foreach ($invKeyCandidates as $c) {
    if (in_array($c, $inventoryCols, true)) { $inventoryKeyCol = $c; break; }
}
if (!$inventoryKeyCol) {
    die("Tidak menemukan kolom key di tabel inventory. Kolom yang ada: " . htmlspecialchars(json_encode($inventoryCols)));
}

// Kolom stok di inventory
$stokCol = in_array('stok', $inventoryCols, true) ? 'stok' : null;
if (!$stokCol) {
    die("Kolom 'stok' tidak ditemukan di tabel inventory. Kolom yang ada: " . htmlspecialchars(json_encode($inventoryCols)));
}

/*
|--------------------------------------------------
| SEARCH
|--------------------------------------------------
*/
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

/*
|--------------------------------------------------
| SORTING (whitelist)
|--------------------------------------------------
*/
$sort = $_GET['sort'] ?? 'terbaru';

$hasCreatedAt = in_array('created_at', $produkCols, true);
switch ($sort) {
    case 'nama_asc':
        $orderBy = "p.nama_stock ASC";
        break;
    case 'nama_desc':
        $orderBy = "p.nama_stock DESC";
        break;
    case 'stok_asc':
        $orderBy = "stok ASC";
        break;
    case 'stok_desc':
        $orderBy = "stok DESC";
        break;
    default:
        // terbaru: pakai created_at kalau ada, kalau tidak pakai id_produk DESC
        $orderBy = $hasCreatedAt ? "p.created_at DESC" : "p.`$produkIdCol` DESC";
        break;
}

/*
|--------------------------------------------------
| ADD TO CART (stok = COALESCE(inventory.stok, produk.qty, 0))
|--------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {

    // kalau belum login: jangan tambah ke cart, tampilkan warning
    if (!is_logged_in()) {
        $current = $_SERVER['REQUEST_URI'] ?? 'produk.php';
        set_redirect_after_login($current);

        $qs = $_SERVER['QUERY_STRING'] ?? '';
        $base = "produk.php" . ($qs ? "?$qs" : "");
        $sep = (strpos($base, '?') !== false) ? '&' : '?';
        header("Location: {$base}{$sep}need_login=1");
        exit;
    }

    $produkId = (int)($_POST['produk_id'] ?? 0);

    // ✅ cek stok konsisten seperti admin: inventory.stok fallback produk.qty
    $sqlStok = "
        SELECT COALESCE(i.`$stokCol`, p.qty, 0) AS stok
        FROM produk p
        LEFT JOIN inventory i ON i.`$inventoryKeyCol` = p.`$produkIdCol`
        WHERE p.`$produkIdCol` = ?
        LIMIT 1
    ";
    $stmt = mysqli_prepare($koneksi, $sqlStok);
    if (!$stmt) die("Prepare stok error: " . htmlspecialchars(mysqli_error($koneksi)));

    mysqli_stmt_bind_param($stmt, "i", $produkId);
    mysqli_stmt_execute($stmt);
    $row  = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $stok = $row ? (int)$row['stok'] : 0;
    mysqli_stmt_close($stmt);

    if ($produkId > 0 && $stok > 0) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $_SESSION['cart'][$produkId] = ($_SESSION['cart'][$produkId] ?? 0) + 1;
        if ($_SESSION['cart'][$produkId] > $stok) $_SESSION['cart'][$produkId] = $stok;
    }

    $qs = $_SERVER['QUERY_STRING'] ?? '';
    $base = "produk.php" . ($qs ? "?$qs" : "");
    $sep  = (strpos($base, '?') !== false) ? '&' : '?';

    header("Location: {$base}{$sep}added=1");
    exit;


}
/*
|--------------------------------------------------
| QUERY PRODUK + INVENTORY (stok fallback + harga ikut)
|--------------------------------------------------
*/
$sql = "
    SELECT
        p.`$produkIdCol` AS produk_id,
        p.kode_stock,
        p.nama_stock,
        p.milik,
        p.gambar,
        p.harga,
        COALESCE(i.`$stokCol`, p.qty, 0) AS stok
    FROM produk p
    LEFT JOIN inventory i ON i.`$inventoryKeyCol` = p.`$produkIdCol`
    WHERE COALESCE(p.status,1) = 1
";

$params = [];
$types  = "";

if ($keyword !== '') {
    $sql .= " AND (p.nama_stock LIKE ? OR p.kode_stock LIKE ? OR p.milik LIKE ?)";
    $like = "%{$keyword}%";
    $params = [$like, $like, $like];
    $types  = "sss";
}

$sql .= " ORDER BY $orderBy";

$stmt = mysqli_prepare($koneksi, $sql);
if (!$stmt) {
    die("Query prepare error: " . htmlspecialchars(mysqli_error($koneksi)) . "<br><pre>" . htmlspecialchars($sql) . "</pre>");
}
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);

/*
|--------------------------------------------------
| PATH gambar upload (relatif)
|--------------------------------------------------
*/
$uploadDirCandidates = [
    __DIR__ . '/assets/uploads/',
    __DIR__ . '/uploads/',
    __DIR__ . '/assets/img/',
    __DIR__ . '/img/',
];

$uploadUrlCandidates = [
    'assets/uploads/',
    'uploads/',
    'assets/img/',
    'img/',
];

function findImageUrl(string $filename, array $dirs, array $urls): string {
    $filename = trim($filename);
    if ($filename === '') return 'gambar_profil/default.jpg';

    // kalau di DB sudah berupa URL/path lengkap
    if (preg_match('~^(https?://|/|assets/|uploads/|img/)~i', $filename)) {
        return $filename;
    }

    foreach ($dirs as $i => $dir) {
        if (file_exists($dir . $filename)) {
            return $urls[$i] . $filename;
        }
    }
    return 'gambar_profil/default.jpg';
}


// CART COUNT
$cartCount = count($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Produk Kami</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/produk.css">

    <style>
        .sort-box{
            display:inline-flex;align-items:center;gap:10px;padding:10px 14px;border-radius:18px;
            border:1px solid rgba(255,255,255,.10);background:rgba(15,17,21,.65);
            backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);position:relative
        }
        .sort-box i{color:rgba(249,115,22,.9);font-size:12px}
        .sort-box select{
            color:#fff;background:transparent;border:none;outline:none;font-weight:800;font-size:12px;
            text-transform:uppercase;letter-spacing:.12em;padding-right:28px;cursor:pointer;
            appearance:none;-webkit-appearance:none;-moz-appearance:none;
        }
        .sort-box::after{
            content:"\f078";font-family:"Font Awesome 6 Free";font-weight:900;
            position:absolute;right:14px;top:50%;transform:translateY(-50%);
            color:rgba(255,255,255,.55);pointer-events:none;
        }
        .sort-box select option{background:#0F1115;color:#fff}
        .sort-box:focus-within{border-color:rgba(249,115,22,.45);box-shadow:0 0 0 6px rgba(249,115,22,.12)}

        /* ✅ BARIS HEADER "Nama | Kategori | Harga | Stok" */
        .produk-head-row{
            display:grid;
            grid-template-columns: 2fr 1.4fr 1.2fr 1.2fr 1.4fr;
            gap:12px;
            padding:14px 18px;
            border:1px solid rgba(255,255,255,.08);
            background:rgba(0,0,0,.25);
            border-radius:18px;
            margin-bottom:14px;
            color:rgba(255,255,255,.65);
            font-size:10px;
            text-transform:uppercase;
            letter-spacing:.14em;
            font-weight:900;
        }

        /* ✅ ROW PRODUK seperti tabel */
        .produk-row{
            display:grid;
            grid-template-columns: 2fr 1.4fr 1.2fr 1.2fr 1.4fr;
            gap:12px;
            align-items:center;
            padding:14px 18px;
            border:1px solid rgba(255,255,255,.08);
            background:rgba(255,255,255,.03);
            border-radius:18px;
            margin-bottom:12px;
        }

        .produk-row:hover{ background:rgba(255,255,255,.05); }

        .col-img{
            display:flex;align-items:center;gap:12px;
            min-width:0;
        }
        .col-img img{width:46px;height:46px;object-fit:cover;border-radius:12px;border:1px solid rgba(255,255,255,.12)}
        .kode-badge{
            display:inline-flex;
            padding:3px 10px;
            border-radius:999px;
            font-size:10px;
            font-weight:900;
            background:rgba(249,115,22,.12);
            color:rgb(249,115,22);
            border:1px solid rgba(249,115,22,.25);
            margin-top:6px;
        }
        .nama{
            font-weight:900;
            color:#fff;
            font-size:14px;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
            max-width:100%;
        }
        .kategori{ color:rgba(255,255,255,.75); font-weight:800; font-size:12px; }
        .harga{ color:#fff; font-weight:900; font-size:13px; }
        .aksi-wrap{ display:flex; gap:10px; justify-content:flex-end; }

        @media (max-width: 900px){
            .produk-head-row{ display:none; }
            .produk-row{
                grid-template-columns: 1fr;
                gap:10px;
            }
            .aksi-wrap{ justify-content:flex-start; }
        }
    </style>
</head>

<body>

<nav class="fixed w-full z-50 glass py-5">
    <div class="max-w-7xl mx-auto px-8 flex items-center justify-between">

        <div class="flex items-center gap-12">
            <a href="index.php" class="flex items-center gap-3">
                <div class="bg-orange-600 p-2 rounded-xl">
                    <i class="fas fa-hammer text-white"></i>
                </div>
                <span class="font-extrabold text-sm tracking-tight uppercase italic">
                    <?= htmlspecialchars($brandShort) ?>
                </span>
            </a>

            <div class="hidden lg:flex gap-10 text-[10px] font-bold uppercase tracking-widest">
                <a href="index.php" class="text-gray-400 hover:text-orange-500 transition">Beranda</a>
                <a href="produk.php" class="text-orange-500">Katalog Produk</a>
                <a href="layanan.php" class="text-gray-400 hover:text-orange-500 transition">Layanan</a>
                <a href="cara-pemesanan.php" class="text-gray-400 hover:text-orange-500 transition">Cara Pemesanan</a>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="keranjang.php"
               class="relative inline-flex items-center justify-center w-12 h-12 rounded-2xl border border-white/10 glass-inner hover:border-orange-500/40 transition group">
                <i class="fas fa-shopping-cart text-white/80 group-hover:text-orange-500 transition"></i>
                <?php if ($cartCount > 0): ?>
                    <span class="absolute -top-2 -right-2 bg-orange-600 text-[10px] font-black px-2 py-1 rounded-full shadow-lg border border-orange-500/30">
                        <?= $cartCount ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>

    </div>
</nav>


<section class="produk-wrapper pt-28">

    <div class="produk-header">
        <h1 class="judul">DAFTAR PRODUK</h1>

        <form method="GET" class="flex gap-4 items-center">
            <div class="relative">
                <i class="fas fa-search absolute left-4 top-3 text-gray-400 text-xs"></i>
                <input type="text"
                       name="q"
                       value="<?= htmlspecialchars($keyword) ?>"
                       placeholder="Cari kode atau nama material..."
                       class="bg-[#0F1115] border border-gray-700 rounded-full py-2 pl-10 pr-4 text-xs text-white outline-none">
            </div>

            <div class="sort-box">
                <i class="fas fa-sort"></i>
                <select name="sort" onchange="this.form.submit()">
                    <option value="terbaru" <?= $sort=='terbaru'?'selected':''; ?>>Terbaru</option>
                    <option value="nama_asc" <?= $sort=='nama_asc'?'selected':''; ?>>Nama A–Z</option>
                    <option value="nama_desc" <?= $sort=='nama_desc'?'selected':''; ?>>Nama Z–A</option>
                    <option value="stok_asc" <?= $sort=='stok_asc'?'selected':''; ?>>Stok Terkecil</option>
                    <option value="stok_desc" <?= $sort=='stok_desc'?'selected':''; ?>>Stok Terbesar</option>
                </select>
            </div>

            <button class="hidden">Cari</button>
        </form>
    </div>

    <?php if (mysqli_num_rows($query) > 0): ?>

        <!-- ✅ HEADER SEPERTI ADMIN -->
        <div class="produk-head-row">
            <div>Nama</div>
            <div>Kategori</div>
            <div>Harga</div>
            <div>Stok</div>
            <div class="text-right">Aksi</div>
        </div>

        <!-- ✅ LIST PRODUK MODEL TABEL -->
        <div>
            <?php while($p = mysqli_fetch_assoc($query)): ?>
                <?php
                $imgFile = (string)($p['gambar'] ?? '');
                $imgSrc  = findImageUrl($imgFile, $uploadDirCandidates, $uploadUrlCandidates);


                // stok + label
                $qty = (int)($p['stok'] ?? 0);
                $label = $qty <= 0 ? 'Habis' : ($qty < 10 ? 'Menipis' : 'Aman');
                $badgeClass = $qty <= 0 ? 'bg-red-500/10 text-red-500' :
                             ($qty < 10 ? 'bg-yellow-500/10 text-yellow-500' :
                             'bg-green-500/10 text-green-500');

                // harga
                $harga = (int)($p['harga'] ?? 0);
                $harga_rp = "Rp " . number_format($harga, 0, ',', '.');
                ?>

                <div class="produk-row">

                    <!-- NAMA + GAMBAR + KODE -->
                    <div class="col-img">
                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($p['nama_stock']) ?>">
                        <div class="min-w-0">
                            <div class="nama"><?= htmlspecialchars($p['nama_stock']) ?></div>
                            <span class="kode-badge"><?= htmlspecialchars($p['kode_stock'] ?? '-') ?></span>
                        </div>
                    </div>

                    <!-- KATEGORI -->
                    <div class="kategori">
                        <?= htmlspecialchars($p['milik'] ?? '-') ?>
                    </div>

                    <!-- HARGA -->
                    <div class="harga">
                        <?= htmlspecialchars($harga_rp) ?>
                    </div>

                    <!-- STOK -->
                    <div>
                        <span class="px-3 py-1 rounded-full text-[10px] font-black <?= $badgeClass ?>">
                            <?= $qty ?> (<?= $label ?>)
                        </span>
                    </div>

                    <!-- AKSI -->
                    <div class="aksi-wrap">
                        <a href="detail_produk.php?id=<?= (int)$p['produk_id'] ?>"
                           class="btn-wa text-center"
                           style="background:#1f2937; min-width:110px">
                            <i class="fas fa-info-circle"></i> Detail
                        </a>

                        <form method="POST">
                            <input type="hidden" name="produk_id" value="<?= (int)$p['produk_id'] ?>">
                            <button type="submit"
                                    name="add_to_cart"
                                    class="btn-wa"
                                    style="min-width:120px"
                                    <?= ($qty <= 0) ? 'disabled style="opacity:.5;cursor:not-allowed"' : '' ?>>
                                <i class="fas fa-cart-plus"></i>
                                <?= ($qty <= 0) ? 'Stok Habis' : 'Tambah' ?>
                            </button>
                        </form>
                    </div>

                </div>

            <?php endwhile; ?>
        </div>

    <?php else: ?>
        <div class="w-full min-h-[65vh] flex items-center justify-center">
            <div class="glass p-12 rounded-[3rem] border border-white/10 shadow-2xl text-center max-w-lg w-full">
                <div class="mx-auto w-16 h-16 rounded-2xl bg-orange-500/10 border border-orange-500/20 flex items-center justify-center mb-6">
                    <i class="fas fa-box-open text-orange-500 text-2xl"></i>
                </div>

                <h2 class="text-2xl font-black uppercase italic tracking-tight text-white">
                    Produk Tidak Ditemukan
                </h2>

                <p class="text-gray-400 text-xs uppercase italic tracking-widest mt-4 leading-relaxed">
                    Coba ubah kata kunci pencarian, atau reset filter untuk melihat semua produk.
                </p>

                <div class="mt-8 flex gap-4 justify-center">
                    <a href="produk.php"
                       class="bg-orange-600 px-6 py-4 rounded-2xl font-black uppercase text-xs tracking-widest orange-glow hover:scale-[1.02] transition inline-flex items-center gap-3">
                        <i class="fas fa-rotate-left"></i> Reset
                    </a>

                    <button onclick="location.reload()"
                            class="bg-[#1f2937] px-6 py-4 rounded-2xl font-black uppercase text-xs tracking-widest border border-white/10 hover:scale-[1.02] transition inline-flex items-center gap-3">
                        <i class="fas fa-arrows-rotate"></i> Reload
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

</section>

<!-- MODAL WARNING LOGIN -->
<div id="loginWarnModal" class="fixed inset-0 z-[999] hidden">
  <div id="loginWarnBackdrop" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

  <div class="relative mx-auto mt-24 w-[92%] max-w-md">
    <div class="rounded-[28px] border border-white/10 bg-[#0F1115]/95 shadow-2xl p-6">
      <div class="flex items-start justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-2xl bg-orange-500/15 border border-orange-500/20 flex items-center justify-center">
            <i class="fas fa-circle-exclamation text-orange-500 text-xl"></i>
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
          <i class="fas fa-xmark"></i>
        </button>
      </div>

      <div class="mt-6 flex gap-3">
        <a href="user/login.php"
          class="flex-1 bg-orange-600 px-5 py-3 rounded-2xl font-black uppercase text-[11px] tracking-widest orange-glow hover:scale-[1.01] transition text-center">
          Login
        </a>
        <a href="user/register.php"
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

  function openModal() {
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }
  function closeModal() {
    modal.classList.add('hidden');
    document.body.style.overflow = '';
  }

  // Jika user belum login: intercept submit tombol tambah
  document.querySelectorAll('form').forEach((form) => {
    const btn = form.querySelector('button[name="add_to_cart"]');
    if (!btn) return;

    form.addEventListener('submit', (e) => {
      if (!loggedIn) {
        e.preventDefault();
        openModal();
      }
    });
  });

  closeBtn?.addEventListener('click', closeModal);
  backdrop?.addEventListener('click', closeModal);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
  });

  // Kalau server redirect dengan ?need_login=1 -> buka modal otomatis
  const params = new URLSearchParams(window.location.search);
  if (params.get('need_login') === '1' && !loggedIn) openModal();
})();
</script>

</body>
</html>
