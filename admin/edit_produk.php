<?php
// ================= SESSION ADMIN =================
session_name('ADMINSESSID');
session_start();

if (empty($_SESSION['login_admin'])) {
    header("Location: login.php");
    exit;
}

// ================= KONEKSI =================
require_once __DIR__ . '/../koneksi/koneksi.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID tidak ditemukan / tidak valid");
}

$id_produk = (int)$_GET['id'];

// ================= AMBIL DATA PRODUK =================
$stmt = mysqli_prepare($koneksi, "SELECT id_produk, kode_stock, nama_stock, harga, merk, gambar FROM produk WHERE id_produk = ? LIMIT 1");
if (!$stmt) {
    die("Prepare gagal: " . mysqli_error($koneksi));
}

mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row) {
    die("Data tidak ditemukan");
}

// ================= PATH GAMBAR =================
$projectFolder = '/web_tokobangunan'; // sesuaikan kalau nama folder beda
$uploadWebPath = $projectFolder . '/assets/uploads/';
$uploadDiskDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . $uploadWebPath;

$gambar = $row['gambar'] ?? '';
$imgSrc = $uploadWebPath . $gambar;
$imgDiskPath = $uploadDiskDir . $gambar;

function esc($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Produk</title>
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
        .bg-orange-maju { background:#FF6600; }
        .text-orange-maju { color:#FF6600; }
    </style>
</head>
<body>

<div class="max-w-4xl mx-auto py-24 px-6">

    <div class="mb-12">
        <h1 class="text-4xl font-black italic uppercase">
            Edit <span class="text-orange-maju">Produk</span>
        </h1>
        <p class="text-gray-400 text-xs uppercase tracking-widest mt-2">
            Perbarui data material bangunan
        </p>
    </div>

    <div class="card-glass rounded-3xl p-10">

        <form action="proses/proses_edit.php"
              method="POST"
              enctype="multipart/form-data"
              class="space-y-8">

            <input type="hidden" name="id_produk" value="<?= (int)$row['id_produk']; ?>">
            <input type="hidden" name="gambar_lama" value="<?= esc($gambar); ?>">

            <!-- GAMBAR -->
            <div>
                <label class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">
                    Gambar Produk
                </label>

                <?php if (!empty($gambar) && file_exists($imgDiskPath)): ?>
                    <img src="<?= esc($imgSrc); ?>"
                         class="mt-4 w-40 h-40 object-cover rounded-xl border border-gray-700">
                <?php else: ?>
                    <p class="text-xs text-gray-500 mt-4">Belum ada gambar</p>
                <?php endif; ?>

                <input type="file" name="gambar" accept="image/*"
                       class="w-full mt-4 bg-[#1A1D23] border border-gray-800 rounded-xl px-5 py-4 text-sm">
                <p class="text-[10px] text-gray-500 mt-2">
                    Kosongkan jika tidak ingin mengganti gambar.
                </p>
            </div>

            <!-- KODE (TIDAK BOLEH DIUBAH) -->
            <div>
                <label class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">
                    Kode Stock (tidak bisa diubah)
                </label>
                <input type="text" name="kode_stock"
                       value="<?= esc($row['kode_stock']); ?>"
                       readonly
                       class="w-full mt-2 bg-[#111827] border border-gray-800 rounded-xl px-5 py-4 text-sm text-gray-300 cursor-not-allowed">
            </div>

            <!-- NAMA (BOLEH) -->
            <div>
                <label class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">
                    Nama Barang
                </label>
                <input type="text" name="nama_stock"
                       value="<?= esc($row['nama_stock']); ?>" required
                       class="w-full mt-2 bg-[#1A1D23] border border-gray-800 rounded-xl px-5 py-4 text-sm">
            </div>

            <!-- HARGA (BOLEH) -->
            <div>
                <label class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">
                    Harga
                </label>
                <input type="number" name="harga" min="0"
                       value="<?= (int)($row['harga'] ?? 0); ?>" required
                       class="w-full mt-2 bg-[#1A1D23] border border-gray-800 rounded-xl px-5 py-4 text-sm">
            </div>

           
            <div>
            <label class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">
                Kategori
            </label>
            <input type="text" name="milik"
                    value="<?= esc($row['milik'] ?? ''); ?>" required
                    class="w-full mt-2 bg-[#1A1D23] border border-gray-800 rounded-xl px-5 py-4 text-sm">
            </div>

            <!-- BUTTON -->
            <div class="flex gap-4 pt-8">
                <button type="submit"
                        class="bg-orange-maju px-10 py-4 rounded-xl font-black uppercase tracking-widest text-sm">
                    Simpan Perubahan
                </button>

                <a href="index.php"
                   class="px-10 py-4 rounded-xl border border-gray-700 uppercase tracking-widest text-sm">
                    Batal
                </a>
            </div>

        </form>
    </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>