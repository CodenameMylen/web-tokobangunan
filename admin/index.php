<?php
// Pisahkan session admin
session_name('ADMINSESSID');
session_start();

if (empty($_SESSION['login_admin']) || empty($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

include '../koneksi/koneksi.php';

/* =====================
   HITUNG ORDER BARU
===================== */
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

$where = $search
    ? "WHERE p.nama_stock LIKE '%$search%' OR p.kode_stock LIKE '%$search%'"
    : "";

/* =====================
   QUERY PRODUK
===================== */
$query_produk = mysqli_query(
    $koneksi,
    "SELECT p.*, COALESCE(i.stok, p.qty) AS stok_view
     FROM produk p
     LEFT JOIN inventory i ON i.produk_id = p.id_produk
     $where
     ORDER BY p.id_produk DESC"
);

if (!$query_produk) {
    die("Query produk gagal: " . mysqli_error($koneksi));
}

/* =====================
   STATISTIK
===================== */
$total_produk = mysqli_fetch_assoc(
    mysqli_query($koneksi, "SELECT COUNT(*) total FROM produk")
)['total'] ?? 0;

$stok_habis = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "SELECT COUNT(*) habis
         FROM produk p
         LEFT JOIN inventory i ON i.produk_id = p.id_produk
         WHERE COALESCE(i.stok, p.qty) <= 0"
    )
)['habis'] ?? 0;

$total_filtered = mysqli_fetch_assoc(
    mysqli_query($koneksi, "SELECT COUNT(*) total FROM produk p $where")
)['total'] ?? 0;

$admin_username = $_SESSION['admin_username'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Admin Panel</title>
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

    <?php $current = basename($_SERVER['PHP_SELF']); ?>

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
    <form method="GET" class="relative w-1/3">
        <i data-lucide="search" class="absolute left-4 top-3 text-gray-500" size="18"></i>
        <input type="text" name="search"
               value="<?= htmlspecialchars($search) ?>"
               placeholder="Cari kode atau nama material..."
               class="w-full bg-[#1A1D23] border border-gray-800 rounded-full py-3 pl-12 pr-4 text-sm outline-none">
    </form>

    <!-- tombol tambah stok + info admin -->
    <div class="flex items-center gap-4">
        <a href="tambah_stok.php"
           class="text-xs text-white/60 hover:text-orange-400 transition border border-white/10 px-4 py-2 rounded-xl bg-white/5">
            + Tambah Stok
        </a>

        <div class="text-right">
            <p class="font-bold"><?= htmlspecialchars($admin_username) ?></p>
            <p class="text-[10px] text-orange-500 uppercase">Administrator</p>
        </div>
    </div>
</header>

<!-- TABLE -->
<div class="card-glass rounded-3xl overflow-hidden">
<table class="w-full">
<thead class="bg-black/30 text-gray-400 text-[10px] uppercase">
<tr>
    <th class="p-6 text-left">Gambar</th>
    <th class="p-6 text-left">Kode</th>
    <th class="p-6 text-left">Nama</th>
    <th class="p-6 text-left">kategori</th>

    <!-- HARGA (TAMBAHAN) -->
    <th class="p-6 text-left">Harga</th>

    <th class="p-6 text-left">Stok</th>
    <th class="p-6 text-center">Aksi</th>
</tr>
</thead>

<tbody class="divide-y divide-gray-800">
<?php if (mysqli_num_rows($query_produk) == 0): ?>
<tr>
    <!-- colspan dari 6 jadi 7 karena tambah kolom Harga -->
    <td colspan="7" class="p-8 text-center text-gray-400">Data tidak ditemukan.</td>
</tr>
<?php else: ?>
<?php while ($row = mysqli_fetch_assoc($query_produk)): ?>
<?php 
    // stok benar dari join
    $qty = (int)($row['stok_view'] ?? 0);

    $label = $qty <= 0 ? 'Habis' : ($qty < 10 ? 'Menipis' : 'Aman');
    $color = $qty <= 0 ? 'bg-red-500/10 text-red-500' :
             ($qty < 10 ? 'bg-yellow-500/10 text-yellow-500' :
             'bg-green-500/10 text-green-500');

    $imgFile = $row['gambar'] ?? '';
    $imgPath = __DIR__ . "/../assets/uploads/" . $imgFile;
    $imgUrl  = "../assets/uploads/" . $imgFile;

    // HARGA (TAMBAHAN)
    $harga = (int)($row['harga'] ?? 0);
    $harga_rp = "Rp " . number_format($harga, 0, ',', '.');
?>
<tr class="hover:bg-white/[0.03]">
    <td class="p-6">
        <?php if (!empty($imgFile) && file_exists($imgPath)): ?>
            <img src="<?= $imgUrl ?>" class="w-12 h-12 object-cover rounded-lg border border-gray-700">
        <?php else: ?>
            <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-800 text-gray-500 text-[10px]">
                N/A
            </div>
        <?php endif; ?>
    </td>
    <td class="p-6 font-mono text-orange-500 text-xs">
        <?= htmlspecialchars($row['kode_stock']) ?>
    </td>

    <td class="p-6 font-bold">
        <?= htmlspecialchars($row['nama_stock']) ?>
    </td>

    <td class="p-6 text-gray-400 text-sm">
        <?= htmlspecialchars($row['milik']) ?>
    </td>

    <!-- HARGA (TAMBAHAN) -->
    <td class="p-6 text-gray-200 text-sm font-semibold">
        <?= $harga_rp ?>
    </td>

    <td class="p-6">
        <span class="px-3 py-1 rounded-full text-[10px] font-bold <?= $color ?>">
            <?= $qty ?> (<?= $label ?>)
        </span>
    </td>

    <td class="p-6 text-center">
        <div class="flex justify-center gap-2">
            <a href="edit_produk.php?id=<?= (int)$row['id_produk'] ?>" class="p-2 bg-blue-500/10 rounded-lg text-blue-400">
                <i data-lucide="edit" size="16"></i>
            </a>

            <a href="hapus_produk.php?id=<?= (int)$row['id_produk'] ?>"
               onclick="return confirm('Hapus data ini?')"
               class="p-2 bg-red-500/10 rounded-lg text-red-400">
                <i data-lucide="trash" size="16"></i>
            </a>
        </div>
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
