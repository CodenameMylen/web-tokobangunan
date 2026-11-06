<?php
session_start();
require_once 'koneksi/koneksi.php';

$brandShort = "Maju Usaha Bangunan";

/* init cart: [id_produk => qty] */
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/*
|--------------------------------------------------
| ACTIONS: update qty, remove, clear
|--------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update qty
    if (isset($_POST['update_qty']) && isset($_POST['qty']) && is_array($_POST['qty'])) {
        foreach ($_POST['qty'] as $pid => $qty) {
            $pid = (int)$pid;
            $qty = (int)$qty;
            if ($pid <= 0) continue;

            if ($qty <= 0) {
                unset($_SESSION['cart'][$pid]);
                continue;
            }

            // ✅ cek stok dari inventory.stok (bukan produk.qty)
            $stmt = mysqli_prepare($koneksi, "SELECT COALESCE(stok,0) AS stok FROM inventory WHERE produk_id = ? LIMIT 1");
            if ($stmt) {
                // ✅ FIX BUG: pakai $pid, bukan $id
                mysqli_stmt_bind_param($stmt, "i", $pid);                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
            } else {
                $row = null;
            }

            $stok = $row ? (int)$row['stok'] : 0;

            if ($stok <= 0) {
                unset($_SESSION['cart'][$pid]);
                continue;
            }

            if ($qty > $stok) $qty = $stok;

            $_SESSION['cart'][$pid] = $qty;
        }

        header("Location: keranjang.php");
        exit;
    }

    // Remove single item
    if (isset($_POST['remove_item'])) {
        $pid = (int)($_POST['produk_id'] ?? 0);
        if ($pid > 0) unset($_SESSION['cart'][$pid]);
        header("Location: keranjang.php");
        exit;
    }

    // Clear cart
    if (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
        header("Location: keranjang.php");
        exit;
    }
}

/*
|--------------------------------------------------
| LOAD CART ITEMS FROM DB (produk + inventory)
|--------------------------------------------------
*/
$cart = $_SESSION['cart'];
$items = [];
$grandTotal = 0;

$uploadDirCandidates = [
    __DIR__ . '/uploads/',
    __DIR__ . '/assets/uploads/',
    __DIR__ . '/assets/img/',
    __DIR__ . '/img/',
];

$uploadUrlCandidates = [
    'uploads/',
    'assets/uploads/',
    'assets/img/',
    'img/',
];

function findImageUrl(string $filename, array $dirs, array $urls): string {
    if ($filename === '') return 'assets/no-image.png';
    foreach ($dirs as $i => $dir) {
        $abs = $dir . $filename;
        if (file_exists($abs)) {
            return $urls[$i] . $filename;
        }
    }
    return 'assets/no-image.png';
}

if (!empty($cart)) {
    $ids = array_keys($cart);
    $ids = array_map('intval', $ids);

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    // ✅ FIX: stok ambil dari inventory.stok
    $sql = "
        SELECT 
            p.id_produk, p.kode_stock, p.nama_stock, p.merk, p.gambar, p.harga,
            COALESCE(i.stok, p.qty, 0) AS stok
        FROM produk p
        LEFT JOIN inventory i ON i.produk_id = p.id_produk
        WHERE p.id_produk IN ($placeholders)
    ";

    $stmt = mysqli_prepare($koneksi, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$ids);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);

        while ($row = mysqli_fetch_assoc($res)) {
            $pid  = (int)$row['id_produk'];
            $qty  = (int)($cart[$pid] ?? 0);
            $stok = (int)$row['stok'];

            // clamp qty to stok
            if ($stok <= 0) {
                $qty = 0;
            } elseif ($qty > $stok) {
                $qty = $stok;
                $_SESSION['cart'][$pid] = $qty;
            }

            if ($qty <= 0) continue;

            $harga = (int)$row['harga'];
            $row['qty_cart'] = $qty;
            $row['subtotal'] = $harga * $qty;
            $grandTotal += $row['subtotal'];

            // gambar
            $imgFile = (string)($row['gambar'] ?? '');
            $row['imgSrc'] = findImageUrl($imgFile, $uploadDirCandidates, $uploadUrlCandidates);

            $items[] = $row;
        }
    }

    // kalau semua item invalid, kosongkan cart
    if (empty($items)) $_SESSION['cart'] = [];
}

$cartCount = count($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Keranjang - <?= htmlspecialchars($brandShort) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .glass{
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }
        .glass-inner{
            background: rgba(15,17,21,.55);
            border: 1px solid rgba(255,255,255,.08);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }
        .orange-glow{
            box-shadow: 0 0 35px rgba(249,115,22,.28);
        }
    </style>
</head>

<body class="bg-[#0B0E13] text-white">

<!-- NAV -->
<nav class="fixed w-full z-50 glass py-5">
    <div class="max-w-7xl mx-auto px-8 flex items-center justify-between">

        <a href="produk.php" class="flex items-center gap-3">
            <div class="bg-orange-600 p-2 rounded-xl">
                <i class="fas fa-hammer text-white"></i>
            </div>
            <span class="font-extrabold text-sm tracking-tight uppercase italic">
                <?= htmlspecialchars($brandShort) ?>
            </span>
        </a>

        <a href="keranjang.php"
           class="relative inline-flex items-center justify-center w-12 h-12 rounded-2xl border border-white/10 glass-inner hover:border-orange-500/40 transition group">
            <i class="fas fa-shopping-cart text-white/80 group-hover:text-orange-500 transition"></i>
            <?php if ($cartCount > 0): ?>
                <span id="badgeCart"
                      class="absolute -top-2 -right-2 bg-orange-600 text-[10px] font-black px-2 py-1 rounded-full shadow-lg border border-orange-500/30">
                    <?= $cartCount ?>
                </span>
            <?php endif; ?>
        </a>

    </div>
</nav>

<!-- CONTENT -->
<section class="pt-28 pb-20 px-8 max-w-7xl mx-auto min-h-screen">

    <div class="flex items-end justify-between flex-wrap gap-6 mb-10">
        <div class="space-y-2">
            <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-500 text-[10px] font-bold uppercase tracking-widest">
                <span class="flex h-2 w-2 rounded-full bg-orange-500 animate-pulse"></span>
                Keranjang Belanja
            </div>
            <h1 class="text-4xl md:text-5xl font-black leading-none tracking-tighter uppercase italic">
                KERANJANG <span class="text-orange-500">ANDA</span>
            </h1>
            <p class="text-gray-400 text-xs italic tracking-widest uppercase">
                Kelola jumlah item, hapus barang, lalu lanjut checkout.
            </p>
        </div>

        <div class="flex gap-3">
            <a href="produk.php"
               class="glass-inner px-6 py-4 rounded-2xl border border-white/10 text-xs font-black uppercase tracking-widest hover:border-orange-500/40 transition inline-flex items-center gap-3">
                <i class="fas fa-arrow-left"></i> Kembali Belanja
            </a>
        </div>
    </div>

    <?php if (empty($items)): ?>
        <!-- EMPTY CART -->
        <div class="w-full min-h-[60vh] flex items-center justify-center">
            <div class="glass p-12 rounded-[3rem] border border-white/10 shadow-2xl text-center max-w-lg w-full">
                <div class="mx-auto w-16 h-16 rounded-2xl bg-orange-500/10 border border-orange-500/20 flex items-center justify-center mb-6">
                    <i class="fas fa-cart-shopping text-orange-500 text-2xl"></i>
                </div>

                <h2 class="text-2xl font-black uppercase italic tracking-tight text-white">
                    Keranjang masih kosong
                </h2>

                <p class="text-gray-400 text-xs uppercase italic tracking-widest mt-4 leading-relaxed">
                    Tambahkan produk dari katalog untuk mulai belanja.
                </p>

                <div class="mt-8 flex gap-4 justify-center">
                    <a href="produk.php"
                       class="bg-orange-600 px-6 py-4 rounded-2xl font-black uppercase text-xs tracking-widest orange-glow hover:scale-[1.02] transition inline-flex items-center gap-3">
                        <i class="fas fa-bag-shopping"></i> Lihat Produk
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>

        <!-- SATU FORM SAJA -->
        <form method="POST" class="grid lg:grid-cols-12 gap-10 items-start">

            <!-- LIST ITEMS -->
            <div class="lg:col-span-8 space-y-4">

                <?php foreach ($items as $it): ?>
                    <?php
                    $pid   = (int)$it['id_produk'];
                    $stok  = (int)$it['stok'];
                    $qty   = (int)$it['qty_cart'];
                    $harga = (int)$it['harga'];

                    $tersedia = $stok > 0;
                    ?>
                    <div class="glass p-6 rounded-[2rem] border border-white/10 shadow-xl flex flex-col sm:flex-row gap-6">
                        <div class="w-full sm:w-40 h-32 rounded-2xl overflow-hidden border border-white/10 glass-inner">
                            <img src="<?= htmlspecialchars($it['imgSrc']) ?>"
                                 alt="<?= htmlspecialchars($it['nama_stock']) ?>"
                                 class="w-full h-full object-cover">
                        </div>

                        <div class="flex-1">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-[10px] uppercase tracking-widest text-gray-400 font-bold italic">
                                        <?= htmlspecialchars($it['kode_stock'] ?? '') ?> • <?= htmlspecialchars($it['merk'] ?? '-') ?>
                                    </div>
                                    <h3 class="text-lg font-black uppercase italic tracking-tight mt-1">
                                        <?= htmlspecialchars($it['nama_stock']) ?>
                                    </h3>

                                    <div class="mt-2 text-[10px] uppercase tracking-widest font-black italic">
                                        <?php if ($tersedia): ?>
                                            <span class="text-green-400">Tersedia</span>
                                        <?php else: ?>
                                            <span class="text-red-400">Tidak Tersedia</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- tombol remove -->
                                <button type="submit"
                                        name="remove_item"
                                        value="1"
                                        onclick="document.getElementById('remove_pid_<?= $pid ?>').value='<?= $pid ?>';"
                                        class="w-10 h-10 rounded-2xl border border-white/10 glass-inner hover:border-red-500/40 transition inline-flex items-center justify-center text-white/80 hover:text-red-400">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <input type="hidden" id="remove_pid_<?= $pid ?>" name="produk_id" value="">
                            </div>

                            <div class="mt-5 flex flex-wrap items-center justify-between gap-4">

                                <div class="inline-flex items-center gap-3 px-4 py-2 rounded-2xl border border-white/10 glass-inner text-xs font-black uppercase tracking-widest">
                                    <span class="text-gray-400">Stok</span>
                                    <span class="text-white"><?= $stok ?></span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400 italic">
                                        Qty
                                    </label>

                                    <input type="number"
                                           name="qty[<?= $pid ?>]"
                                           min="1"
                                           max="<?= max(1, $stok) ?>"
                                           value="<?= $qty ?>"
                                           data-pid="<?= $pid ?>"
                                           data-price="<?= $harga ?>"
                                           data-max="<?= max(1, $stok) ?>"
                                           class="qty-input w-24 glass-inner px-4 py-3 rounded-2xl border border-white/10 focus:border-orange-500/60 outline-none text-sm text-white">
                                </div>

                                <div class="text-right">
                                    <div class="text-[10px] uppercase tracking-widest text-gray-400 font-bold italic">Subtotal</div>

                                    <div class="text-xl font-black tracking-tight text-orange-500">
                                        Rp <span class="item-subtotal" data-pid="<?= $pid ?>"><?= number_format((int)$it['subtotal'], 0, ',', '.') ?></span>
                                    </div>

                                    <div class="text-[10px] uppercase tracking-widest text-gray-500 italic">
                                        (Rp <span class="item-price" data-pid="<?= $pid ?>"><?= number_format($harga, 0, ',', '.') ?></span>
                                        × <span class="item-qty" data-pid="<?= $pid ?>"><?= $qty ?></span>)
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" name="update_qty"
                            class="bg-orange-600 px-6 py-4 rounded-2xl font-black uppercase text-xs tracking-widest orange-glow hover:scale-[1.02] transition inline-flex items-center gap-3">
                        <i class="fas fa-rotate"></i> Update Qty
                    </button>

                    <button type="submit" name="clear_cart"
                            class="glass-inner px-6 py-4 rounded-2xl border border-white/10 text-xs font-black uppercase tracking-widest hover:border-red-500/40 transition inline-flex items-center gap-3 text-white/80 hover:text-red-400">
                        <i class="fas fa-trash"></i> Kosongkan
                    </button>
                </div>
            </div>

            <!-- SUMMARY -->
            <div class="lg:col-span-4">
                <div class="glass p-8 rounded-[2.5rem] border border-white/10 shadow-2xl sticky top-28">
                    <h3 class="text-2xl font-black uppercase italic tracking-tight">
                        Ringkasan
                    </h3>

                    <div class="mt-6 space-y-4">
                        <div class="flex items-center justify-between text-xs font-bold uppercase tracking-widest text-gray-400 italic">
                            <span>Total Item</span>
                            <span class="text-white" id="cartCount"><?= $cartCount ?></span>
                        </div>

                        <div class="flex items-center justify-between text-xs font-bold uppercase tracking-widest text-gray-400 italic">
                            <span>Total</span>
                            <span class="text-orange-500 text-lg font-black">
                                Rp <span id="grandTotal"><?= number_format((int)$grandTotal, 0, ',', '.') ?></span>
                            </span>
                        </div>

                        <a href="checkout.php"
                           class="mt-4 block text-center bg-orange-600 px-6 py-5 rounded-2xl font-black uppercase text-xs tracking-widest orange-glow hover:scale-[1.02] transition">
                            <i class="fas fa-credit-card mr-2"></i> Checkout
                        </a>

                        <a href="produk.php"
                           class="block text-center glass-inner px-6 py-5 rounded-2xl border border-white/10 text-xs font-black uppercase tracking-widest hover:border-orange-500/40 transition">
                            Lanjut Belanja
                        </a>
                    </div>
                </div>
            </div>

        </form>

    <?php endif; ?>

</section>

<!-- REALTIME CALC -->
<script>
(function () {
  const fmtIDR = (n) => new Intl.NumberFormat('id-ID').format(n || 0);

  const qtyInputs = document.querySelectorAll('.qty-input');
  const grandTotalEl = document.getElementById('grandTotal');
  const cartCountEl = document.getElementById('cartCount');
  const badgeCartEl = document.getElementById('badgeCart');

  function clamp(val, min, max) {
    val = Number.isFinite(val) ? val : min;
    if (val < min) val = min;
    if (val > max) val = max;
    return val;
  }

  function syncHiddenQty(pid, q) {
    // Cari input hidden yang akan dikirim ke server: name="qty[PID]"
    const hidden = document.querySelector(`input[name="qty[${pid}]"]`);
    if (hidden) hidden.value = String(q);
  }

  function recalc() {
    let grand = 0;
    let count = 0;

    qtyInputs.forEach((inp) => {
      const pid = inp.dataset.pid;
      const price = parseInt(inp.dataset.price || '0', 10);
      const max = parseInt(inp.dataset.max || '999999', 10);

      let q = parseInt(inp.value || '1', 10);
      q = clamp(q, 1, max);

      // rapikan value di input
      if (String(q) !== String(inp.value)) inp.value = q;

      const sub = price * q;

      // update subtotal per item
      const subEl = document.querySelector(`.item-subtotal[data-pid="${pid}"]`);
      if (subEl) subEl.textContent = fmtIDR(sub);

      // update qty display (kalau ada)
      const qEl = document.querySelector(`.item-qty[data-pid="${pid}"]`);
      if (qEl) qEl.textContent = String(q);

      // sync hidden input buat submit (kalau ada)
      syncHiddenQty(pid, q);

      grand += sub;
      if (q > 0) count += 1;
    });

    // update total & badge
    if (grandTotalEl) grandTotalEl.textContent = fmtIDR(grand);
    if (cartCountEl) cartCountEl.textContent = String(count);
    if (badgeCartEl) badgeCartEl.textContent = String(count);
  }

  // Optional: support tombol plus/minus (kalau kamu punya)
  // tombol plus:  <button class="qty-plus" data-pid="ID">+</button>
  // tombol minus: <button class="qty-minus" data-pid="ID">-</button>
  document.addEventListener('click', (e) => {
    const plus = e.target.closest('.qty-plus');
    const minus = e.target.closest('.qty-minus');

    if (!plus && !minus) return;

    const btn = plus || minus;
    const pid = btn.dataset.pid;
    if (!pid) return;

    const inp = document.querySelector(`.qty-input[data-pid="${pid}"]`);
    if (!inp) return;

    const max = parseInt(inp.dataset.max || '999999', 10);
    let q = parseInt(inp.value || '1', 10);
    q = clamp(q, 1, max);

    if (plus) q = clamp(q + 1, 1, max);
    if (minus) q = clamp(q - 1, 1, max);

    inp.value = String(q);
    recalc();
  });

  // listener input qty manual
  qtyInputs.forEach((inp) => {
    inp.addEventListener('input', recalc);
    inp.addEventListener('change', recalc);
    inp.addEventListener('blur', recalc);
  });

  recalc();
})();
</script>


</body>
</html>
