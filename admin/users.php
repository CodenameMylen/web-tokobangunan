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


/* =====================
   SEARCH
   ===================== */
$search = isset($_GET['search'])
    ? mysqli_real_escape_string($koneksi, trim($_GET['search']))
    : '';

/* =====================
   AUTO DETECT USERS COLUMNS
   ===================== */
$cols = [];
$resCols = mysqli_query($koneksi, "SHOW COLUMNS FROM users");
if ($resCols) {
    while ($c = mysqli_fetch_assoc($resCols)) {
        $cols[] = strtolower($c['Field']);
    }
}

// detect primary key
$pk = in_array('id_user', $cols, true) ? 'id_user' : (in_array('id', $cols, true) ? 'id' : 'id');

// detect phone column
$phoneCandidates = ['phone', 'no_hp', 'nomor_hp', 'hp', 'telp', 'telepon'];
$phoneCol = null;
foreach ($phoneCandidates as $cand) {
    if (in_array($cand, $cols, true)) {
        $phoneCol = $cand;
        break;
    }
}
if ($phoneCol === null) $phoneCol = '__NO_PHONE_COL__';

// where
$where = "";
if ($search !== '') {
    if ($phoneCol !== '__NO_PHONE_COL__') {
        $where = "WHERE username LIKE '%$search%' OR email LIKE '%$search%' OR `$phoneCol` LIKE '%$search%'";
    } else {
        $where = "WHERE username LIKE '%$search%' OR email LIKE '%$search%'";
    }
}

/* =====================
   QUERY USERS
   ===================== */
$query_users = mysqli_query($koneksi, "SELECT * FROM users $where ORDER BY `$pk` DESC");
if (!$query_users) {
    die("Query users gagal: " . mysqli_error($koneksi));
}

$username = $_SESSION['username'] ?? 'Administrator';
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Kelola Users</title>
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

<header class="flex justify-between items-center mb-10">

<div class="flex items-center gap-3">

    <form method="GET" class="relative">
        <i data-lucide="search"
           class="absolute left-4 top-3 text-gray-500"
           size="18"></i>

        <input type="text"
               name="search"
               value="<?= htmlspecialchars($search) ?>"
               placeholder="Cari username / email / nomor HP..."
               class="w-80 bg-[#1A1D23] border border-gray-800 rounded-full py-3 pl-12 pr-4 text-sm outline-none">
    </form>

    <?php if (!empty($search)): ?>
        <a href="users.php"
           class="px-4 py-3 rounded-full bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 text-sm flex items-center gap-2">
            <i data-lucide="rotate-ccw" size="16"></i>
        </a>
    <?php endif; ?>

</div>


<div class="text-right">
    <p class="font-bold"><?= htmlspecialchars($username) ?></p>
    <p class="text-[10px] text-orange-500 uppercase">Administrator</p>
</div>
</header>

<!-- TABLE USERS -->
<div class="card-glass rounded-3xl overflow-hidden">
<table class="w-full">
<thead class="bg-black/30 text-gray-400 text-[10px] uppercase">
<tr>
    <th class="p-6 text-left">Username</th>
    <th class="p-6 text-left">Email</th>
    <th class="p-6 text-left">Nomor HP</th>
</tr>
</thead>

<tbody class="divide-y divide-gray-800">
<?php if (mysqli_num_rows($query_users) == 0): ?>
<tr>
    <td colspan="4" class="p-8 text-center text-gray-400">Data user tidak ditemukan.</td>
</tr>
<?php else: ?>
<?php while ($u = mysqli_fetch_assoc($query_users)): ?>
<?php
$uid = (int)($u[$pk] ?? 0);
$hp  = ($phoneCol !== '__NO_PHONE_COL__') ? ($u[$phoneCol] ?? '-') : '-';
?>
<tr class="hover:bg-white/[0.03]">
    <td class="p-6 font-bold"><?= htmlspecialchars($u['username'] ?? '-') ?></td>
    <td class="p-6 text-gray-300 text-sm"><?= htmlspecialchars($u['email'] ?? '-') ?></td>
    <td class="p-6 text-gray-300 text-sm">
        <?= htmlspecialchars($hp ?: '-') ?>
        <?php if ($phoneCol === '__NO_PHONE_COL__'): ?>
            <span class="ml-2 text-[10px] text-gray-500">(kolom HP tidak ada)</span>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
<?php endif; ?>
</tbody>
</table>
</div>

</main>
</div>

<script>lucide.createIcons();</script>
</body>
</html>
