<?php
session_start();
include __DIR__ . '/koneksi/koneksi.php';
require_once __DIR__ . '/user/auth.php';

$namaToko = "Toko Bangunan Maju Usaha Bangunan";
$brandShort = "Maju Usaha Bangunan";

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    die("Keranjang kosong.");
}

$productIds = array_map('intval', array_keys($cart));
$placeholders = implode(',', array_fill(0, count($productIds), '?'));

$sql = "SELECT p.id_produk, p.nama_stock, p.harga
               COALESCE(i.stok, p.qty) AS stok
        FROM produk p
        LEFT JOIN inventory i ON i.produk_id = p.id_produk
        WHERE p.id_produk IN ($placeholders)";

$stmt = mysqli_prepare($koneksi, $sql);
if (!$stmt) die("Prepare gagal: " . mysqli_error($koneksi));

$types = str_repeat('i', count($productIds));
$params = [];
$params[] = $types;
foreach ($productIds as $k => $v) $params[] = &$productIds[$k];
call_user_func_array([$stmt, 'bind_param'], $params);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$produkMap = [];
while ($row = mysqli_fetch_assoc($result)) {
    $produkMap[(int)$row['id_produk']] = $row;
}

$total = 0;
$validItems = 0; // FIX: hitung item valid yg benar-benar tampil
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo $namaToko; ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-[#0b0f1a] text-white">

<!-- NAV (nyambung desain kamu) -->
<nav class="fixed w-full z-50 glass py-5">
    <div class="max-w-7xl mx-auto px-8 flex items-center justify-between">
        <div class="flex items-center gap-12">
            <div class="flex items-center gap-3">
                <div class="bg-orange-600 p-2 rounded-xl"><i class="fas fa-hammer text-white"></i></div>
                <span class="font-extrabold text-sm tracking-tight uppercase italic"><?php echo $brandShort; ?></span>
            </div>
            <div class="hidden lg:flex gap-10 text-[10px] font-bold uppercase tracking-widest">
                <a href="index.php" class="text-gray-400 hover:text-orange-500 transition">Beranda</a>
                <a href="produk.php" class="text-gray-400 hover:text-orange-500 transition">Katalog Produk</a>
            </div>
        </div>

        <div class="hidden sm:flex items-center gap-4 text-[10px] font-bold uppercase tracking-widest italic">
            <?php if (is_logged_in()): ?>
                <span class="text-orange-500">Halo, <?php echo htmlspecialchars(current_username()); ?></span>
                <span class="text-white/10">|</span>
                <a href="user/logout.php" class="text-gray-400 hover:text-orange-500 transition">Logout</a>
            <?php else: ?>
                <a href="user/login.php" class="text-gray-400 hover:text-orange-500 transition">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- CONTENT -->
<section class="pt-32 pb-20 px-8 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-10">
        <div>
            <h1 class="text-4xl md:text-5xl font-black uppercase italic tracking-tighter">
                Checkout
            </h1>
            <p class="text-gray-400 text-xs uppercase tracking-widest italic mt-2">
                Ringkasan belanja & konfirmasi pesanan
            </p>
        </div>

        <a href="keranjang.php"
        class="text-[10px] uppercase tracking-widest font-bold text-gray-400 hover:text-orange-500 transition">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Keranjang
        </a>
    </div>

    <div class="grid lg:grid-cols-12 gap-10 items-start">
        <!-- LEFT: RINGKASAN -->
        <div class="lg:col-span-7 glass p-8 rounded-[3rem] border border-white/10">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-black uppercase italic tracking-widest text-orange-500">
                    Ringkasan Belanja
                </h2>

                <!-- FIX: tampilkan item valid, bukan count($cart) -->
                <span class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic">
                    <span id="item_count_server"><?php echo $validItems; ?></span> Item
                </span>
            </div>

            <div class="space-y-4">
                <?php foreach ($cart as $id_produk => $qty_beli): ?>
                    <?php
                        $id_produk = (int)$id_produk;
                        $qty_beli  = (int)$qty_beli;

                        if (!isset($produkMap[$id_produk])) continue;

                        $nama  = $produkMap[$id_produk]['nama_stock'];
                        $harga = (int)$produkMap[$id_produk]['harga'];
                        $stok  = (int)($produkMap[$id_produk]['stok'] ?? 0);

                        // kalau stok 0, item tidak tampil
                        if ($stok < 1) continue;

                        if ($qty_beli > $stok) $qty_beli = $stok;
                        if ($qty_beli < 1) continue;

                        // FIX: item valid bertambah
                        $validItems++;

                        $subtotal = $harga * $qty_beli;
                        $total += $subtotal;
                    ?>

                    <div class="glass-inner p-5 rounded-2xl border border-white/5 flex items-center justify-between gap-6">
                        <div class="min-w-0">
                            <p class="font-black uppercase italic tracking-widest text-sm truncate">
                                <?php echo htmlspecialchars($nama); ?>
                            </p>
                            <p class="text-gray-400 text-[10px] uppercase tracking-widest mt-2 font-bold italic">
                                Harga: Rp <?php echo number_format($harga, 0, ',', '.'); ?>
                                <span class="text-white/10 mx-2">|</span>
                                Stok: <?php echo $stok; ?>
                            </p>
                        </div>

                        <div class="text-right shrink-0">
                            <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic">
                                Qty
                            </p>
                            <p class="text-lg font-black text-white">
                                <?php echo $qty_beli; ?>
                            </p>
                        </div>

                        <div class="text-right shrink-0">
                            <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic">
                                Subtotal
                            </p>
                            <p class="text-lg font-black text-orange-500">
                                Rp <?php echo number_format($subtotal, 0, ',', '.'); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($validItems === 0): ?>
                <div class="mt-6 glass-inner p-5 rounded-2xl border border-white/5">
                    <p class="text-red-400 text-xs uppercase tracking-widest font-bold italic">
                        Tidak ada item valid (stok mungkin habis / produk tidak ditemukan).
                    </p>
                </div>
            <?php endif; ?>

            <div class="mt-8 pt-8 border-t border-white/5 space-y-4">
                <!-- Subtotal -->
                <div class="flex items-center justify-between">
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic">
                        Subtotal Belanja
                    </p>
                    <p id="subtotal_display" class="text-sm font-black text-white uppercase italic tracking-widest">
                        Rp <?php echo number_format($total, 0, ',', '.'); ?>
                    </p>
                </div>

                <!-- Ongkir -->
                <div class="flex items-center justify-between">
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic">
                        Ongkos Kirim
                    </p>
                    <p id="ongkir_left_display" class="text-sm font-black text-orange-500 uppercase italic tracking-widest">
                        Rp 0
                    </p>
                </div>

                <!-- Divider halus -->
                <div class="h-px bg-white/10"></div>

                <!-- Grand Total -->
                <div class="flex items-end justify-between">
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic">
                        Total Pembayaran
                    </p>

                    <p id="grand_total_display" class="text-2xl md:text-3xl font-black text-orange-500 uppercase italic tracking-tighter">
                        Rp <?php echo number_format($total, 0, ',', '.'); ?>
                    </p>
                </div>

                <!-- Hidden subtotal untuk JS -->
                <input type="hidden" id="subtotal_value" value="<?php echo $total; ?>">

                <!-- FIX: update angka item (server sudah hitung) -->
                <script>
                  document.addEventListener('DOMContentLoaded', () => {
                    const el = document.getElementById('item_count_server');
                    if (el) el.textContent = "<?php echo $validItems; ?>";
                  });
                </script>
            </div>

        </div>

        <!-- RIGHT: FORM -->
        <div class="lg:col-span-5 
            bg-gradient-to-br from-[#111827]/90 to-[#0b0f1a]/90 
            backdrop-blur-xl 
            border border-white/10 
            rounded-[2.5rem] 
            p-10 
            shadow-2xl 
            relative 
            overflow-hidden">

        <!-- Glow background effect -->
        <div class="absolute -top-20 -right-20 w-60 h-60 bg-orange-600/10 rounded-full blur-3xl"></div>

        <h2 class="text-2xl font-black uppercase italic tracking-widest text-orange-500 mb-8 relative z-10">
            Data Pengiriman
        </h2>

            <form method="POST" action="proses/proses_checkout.php" class="space-y-8 relative z-10">

            <!-- ALAMAT -->
            <div>
            <label class="block text-xs uppercase tracking-[0.3em] font-bold italic text-gray-400 mb-3">
                Masukkan Detail Alamat Pengiriman
            </label>

            <textarea name="alamat" id="alamat" required
                class="w-full rounded-2xl p-5 bg-black/40 border border-white/10 text-white text-sm
                    focus:border-orange-500 focus:ring-2 focus:ring-orange-500/30 transition duration-300 resize-none"
                rows="4"
                placeholder="Tulis lengkap detail alamat pengiriman"></textarea>

            <!-- AMBIL LOKASI (ONGKIR OTOMATIS BERDASARKAN JARAK) -->
            <div class="flex items-center gap-3 mt-4">
                <button type="button" id="btnLokasi"
                class="px-5 py-3 rounded-2xl bg-black/40 border border-white/10
                        hover:border-orange-500/60 transition text-xs uppercase tracking-widest font-bold italic">
                <i class="fa-solid fa-location-crosshairs mr-2 text-orange-500"></i>
                Gunakan Lokasi Saya
                </button>

                <span id="lokasiStatus" class="text-[10px] uppercase tracking-widest italic text-gray-500">
                Lokasi belum diambil
                </span>
            </div>

            <!-- hidden: dikirim ke proses_checkout.php -->
            <input type="hidden" name="lat" id="lat">
            <input type="hidden" name="lng" id="lng">
            <input type="hidden" name="jarak_km" id="jarak_km" value="0">
            <input type="hidden" name="ongkir" id="ongkir_value" value="0">
            </div>


            <!-- METODE -->
            <div>
                <label class="block text-xs uppercase tracking-[0.3em] font-bold italic text-gray-400 mb-3">
                    Metode Pembayaran
                </label>

                <input type="hidden" name="metode" id="metode_value" required>

                <div class="relative z-[60]" id="metode_dropdown">
                    <button type="button"
                    id="metode_btn"
                    class="w-full rounded-2xl p-5 bg-black/40 border border-orange-500/40
                            text-left text-white text-sm
                            focus:outline-none focus:ring-2 focus:ring-orange-500/30
                            transition flex items-center justify-between">
                    <span id="metode_label" class="text-white/70">-- Pilih metode pembayaran --</span>
                    <i id="metode_icon" class="fa-solid fa-chevron-down text-orange-500 text-xs transition"></i>
                    </button>

                    <div id="metode_menu"
                    class="hidden absolute left-0 mt-3 w-full rounded-2xl overflow-hidden
                            bg-[#0b0f1a]/95 backdrop-blur-xl border border-white/10 shadow-2xl z-[9999]">

                    <button type="button" data-value="cod"
                        class="w-full px-5 py-4 flex items-center justify-between hover:bg-white/5 transition text-left">
                        <div>
                        <p class="font-black uppercase italic tracking-widest text-sm text-white">COD</p>
                        <p class="text-[10px] uppercase tracking-widest italic text-gray-400 mt-1">
                            Bayar di tempat saat barang sampai
                        </p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-[10px] uppercase tracking-widest font-bold italic
                                    bg-orange-500/10 border border-orange-500/20 text-orange-400">
                        Populer
                        </span>
                    </button>

                    <div class="h-px bg-white/10"></div>

                    <button type="button" data-value="pickup"
                        class="w-full px-5 py-4 flex items-center justify-between hover:bg-white/5 transition text-left">
                        <div>
                            <p class="font-black uppercase italic tracking-widest text-sm text-white">
                                Ambil di Tempat
                            </p>
                            <p class="text-[10px] uppercase tracking-widest italic text-gray-400 mt-1">
                                Ambil langsung di toko
                            </p>
                        </div>
                        <i class="fa-solid fa-store text-orange-500/80"></i>
                    </button>

                    </div>
                </div>

                <p class="text-[10px] text-gray-500 uppercase tracking-widest italic mt-3">
                    Pilih metode pembayaran agar pesanan bisa diproses.
                </p>
                <p id="metode_error"
                    class="hidden mt-3 text-[10px] uppercase tracking-widest font-bold italic text-red-400">
                    Silakan pilih metode pembayaran dulu.
                </p>

            </div>

            <!-- BUTTON -->
            <div class="pt-4">
                <button type="submit"
                    class="w-full 
                        bg-gradient-to-r from-orange-600 to-orange-500
                        hover:from-orange-500 hover:to-orange-400
                        px-10 py-5 
                        rounded-2xl 
                        font-black 
                        uppercase 
                        text-sm 
                        tracking-widest 
                        transition duration-300 
                        shadow-lg 
                        hover:shadow-orange-500/30
                        hover:scale-[1.02]
                        active:scale-[0.98]
                        flex items-center justify-center gap-4">

                    Proses Checkout
                    <i class="fa-solid fa-bag-shopping"></i>
                </button>

                <p class="text-xs text-gray-500 uppercase tracking-widest italic text-center mt-6 leading-relaxed">
                    Dengan menekan tombol checkout, pesanan akan diproses dan stok akan divalidasi.
                </p>
            </div>

        </form>
    </div>

    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // ===================== ELEMEN UI =====================
  const dd = document.getElementById('metode_dropdown');
  const btn = document.getElementById('metode_btn');
  const menu = document.getElementById('metode_menu');
  const label = document.getElementById('metode_label');
  const valueInput = document.getElementById('metode_value');
  const icon = document.getElementById('metode_icon');

  const formCheckout = document.querySelector('form[action="proses/proses_checkout.php"]');
  const metodeError = document.getElementById('metode_error');

  const alamatTextarea = document.getElementById('alamat');
  const btnLokasi = document.getElementById('btnLokasi');
  const lokasiStatus = document.getElementById('lokasiStatus');

  // hidden input lokasi & ongkir
  const latInput = document.getElementById('lat');
  const lngInput = document.getElementById('lng');
  const jarakInput = document.getElementById('jarak_km');
  const ongkirInput = document.getElementById('ongkir_value');

  // ringkasan kiri
  const subtotalValue = document.getElementById('subtotal_value');
  const ongkirLeftDisplay = document.getElementById('ongkir_left_display');
  const grandTotalDisplay = document.getElementById('grand_total_display');

  // item count (server)
  const itemCountServer = document.getElementById('item_count_server');

  // ===================== KONFIG ONGKIR =====================
  const TOKO_LAT = 0.423796;
  const TOKO_LNG = 101.352096;

  const BASE_FEE = 5000;
  const PER_KM   = 2500;
  const MIN_FEE  = 10000;
  const MAX_FEE  = 25000;

  // ===================== HELPER =====================
  function formatRupiah(n) {
    return 'Rp ' + (n || 0).toLocaleString('id-ID');
  }

  function updateRingkasan(ongkir) {
    const subtotal = parseInt(subtotalValue?.value || "0", 10);
    const grand = subtotal + (parseInt(ongkir || 0, 10));

    if (ongkirLeftDisplay) ongkirLeftDisplay.textContent = formatRupiah(ongkir);
    if (grandTotalDisplay) grandTotalDisplay.textContent = formatRupiah(grand);
  }

  function jarakKm(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
      Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  }

  function hitungOngkir(km) {
    let ongkir = Math.round(BASE_FEE + (km * PER_KM));
    if (ongkir < MIN_FEE) ongkir = MIN_FEE;
    if (ongkir > MAX_FEE) ongkir = MAX_FEE;
    return ongkir;
  }

  function clearLokasiDanOngkir() {
    if (latInput) latInput.value = '';
    if (lngInput) lngInput.value = '';
    if (jarakInput) jarakInput.value = '0';
    if (ongkirInput) ongkirInput.value = '0';
    updateRingkasan(0);
  }

  function setPickupMode(isPickup) {
    if (!alamatTextarea) return;

    if (isPickup) {
      // reset data lokasi & ongkir
      clearLokasiDanOngkir();

      // matikan input alamat
      alamatTextarea.required = false;
      alamatTextarea.value = '';
      alamatTextarea.disabled = true;
      alamatTextarea.classList.add('opacity-60', 'cursor-not-allowed');

      // matikan tombol lokasi
      if (btnLokasi) {
        btnLokasi.disabled = true;
        btnLokasi.classList.add('opacity-60', 'cursor-not-allowed');
      }

      if (lokasiStatus) lokasiStatus.textContent = 'Ambil di toko (lokasi tidak diperlukan)';
    } else {
      // hidupkan lagi untuk COD
      alamatTextarea.disabled = false;
      alamatTextarea.required = true;
      alamatTextarea.classList.remove('opacity-60', 'cursor-not-allowed');

      if (btnLokasi) {
        btnLokasi.disabled = false;
        btnLokasi.classList.remove('opacity-60', 'cursor-not-allowed');
      }

      if (lokasiStatus) lokasiStatus.textContent = 'Lokasi belum diambil';
    }
  }

  function metodeLabel(val) {
    if (val === 'cod') return 'COD (Bayar di Tempat)';
    if (val === 'pickup') return 'Ambil di Tempat';
    return '-';
  }

  // ===================== INIT RINGKASAN =====================
  updateRingkasan(0);
  setPickupMode(false); // default (sebelum pilih, anggap non-pickup)

  // ===================== DROPDOWN METODE =====================
  if (!dd || !btn || !menu || !label || !valueInput || !icon) {
    console.error('Dropdown element tidak ditemukan. Cek ID HTML-nya.');
    return;
  }

  const openMenu = () => {
    menu.classList.remove('hidden');
    icon.classList.add('rotate-180');
  };

  const closeMenu = () => {
    menu.classList.add('hidden');
    icon.classList.remove('rotate-180');
  };

  btn.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    menu.classList.contains('hidden') ? openMenu() : closeMenu();
  });

  menu.querySelectorAll('button[data-value]').forEach((item) => {
    item.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();

      const val = item.getAttribute('data-value') || '';
      valueInput.value = val;

      // hilangkan warning kalau sudah pilih metode
      if (metodeError) metodeError.classList.add('hidden');
      btn.classList.remove('border-red-500/60');
      btn.classList.add('border-orange-500/40');

      // aktif/nonaktif alamat & tombol lokasi
      setPickupMode(val === 'pickup');

      // label dropdown
      if (val === 'cod') {
        label.textContent = 'COD (Bayar di Tempat)';
      } else if (val === 'pickup') {
        label.textContent = 'Ambil di Tempat';
      } else {
        label.textContent = '-- Pilih metode pembayaran --';
      }

      label.classList.remove('text-white/70');
      label.classList.add('text-white');

      closeMenu();
    });
  });

  document.addEventListener('click', () => closeMenu());
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMenu();
  });

  // ===================== MODAL KONFIRMASI CHECKOUT =====================
  const confirmModal   = document.getElementById('confirmModal');
  const confirmOverlay = document.getElementById('confirmOverlay');
  const confirmClose   = document.getElementById('confirmClose');
  const confirmCancel  = document.getElementById('confirmCancel');
  const confirmYes     = document.getElementById('confirmYes');

  const cItem       = document.getElementById('c_item');
  const cMetode     = document.getElementById('c_metode');
  const cOngkir     = document.getElementById('c_ongkir');
  const cTotal      = document.getElementById('c_total');
  const cAlamat     = document.getElementById('c_alamat');
  const cAlamatWrap = document.getElementById('c_alamat_wrap');

  let isConfirmedSubmit = false;

  function openConfirmModal() {
    if (!confirmModal) return;
    confirmModal.classList.remove('hidden');
    confirmModal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
  }

  function closeConfirmModal() {
    if (!confirmModal) return;
    confirmModal.classList.add('hidden');
    confirmModal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
  }

  function isiRingkasanModal() {
    const metode = valueInput?.value || '';
    const ongkir = parseInt(ongkirInput?.value || '0', 10);
    const subtotal = parseInt(subtotalValue?.value || '0', 10);
    const total = subtotal + ongkir;

    const itemText = (itemCountServer?.textContent || '0').trim();

    if (cItem) cItem.textContent = itemText + ' Item';
    if (cMetode) cMetode.textContent = metodeLabel(metode);
    if (cOngkir) cOngkir.textContent = formatRupiah(ongkir);
    if (cTotal) cTotal.textContent = formatRupiah(total);

    // alamat hanya kalau COD
    if (metode === 'cod') {
      const alamatVal = (alamatTextarea?.value || '').trim();
      if (cAlamat) cAlamat.textContent = alamatVal || '-';
      if (cAlamatWrap) cAlamatWrap.classList.remove('hidden');
    } else {
      if (cAlamatWrap) cAlamatWrap.classList.add('hidden');
    }
  }

  // close actions modal
  confirmOverlay?.addEventListener('click', closeConfirmModal);
  confirmClose?.addEventListener('click', closeConfirmModal);
  confirmCancel?.addEventListener('click', closeConfirmModal);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeConfirmModal();
  });

  const checkoutLoader = document.getElementById('checkoutLoader');

  function showCheckoutLoader(){
    if (!checkoutLoader) return;
    checkoutLoader.classList.remove('hidden');
    checkoutLoader.classList.add('flex');
  }

  confirmYes?.addEventListener('click', () => {
    closeConfirmModal();
    isConfirmedSubmit = true;

    // tampilkan loader dulu biar sempat render
    showCheckoutLoader();

    // disable tombol konfirmasi biar tidak double klik
    confirmYes.disabled = true;
    confirmYes.classList.add('opacity-70', 'cursor-not-allowed');

    setTimeout(() => {
      formCheckout?.submit();
    }, 150);
  });

  // ===================== VALIDASI SUBMIT + KONFIRMASI (TANPA PINDAH PAGE) =====================
  if (formCheckout) {
    formCheckout.addEventListener('submit', (e) => {
      // kalau sudah dikonfirmasi, biarkan submit jalan
    if (isConfirmedSubmit) {
      showCheckoutLoader();
      return;
    }

      // 1) wajib pilih metode
      if (!valueInput.value) {
        e.preventDefault();

        if (metodeError) metodeError.classList.remove('hidden');

        // highlight dropdown jadi merah
        btn.classList.add('border-red-500/60');
        btn.classList.remove('border-orange-500/40');

        // fokus ke bagian metode
        dd.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }

      // 2) kalau COD wajib alamat (pickup tidak wajib)
      if (valueInput.value === 'cod') {
        const alamatVal = (alamatTextarea?.value || '').trim();
        if (!alamatVal) {
          e.preventDefault();
          alamatTextarea?.focus();
          return;
        }
      }

      // 3) tampilkan modal konfirmasi
      e.preventDefault();
      isiRingkasanModal();
      openConfirmModal();
    });
  }

  // ===================== GEOLOCATION + HITUNG ONGKIR =====================
  if (btnLokasi) {
    btnLokasi.addEventListener('click', () => {
      // kalau pickup, jangan proses lokasi
      if (valueInput.value === 'pickup') return;

      if (!navigator.geolocation) {
        if (lokasiStatus) lokasiStatus.textContent = "Browser tidak support lokasi.";
        return;
      }

      if (lokasiStatus) lokasiStatus.textContent = "Mengambil lokasi...";
      btnLokasi.disabled = true;

      navigator.geolocation.getCurrentPosition(
        (pos) => {
          const userLat = pos.coords.latitude;
          const userLng = pos.coords.longitude;

          if (latInput) latInput.value = userLat;
          if (lngInput) lngInput.value = userLng;

          const km = jarakKm(TOKO_LAT, TOKO_LNG, userLat, userLng);
          const kmFix = Math.max(0, km);

          if (jarakInput) jarakInput.value = kmFix.toFixed(2);

          const ongkir = hitungOngkir(kmFix);
          if (ongkirInput) ongkirInput.value = ongkir;

          if (lokasiStatus) lokasiStatus.textContent = `Jarak ~ ${kmFix.toFixed(2)} km`;
          updateRingkasan(ongkir);

          // aktifkan lagi kalau tidak pickup
          if (valueInput.value !== 'pickup') btnLokasi.disabled = false;
        },
        () => {
          if (lokasiStatus) lokasiStatus.textContent = "Gagal ambil lokasi. Izinkan GPS.";
          if (valueInput.value !== 'pickup') btnLokasi.disabled = false;
        },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    });
  }
});
</script>

<div id="confirmModal"
  class="hidden fixed inset-0 z-[99999] items-center justify-center">
  
  <!-- overlay -->
  <div id="confirmOverlay" class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>

  <!-- box -->
  <div class="relative w-[92%] max-w-lg rounded-[2.5rem] border border-white/10
              bg-gradient-to-br from-[#111827]/95 to-[#0b0f1a]/95
              shadow-2xl overflow-hidden">

    <!-- glow -->
    <div class="absolute -top-16 -right-16 w-56 h-56 bg-orange-600/10 rounded-full blur-3xl"></div>

    <div class="relative p-8">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h3 class="text-xl font-black uppercase italic tracking-widest text-orange-500">
            Konfirmasi Checkout
          </h3>
          <p class="text-[10px] uppercase tracking-widest italic text-gray-400 mt-2">
            Pastikan data sudah benar sebelum diproses.
          </p>
        </div>

        <button type="button" id="confirmClose"
          class="w-10 h-10 rounded-2xl bg-black/40 border border-white/10
                 hover:border-orange-500/60 transition flex items-center justify-center">
          <i class="fa-solid fa-xmark text-orange-500"></i>
        </button>
      </div>

      <!-- ringkasan -->
      <div class="mt-6 space-y-3">
        <div class="glass-inner p-5 rounded-2xl border border-white/5">
          <div class="flex items-center justify-between">
            <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic">Item</p>
            <p id="c_item" class="text-sm font-black text-white uppercase italic tracking-widest">0</p>
          </div>
          <div class="h-px bg-white/10 my-4"></div>

          <div class="flex items-center justify-between">
            <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic">Metode</p>
            <p id="c_metode" class="text-sm font-black text-white uppercase italic tracking-widest">-</p>
          </div>

          <div class="flex items-center justify-between mt-3">
            <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic">Ongkir</p>
            <p id="c_ongkir" class="text-sm font-black text-orange-500 uppercase italic tracking-widest">Rp 0</p>
          </div>

          <div class="flex items-center justify-between mt-3">
            <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic">Total</p>
            <p id="c_total" class="text-2xl font-black text-orange-500 uppercase italic tracking-tighter">
              Rp 0
            </p>
          </div>
        </div>

        <div id="c_alamat_wrap"
             class="glass-inner p-5 rounded-2xl border border-white/5">
          <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold italic">Alamat</p>
          <p id="c_alamat" class="mt-2 text-sm text-white/90 leading-relaxed"></p>
        </div>
      </div>

      <!-- actions -->
      <div class="mt-7 grid grid-cols-2 gap-4">
        <button type="button" id="confirmCancel"
          class="px-6 py-4 rounded-2xl bg-black/40 border border-white/10
                 hover:border-orange-500/60 transition text-xs uppercase tracking-widest font-bold italic">
          Batal
        </button>

        <button type="button" id="confirmYes"
          class="px-6 py-4 rounded-2xl font-black uppercase text-xs tracking-widest
                 bg-gradient-to-r from-orange-600 to-orange-500
                 hover:from-orange-500 hover:to-orange-400 transition
                 shadow-lg hover:shadow-orange-500/30">
          Ya, Proses
        </button>
      </div>

      <p class="mt-5 text-[10px] uppercase tracking-widest italic text-gray-500 text-center">
        Setelah diproses, stok akan divalidasi.
      </p>
    </div>
  </div>
</div>
<div id="checkoutLoader" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/60 backdrop-blur-sm">
  <div class="glass-inner rounded-3xl px-10 py-8 border border-white/10 text-center">
    <div class="mx-auto h-12 w-12 rounded-full border-4 border-white/20 border-t-orange-500 animate-spin"></div>
    <p class="mt-5 text-xs font-bold uppercase tracking-widest italic text-gray-200">
      Memproses pesanan...
    </p>
  </div>
</div>
</body>
</html>
