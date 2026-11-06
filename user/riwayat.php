<?php
session_start();
include '../koneksi/koneksi.php';
require_once __DIR__ . '/auth.php';

if (!is_logged_in()) {
  header("Location: login.php");
  exit;
}

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/**
 * Root project dari URL path:
 * contoh kalau script di /web_tokobangunan/user/riwayat.php
 * maka hasil: /web_tokobangunan
 */
function project_root_web(): string {
  return rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
}

/**
 * Build URL gambar uploads + cek file fisik ada
 */
function uploads_image_src(string $filename): string {
  $filename = trim($filename);
  if ($filename === '') return '';

  $rootWeb = project_root_web(); // /web_tokobangunan
  $webPath = $rootWeb . '/assets/uploads/' . $filename;

  // disk path: DOCUMENT_ROOT + webPath
  $diskPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $webPath;

  return file_exists($diskPath) ? $webPath : '';
}

/* ambil user id aman */
function current_user_id_safe($koneksi){
  foreach (['user_id','id','id_user','uid'] as $k) {
    if (isset($_SESSION[$k]) && is_numeric($_SESSION[$k])) return (int)$_SESSION[$k];
  }
  $uname = current_username();
  if (!$uname) return 0;

  $stmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE username=? LIMIT 1");
  if (!$stmt) return 0;

  mysqli_stmt_bind_param($stmt, "s", $uname);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $row = $res ? mysqli_fetch_assoc($res) : null;
  mysqli_stmt_close($stmt);
  return (int)($row['id'] ?? 0);
}

$userId = current_user_id_safe($koneksi);
if ($userId <= 0) {
  header("Location: login.php");
  exit;
}

/* orders */
$stmt = mysqli_prepare($koneksi, "SELECT id, total_harga, alamat, metode_pembayaran, status, created_at, no_resi
                                 FROM orders WHERE user_id=? ORDER BY created_at DESC");
if (!$stmt) {
  die("Prepare orders gagal: " . mysqli_error($koneksi));
}
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$orders = [];
while($r = mysqli_fetch_assoc($res)) $orders[] = $r;
mysqli_stmt_close($stmt);

/* items */
function get_order_items_user($koneksi, $orderId){
  $items = [];
  $sql = "SELECT
            oi.produk_id, oi.harga, oi.qty,
            p.nama_stock AS produk_nama,
            p.kode_stock AS produk_kode,
            p.gambar,
            p.merk
          FROM order_items oi
          LEFT JOIN produk p ON p.id_produk = oi.produk_id
          WHERE oi.order_id = ?";

  $stmt = mysqli_prepare($koneksi, $sql);
  if (!$stmt) return $items;

  mysqli_stmt_bind_param($stmt, "i", $orderId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  while($r = mysqli_fetch_assoc($res)) $items[] = $r;
  mysqli_stmt_close($stmt);
  return $items;
}

/* chip status */
function status_chip_class($s){
  $s = strtolower(trim((string)$s));
  return match($s){
    'pending'  => 'bg-yellow-500/15 text-yellow-300 ring-1 ring-yellow-500/30',
    'diproses' => 'bg-sky-500/15 text-sky-300 ring-1 ring-sky-500/30',
    'dikirim'  => 'bg-indigo-500/15 text-indigo-300 ring-1 ring-indigo-500/30',
    'selesai'  => 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-500/30',
    'batal'    => 'bg-rose-500/15 text-rose-300 ring-1 ring-rose-500/30',
    default    => 'bg-zinc-500/15 text-zinc-200 ring-1 ring-zinc-500/30',
  };
}

$namaToko   = "Toko Bangunan Maju Usaha Bangunan";
$brandShort = "Maju Usaha Bangunan";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Riwayat Pesanan - <?= esc($namaToko) ?></title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
</head>

<body>

  <!-- NAVBAR -->
  <nav class="fixed w-full z-50 glass py-5">
    <div class="max-w-7xl mx-auto px-8 flex items-center justify-between">
      <div class="flex items-center gap-12">
        <div class="flex items-center gap-3">
          <div class="bg-orange-600 p-2 rounded-xl"><i class="fas fa-hammer text-white"></i></div>
          <a href="../index.php" class="font-extrabold text-sm tracking-tight uppercase italic">
            <?= esc($brandShort) ?>
          </a>
        </div>
        <div class="hidden lg:flex gap-10 text-[10px] font-bold uppercase tracking-widest">
          <a href="../index.php" class="text-gray-400 hover:text-orange-500 transition">Beranda</a>
          <a href="../produk.php" class="text-gray-400 hover:text-orange-500 transition">Katalog Produk</a>
          <a href="../layanan.php" class="text-gray-400 hover:text-orange-500 transition">Layanan</a>
          <a href="../cara-pemesanan.php" class="text-gray-400 hover:text-orange-500 transition">Cara Pemesanan</a>
        </div>
      </div>

      <div class="hidden sm:flex items-center gap-4 text-[10px] font-bold uppercase tracking-widest italic">
        <span class="text-gray-500">Kabupaten Kampar, Riau</span>
        <span class="text-white/10">|</span>

        <span class="text-orange-500">
          Halo, <?= esc(current_username()); ?>
        </span>

        <span class="text-white/10">|</span>

        <a href="riwayat.php" class="text-gray-400 hover:text-orange-500 transition">
          Riwayat
        </a>

        <span class="text-white/10">|</span>

        <a href="logout.php" class="text-gray-400 hover:text-orange-500 transition">
          Logout
        </a>
      </div>
    </div>
  </nav>

  <!-- CONTENT -->
  <section class="pt-32 pb-20 px-8 max-w-7xl mx-auto min-h-screen">
    <div class="flex items-end justify-between gap-6 mb-10">
      <div>
        <h1 class="text-5xl md:text-6xl font-black leading-none tracking-tighter uppercase italic">
          Riwayat <span class="orange-accent">Pesanan</span>
        </h1>
        <p class="text-gray-400 text-sm leading-loose mt-3">
          Semua transaksi kamu tersimpan di sini. Klik order untuk melihat detail item.
        </p>
      </div>

      <a href="../produk.php"
        class="bg-orange-600 px-8 py-4 rounded-2xl font-black inline-flex items-center gap-3 orange-glow hover:scale-105 transition uppercase text-[10px] tracking-widest">
        Belanja Lagi
        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.5 7h13M7 13L5.4 5M9 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" />
        </svg>
      </a>
    </div>

    <?php if (count($orders) === 0): ?>
      <div class="glass rounded-[2.5rem] p-10 border border-white/10 text-center">
        <div class="text-orange-500 text-5xl mb-4">🧾</div>
        <div class="text-xl font-extrabold italic uppercase tracking-tight">Belum ada pesanan</div>
        <p class="text-gray-400 text-sm mt-2">Mulai belanja produk, nanti riwayat kamu muncul di sini.</p>
        <div class="mt-6">
          <a href="../produk.php"
             class="bg-orange-600 px-8 py-4 rounded-2xl font-black inline-flex items-center gap-3 orange-glow hover:scale-105 transition uppercase text-[10px] tracking-widest">
            Ke Katalog →
          </a>
        </div>
      </div>
    <?php else: ?>

      <div class="space-y-5">
        <?php foreach ($orders as $o): ?>
          <?php
            $oid = (int)$o['id'];
            $status = strtolower($o['status'] ?? 'pending');
            $chip = status_chip_class($status);
            $tgl = $o['created_at'] ? date('d M Y, H:i', strtotime($o['created_at'])) : '-';
            $total = (float)($o['total_harga'] ?? 0);
            $metode = $o['metode_pembayaran'] ?? '-';
            $alamat = $o['alamat'] ?? '-';

            $items = get_order_items_user($koneksi, $oid);

            // ambil gambar produk pertama untuk header
            $firstImage = '';
            if (!empty($items)) {
              $firstFilename = $items[0]['gambar'] ?? '';
              $firstImage = uploads_image_src($firstFilename); // sudah cek file_exists
            }
          ?>

          <details class="glass rounded-[2.5rem] overflow-hidden border border-white/10">
            <summary class="cursor-pointer px-7 py-6 flex items-center justify-between gap-6">
              <div class="flex items-center gap-5 min-w-0">
                <div class="w-12 h-12 rounded-2xl overflow-hidden border border-white/10 bg-white/5 shrink-0">
                  <?php if ($firstImage): ?>
                    <img src="<?= esc($firstImage) ?>" class="w-full h-full object-cover" alt="">
                  <?php else: ?>
                    <div class="w-full h-full grid place-items-center text-zinc-500">📦</div>
                  <?php endif; ?>
                </div>

                <div class="min-w-0">
                  <div class="text-lg font-extrabold italic uppercase tracking-tight">
                    ORDER #<?= $oid ?>
                  </div>
                  <div class="text-xs text-zinc-500 uppercase tracking-widest italic mt-1">
                    <?= esc($tgl) ?> • METODE: <?= esc($metode) ?>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-4 shrink-0">
                <span class="inline-flex items-center rounded-full px-4 py-2 text-[10px] font-black uppercase tracking-widest <?= esc($chip) ?>">
                  <?= esc($status) ?>
                </span>

                <div class="text-right">
                  <div class="text-[10px] text-zinc-500 uppercase tracking-widest">TOTAL</div>
                  <div class="text-orange-400 font-black text-lg">
                    Rp <?= number_format($total,0,',','.') ?>
                  </div>
                </div>

                <div class="text-zinc-400">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  </svg>
                </div>
              </div>
            </summary>

            <div class="px-7 pb-7">
              <div class="glass-inner rounded-[2rem] p-6">
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                  <div>
                    <div class="text-[10px] text-zinc-500 uppercase tracking-widest font-black">Alamat</div>
                    <div class="text-sm text-zinc-200 mt-2 leading-relaxed"><?= esc($alamat) ?></div>
                  </div>
                  <div>
                    <div class="text-[10px] text-zinc-500 uppercase tracking-widest font-black">Ringkasan</div>
                    <div class="text-sm text-zinc-200 mt-2">
                      Item: <span class="font-black"><?= count($items) ?></span><br>
                      Total: <span class="text-orange-400 font-black">Rp <?= number_format($total,0,',','.') ?></span>

                      <?php if (!empty($o['no_resi'])): ?>
                        <br>
                        Resi: <span class="text-indigo-400 font-black"><?= esc($o['no_resi']) ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>

                <div class="border-t border-white/5 pt-5">
                  <div class="text-[10px] text-zinc-500 uppercase tracking-widest font-black mb-4">Detail Item</div>

                  <?php if (count($items) === 0): ?>
                    <div class="text-zinc-400 text-sm">Item tidak ditemukan.</div>
                  <?php else: ?>
                    <div class="space-y-4">
                      <?php foreach ($items as $it): ?>
                        <?php
                          $pid = (int)($it['produk_id'] ?? 0);
                          $nama = $it['produk_nama'] ?? ("Produk ID: ".$pid);
                          $kode = $it['produk_kode'] ?? ("ID ".$pid);
                          $merk = $it['merk'] ?? '';
                          $qty  = (int)($it['qty'] ?? 0);
                          $harga = (float)($it['harga'] ?? 0);
                          $sub = $qty * $harga;

                          $imgFile = $it['gambar'] ?? '';
                          $imgSrc  = uploads_image_src($imgFile);
                        ?>

                        <div class="flex items-center justify-between gap-4 p-4 rounded-2xl bg-white/[0.03] border border-white/5">
                          <div class="flex items-center gap-4 min-w-0">
                            <?php if ($imgSrc): ?>
                              <img src="<?= esc($imgSrc) ?>" class="w-14 h-14 rounded-xl object-cover border border-white/10" alt="">
                            <?php else: ?>
                              <div class="w-14 h-14 rounded-xl bg-white/5 border border-white/10 grid place-items-center text-zinc-500">📦</div>
                            <?php endif; ?>

                            <div class="min-w-0">
                              <div class="font-extrabold italic uppercase tracking-tight truncate"><?= esc($nama) ?></div>
                              <div class="text-xs text-zinc-500 uppercase tracking-widest italic">
                                <?= esc($kode) ?><?= $merk ? " • ".esc($merk) : "" ?> • Qty <?= $qty ?>
                              </div>
                            </div>
                          </div>

                          <div class="text-right">
                            <div class="text-xs text-zinc-400">Rp <?= number_format($harga,0,',','.') ?></div>
                            <div class="text-orange-400 font-black">Rp <?= number_format($sub,0,',','.') ?></div>
                          </div>
                        </div>

                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>

              </div>
            </div>
          </details>

        <?php endforeach; ?>
      </div>

    <?php endif; ?>
  </section>

</body>
</html>
