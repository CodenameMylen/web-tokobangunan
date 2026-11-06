<?php
require_once __DIR__ . '/_admin_session.php';

if (empty($_SESSION['login_admin'])) {
    header("Location: login.php");
    exit;
}

include '../koneksi/koneksi.php';

// NOTIF ORDER BARU (anggap baru = pending)
$resNew = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM orders WHERE status='pending'");
$newOrders = 0;
if ($resNew) {
    $newOrders = (int)(mysqli_fetch_assoc($resNew)['total'] ?? 0);
}


$slug = 'layanan';
$username = $_SESSION['username'] ?? 'Administrator';
$current = basename($_SERVER['PHP_SELF']);

function getJsonPage(mysqli $koneksi, string $slug): array {
    $stmt = mysqli_prepare($koneksi, "SELECT content FROM site_pages WHERE slug = ? LIMIT 1");
    if (!$stmt) return [];

    mysqli_stmt_bind_param($stmt, "s", $slug);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;

    if (!$row || empty($row['content'])) return [];

    $data = json_decode($row['content'], true);
    return is_array($data) ? $data : [];
}

function saveJsonPage(mysqli $koneksi, string $slug, array $data): void {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);

    $stmt = mysqli_prepare($koneksi,
        "INSERT INTO site_pages (slug, content)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE content = VALUES(content)"
    );
    mysqli_stmt_bind_param($stmt, "ss", $slug, $json);
    mysqli_stmt_execute($stmt);
}

$msg = null;
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['reset'])) {
        $del = mysqli_prepare($koneksi, "DELETE FROM site_pages WHERE slug = ?");
        mysqli_stmt_bind_param($del, "s", $slug);
        mysqli_stmt_execute($del);
        $msg = "Konten layanan di-reset ke default.";
    }

    if (isset($_POST['save'])) {
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');

        // ambil array layanan dari form (dinamis)
        $servicesIn = $_POST['services'] ?? [];
        $services = [];

        if (is_array($servicesIn)) {
            foreach ($servicesIn as $svc) {
                $st = trim($svc['title'] ?? '');
                $sd = trim($svc['desc'] ?? '');

                // skip kalau keduanya kosong
                if ($st === '' && $sd === '') continue;

                $services[] = ['title' => $st, 'desc' => $sd];
            }
        }

        if ($title === '') {
            $err = "Judul halaman wajib diisi.";
        } elseif (count($services) === 0) {
            $err = "Minimal 1 layanan harus diisi.";
        } else {
            $data = [
                'type' => 'layanan_form_v2',
                'title' => $title,
                'subtitle' => $subtitle,
                'services' => $services,
            ];
            saveJsonPage($koneksi, $slug, $data);
            $msg = "Konten layanan berhasil disimpan.";
        }
    }
}

$data = getJsonPage($koneksi, $slug);

// default value kalau belum ada
$titleVal = $data['title'] ?? 'Layanan Kami';
$subtitleVal = $data['subtitle'] ?? 'Solusi layanan terbaik untuk memenuhi kebutuhan material bangunan Anda secara cepat, praktis, dan terpercaya.';

$servicesVal = [];
if (!empty($data['services']) && is_array($data['services'])) {
    $servicesVal = $data['services'];
}

if (count($servicesVal) === 0) {
    $servicesVal = [
        ['title' => 'Pemesanan via WhatsApp', 'desc' => 'Website ini dilengkapi dengan fitur pemesanan melalui WhatsApp yang memungkinkan pelanggan melakukan pemesanan secara langsung dan praktis.'],
        ['title' => 'Layanan Profesional & Terpercaya', 'desc' => 'Membantu toko bangunan memperluas jangkauan pemasaran, meningkatkan profesionalisme layanan, serta memudahkan pelanggan mengakses informasi produk.'],
        ['title' => 'Adaptif di Era Digital', 'desc' => 'Dengan adanya website profil dan katalog produk berbasis web, toko bangunan dapat beradaptasi dengan perkembangan teknologi.'],
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Halaman Layanan</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<style>
body { background:#0F1115; color:white; }
.card-glass {
    background: rgba(26,29,35,.8);
    backdrop-filter: blur(10px);
    border:1px solid rgba(255,255,255,.05);
}
.accent-orange { color:#FF6600; }
.bg-orange-maju { background:#FF6600; }

/* scroll bar kecil biar enak */
#servicesWrap::-webkit-scrollbar { height: 10px; }
#servicesWrap::-webkit-scrollbar-thumb { background: rgba(255,255,255,.10); border-radius: 999px; }
#servicesWrap::-webkit-scrollbar-track { background: rgba(255,255,255,.03); border-radius: 999px; }
</style>
</head>

<body class="font-sans">
<div class="flex h-screen">

<!-- SIDEBAR -->
<aside class="w-72 bg-[#1A1D23] border-r border-gray-800 flex flex-col p-6">
    <div class="flex items-center gap-3 mb-10">
        <div class="bg-orange-maju p-2 rounded-lg">
            <i data-lucide="hammer"></i>
        </div>
        <h1 class="font-bold text-xl italic">
            MAJU <span class="accent-orange">USAHA</span>
        </h1>
    </div>

    <nav class="space-y-2 flex-1">
        <div class="space-y-2">
            <p class="text-[10px] text-gray-500 uppercase tracking-widest px-3">Manajemen Produk</p>

            <a href="index.php"
               class="flex items-center gap-3 p-3 rounded-xl <?= $current === 'index.php' ? 'bg-orange-maju/10 text-orange-500 font-bold' : 'text-gray-400 hover:bg-gray-800' ?>">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>

            <a href="tambah_produk.php"
               class="flex items-center gap-3 p-3 rounded-xl <?= $current === 'tambah_produk.php' ? 'bg-orange-maju/10 text-orange-500 font-bold' : 'text-gray-400 hover:bg-gray-800' ?>">
                <i data-lucide="plus-square"></i> Tambah Barang
            </a>

        </div>

        <div class="space-y-2">
            <p class="text-[10px] text-gray-500 uppercase tracking-widest px-3">Manajemen Website</p>

            <a href="users.php"
               class="flex items-center gap-3 p-3 rounded-xl <?= $current === 'users.php' ? 'bg-orange-maju/10 text-orange-500 font-bold' : 'text-gray-400 hover:bg-gray-800' ?>">
                <i data-lucide="users"></i> Kelola Users
            </a>

            <a href="page_edit.php"
               class="flex items-center gap-3 p-3 rounded-xl <?= $current === 'halaman.php' ? 'bg-orange-maju/10 text-orange-500 font-bold' : 'text-gray-400 hover:bg-gray-800' ?>">
                <i data-lucide="file-text"></i> Kelola Page User
            </a>
        </div>

        <div class="space-y-2">
            <p class="text-[10px] text-gray-500 uppercase tracking-widest px-3">Pemesanan Produk</p>

                <a href="pengelolaan_pesanan.php"
                class="flex items-center justify-between p-3 rounded-xl <?= $current === 'pengelolaan_pesanan.php' ? 'bg-orange-maju/10 text-orange-500 font-bold' : 'text-gray-400 hover:bg-gray-800' ?>">
                    <span class="flex items-center gap-3">
                        <i data-lucide="shopping-cart"></i> Pesanan
                    </span>

                    <?php if ($newOrders > 0): ?>
                        <span class="min-w-[22px] h-[22px] px-2 flex items-center justify-center
                                    text-[11px] font-bold rounded-full bg-orange-maju text-black">
                            <?= $newOrders ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="laporan_penjualan.php"
                    class="flex items-center gap-3 p-3 rounded-xl <?= $current === 'laporan_penjualan.php' ? 'bg-orange-maju/10 text-orange-500 font-bold' : 'text-gray-400 hover:bg-gray-800' ?>">
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

<header class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold">Edit Halaman: <span class="accent-orange">Layanan</span></h2>
        <p class="text-gray-400 text-sm mt-1">Isi form di bawah, lalu klik Simpan.<p>
    </div>

    <div class="text-right">
        <p class="font-bold"><?= htmlspecialchars($username) ?></p>
        <p class="text-[10px] text-orange-500 uppercase">Administrator</p>
    </div>
</header>

<?php if ($msg): ?>
    <div class="mb-4 p-4 rounded-2xl bg-green-500/10 border border-green-500/20 text-green-300">
        <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<?php if ($err): ?>
    <div class="mb-4 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-300">
        <?= htmlspecialchars($err) ?>
    </div>
<?php endif; ?>

<div class="card-glass rounded-3xl p-6">
    <form method="post" class="space-y-6">

        <!-- Judul & Subjudul -->
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-300 mb-2">Judul Halaman</label>
                <input type="text" name="title"
                       value="<?= htmlspecialchars($titleVal) ?>"
                       class="w-full bg-[#0F1115] border border-gray-800 rounded-2xl px-4 py-3 text-sm outline-none"
                       placeholder="Contoh: Layanan Kami">
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-2">Subjudul</label>
                <input type="text" name="subtitle"
                       value="<?= htmlspecialchars($subtitleVal) ?>"
                       class="w-full bg-[#0F1115] border border-gray-800 rounded-2xl px-4 py-3 text-sm outline-none"
                       placeholder="Contoh: Solusi layanan terbaik untuk kebutuhan Anda...">
            </div>
        </div>

        <!-- LIST LAYANAN (HORIZONTAL SCROLL) -->
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-300 font-semibold">Daftar Layanan</p>

            <button type="button"
                    class="px-4 py-2 rounded-2xl bg-[#1A1D23] border border-gray-800 text-gray-200 hover:bg-gray-800 text-sm flex items-center gap-2"
                    onclick="addService()">
                <span class="text-orange-500 font-bold">+</span> Tambah Layanan
            </button>
        </div>

        <div id="servicesWrap"
             class="flex gap-4 overflow-x-auto pb-3 snap-x snap-mandatory">
            <?php foreach ($servicesVal as $idx => $svc): ?>
                <?php
                    $no = $idx + 1;
                    $svTitle = $svc['title'] ?? '';
                    $svDesc  = $svc['desc'] ?? '';
                ?>
                <div class="service-item snap-start flex-shrink-0 w-[360px] bg-black/20 border border-gray-800 rounded-3xl p-5">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-full bg-orange-maju/20 text-orange-400 flex items-center justify-center text-xs font-bold service-no">
                                <?= $no ?>
                            </span>
                            <p class="font-bold svc-label">Layanan <?= $no ?></p>
                        </div>

                        <button type="button"
                                class="px-3 py-2 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 hover:bg-red-500/20 text-xs"
                                onclick="removeService(this)">
                            Hapus
                        </button>
                    </div>

                    <label class="block text-xs text-gray-400 mb-2">Judul</label>
                    <input type="text"
                           name="services[<?= $idx ?>][title]"
                           value="<?= htmlspecialchars($svTitle) ?>"
                           class="w-full bg-[#0F1115] border border-gray-800 rounded-2xl px-3 py-2 text-sm outline-none"
                           placeholder="Judul layanan">

                    <label class="block text-xs text-gray-400 mt-4 mb-2">Deskripsi</label>
                    <textarea name="services[<?= $idx ?>][desc]" rows="7"
                              class="w-full bg-[#0F1115] border border-gray-800 rounded-2xl p-3 text-sm outline-none"
                              placeholder="Tulis deskripsi layanan..."><?= htmlspecialchars($svDesc) ?></textarea>

                    <p class="text-[10px] text-gray-500 mt-2">Boleh pakai Enter untuk baris baru.</p>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="text-[11px] text-gray-500">Geser ke kanan/kiri untuk melihat layanan lainnya.</p>

        <!-- tombol bawah -->
        <div class="flex flex-wrap gap-3 pt-2">
            <button type="submit" name="save"
                    class="px-7 py-3 rounded-2xl bg-orange-maju text-black font-bold hover:opacity-90">
                Simpan
            </button>

            <button type="submit" name="reset"
                    onclick="return confirm('Reset ke default layanan.php? Data yang tersimpan akan dihapus.');"
                    class="px-7 py-3 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-300 hover:bg-red-500/20">
                Reset
            </button>

            <a href="../layanan.php" target="_blank"
               class="px-7 py-3 rounded-2xl bg-blue-500/10 border border-blue-500/20 text-blue-300 hover:bg-blue-500/20">
                Preview Layanan
            </a>
        </div>

    </form>
</div>

</main>
</div>

<script>
function renumberServices() {
    const items = document.querySelectorAll('#servicesWrap .service-item');
    items.forEach((item, i) => {
        const noEl = item.querySelector('.service-no');
        const labelEl = item.querySelector('.svc-label');
        if (noEl) noEl.textContent = (i + 1);
        if (labelEl) labelEl.textContent = `Layanan ${i + 1}`;

        const title = item.querySelector('input[name^="services["]');
        const desc  = item.querySelector('textarea[name^="services["]');

        if (title) title.name = `services[${i}][title]`;
        if (desc)  desc.name  = `services[${i}][desc]`;
    });
}

function removeService(btn) {
    const item = btn.closest('.service-item');
    if (!item) return;

    const total = document.querySelectorAll('#servicesWrap .service-item').length;
    if (total <= 1) {
        alert('Minimal 1 layanan harus ada.');
        return;
    }

    item.remove();
    renumberServices();
}

function addService() {
    const wrap = document.getElementById('servicesWrap');
    const idx = wrap.querySelectorAll('.service-item').length;

    const div = document.createElement('div');
    div.className = 'service-item snap-start flex-shrink-0 w-[360px] bg-black/20 border border-gray-800 rounded-3xl p-5';
    div.innerHTML = `
        <div class="flex items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-full bg-orange-maju/20 text-orange-400 flex items-center justify-center text-xs font-bold service-no">${idx+1}</span>
                <p class="font-bold svc-label">Layanan ${idx+1}</p>
            </div>

            <button type="button"
                    class="px-3 py-2 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 hover:bg-red-500/20 text-xs"
                    onclick="removeService(this)">
                Hapus
            </button>
        </div>

        <label class="block text-xs text-gray-400 mb-2">Judul</label>
        <input type="text"
               name="services[${idx}][title]"
               class="w-full bg-[#0F1115] border border-gray-800 rounded-2xl px-3 py-2 text-sm outline-none"
               placeholder="Judul layanan">

        <label class="block text-xs text-gray-400 mt-4 mb-2">Deskripsi</label>
        <textarea name="services[${idx}][desc]" rows="7"
                  class="w-full bg-[#0F1115] border border-gray-800 rounded-2xl p-3 text-sm outline-none"
                  placeholder="Tulis deskripsi layanan..."></textarea>

        <p class="text-[10px] text-gray-500 mt-2">Boleh pakai Enter untuk baris baru.</p>
    `;

    wrap.appendChild(div);

    // auto scroll ke card baru
    div.scrollIntoView({ behavior: 'smooth', inline: 'end', block: 'nearest' });

    renumberServices();
}
</script>

<script>lucide.createIcons();</script>
</body>
</html>
