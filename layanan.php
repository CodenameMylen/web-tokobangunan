<?php
$brandShort = "Maju Usaha Bangunan";
$waNumber = "6281371392939";
$linkWA = "https://wa.me/$waNumber?text=Halo%20Maju%20Usaha%20Bangunan,%20saya%20ingin%20bertanya%20tentang%20layanan...";

include 'koneksi/koneksi.php';

/**
 * Ambil konten dari DB berdasarkan slug.
 * Return string content (bisa HTML atau JSON) atau '' jika tidak ada.
 */
function getPageContent(mysqli $koneksi, string $slug): string {
    $stmt = mysqli_prepare($koneksi, "SELECT content FROM site_pages WHERE slug = ? LIMIT 1");
    if (!$stmt) return '';
    mysqli_stmt_bind_param($stmt, "s", $slug);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    return $row['content'] ?? '';
}

$pageContent = getPageContent($koneksi, 'layanan');

/**
 * Kalau content adalah JSON layanan (v2), decode ke array
 */
$pageData = null;
if (!empty($pageContent)) {
    $decoded = json_decode($pageContent, true);
    if (is_array($decoded) && ($decoded['type'] ?? '') === 'layanan_form_v2') {
        $pageData = $decoded;
    }
}
$currentPage = basename($_SERVER['PHP_SELF']);

function navClass(string $file, string $currentPage): string {
    if ($file === $currentPage) {
        // ACTIVE (stay)
        return 'text-orange-500';
    }
    // NORMAL
    return 'text-gray-400 hover:text-orange-500 transition';
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Layanan Kami - <?php echo $brandShort; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- CSS GLOBAL -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="fixed w-full z-50 glass py-5">
    <div class="max-w-7xl mx-auto px-8 flex items-center justify-between">
        <div class="flex items-center gap-12">
            <div class="flex items-center gap-3">
                <div class="bg-orange-600 p-2 rounded-xl"><i class="fas fa-hammer text-white"></i></div>
                <span class="font-extrabold text-sm tracking-tight uppercase italic"><?php echo $brandShort; ?></span>
            </div>
            <div class="hidden lg:flex gap-10 text-[10px] font-bold uppercase tracking-widest">
                <a href="index.php" class="<?= navClass('index.php', $currentPage) ?>">Beranda</a>
                <a href="produk.php" class="<?= navClass('produk.php', $currentPage) ?>">Katalog Produk</a>
                <a href="layanan.php" class="<?= navClass('layanan.php', $currentPage) ?>">Layanan</a>
                <a href="cara-pemesanan.php" class="<?= navClass('cara-pemesanan.php', $currentPage) ?>">Cara Pemesanan</a>
            </div>

        </div>
        <div class="hidden sm:block text-[10px] font-bold text-gray-500 uppercase tracking-widest italic">
            Kabupaten Kampar, Riau
        </div>
    </div>
</nav>

<!-- KONTEN -->
<section class="pt-[150px] pb-32 px-8 max-w-7xl mx-auto">

<?php if (!empty($pageData)): ?>
    <!-- HEADER (DARI ADMIN) -->
    <div class="text-center mb-20">
        <h1 class="text-5xl md:text-6xl font-black uppercase italic tracking-tighter">
            <?= htmlspecialchars($pageData['title'] ?? 'Layanan Kami') ?>
        </h1>
        <p class="text-gray-500 text-xs uppercase tracking-widest italic mt-6 max-w-3xl mx-auto">
            <?= htmlspecialchars($pageData['subtitle'] ?? '') ?>
        </p>
    </div>

    <!-- KONTEN UTAMA -->
    <div class="flex justify-center">
        <div class="space-y-8">

            <?php foreach (($pageData['services'] ?? []) as $service): ?>
                <div class="glass-inner p-8 rounded-[2.5rem] border-l-8 border-orange-600 shadow-xl">
                    <h3 class="text-orange-500 font-black uppercase italic text-xl mb-4">
                        <?= htmlspecialchars($service['title'] ?? '') ?>
                    </h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        <?= nl2br(htmlspecialchars($service['desc'] ?? '')) ?>
                    </p>
                </div>
            <?php endforeach; ?>

            <a href="<?php echo $linkWA; ?>" target="_blank"
               class="inline-flex items-center gap-4 bg-orange-600 px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-sm orange-glow hover:scale-105 transition">
                Hubungi via WhatsApp
                <i class="fab fa-whatsapp text-2xl"></i>
            </a>

        </div>
    </div>

<?php else: ?>
    <!-- DEFAULT (KALAU BELUM ADA DATA ADMIN) -->
    <div class="text-center mb-20">
        <h1 class="text-5xl md:text-6xl font-black uppercase italic tracking-tighter">
            Layanan Kami
        </h1>
        <p class="text-gray-500 text-xs uppercase tracking-widest italic mt-6 max-w-3xl mx-auto">
            Solusi layanan terbaik untuk memenuhi kebutuhan material bangunan Anda secara cepat, praktis, dan terpercaya.
        </p>
    </div>

    <div class="flex justify-center">
        <div class="space-y-8">

            <div class="glass-inner p-8 rounded-[2.5rem] border-l-8 border-orange-600 shadow-xl">
                <h3 class="text-orange-500 font-black uppercase italic text-xl mb-4">
                    Pemesanan via WhatsApp
                </h3>
                <p class="text-gray-400 text-sm leading-relaxed">
                    Website ini dilengkapi dengan fitur pemesanan melalui WhatsApp yang memungkinkan pelanggan
                    melakukan pemesanan secara langsung dan praktis. Pelanggan dapat melakukan konfirmasi produk,
                    jumlah pesanan, serta pengiriman dengan lebih cepat dan mudah.
                </p>
            </div>

            <div class="glass-inner p-8 rounded-[2.5rem] border-l-8 border-orange-600 shadow-xl">
                <h3 class="text-orange-500 font-black uppercase italic text-xl mb-4">
                    Layanan Profesional & Terpercaya
                </h3>
                <p class="text-gray-400 text-sm leading-relaxed">
                    Pengembangan website ini diharapkan dapat membantu toko bangunan dalam memperluas jangkauan
                    pemasaran, meningkatkan profesionalisme layanan, serta memberikan kemudahan bagi pelanggan
                    dalam mengakses informasi produk.
                </p>
            </div>

            <div class="glass-inner p-8 rounded-[2.5rem] border-l-8 border-orange-600 shadow-xl">
                <h3 class="text-orange-500 font-black uppercase italic text-xl mb-4">
                    Adaptif di Era Digital
                </h3>
                <p class="text-gray-400 text-sm leading-relaxed">
                    Dengan adanya website profil dan katalog produk berbasis web ini, toko bangunan dapat
                    beradaptasi dengan perkembangan teknologi serta kebutuhan pelanggan di era digital.
                </p>
            </div>

            <a href="<?php echo $linkWA; ?>" target="_blank"
               class="inline-flex items-center gap-4 bg-orange-600 px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-sm orange-glow hover:scale-105 transition">
                Hubungi via WhatsApp
                <i class="fab fa-whatsapp text-2xl"></i>
            </a>

        </div>
    </div>
<?php endif; ?>

</section>
</body>
</html>
