<?php
$brandShort = "Maju Usaha Bangunan";
$waNumber = "6281371392939";

// link WA untuk bantuan admin
$linkWA = "https://wa.me/$waNumber?text=Halo%20Maju%20Usaha%20Bangunan,%20saya%20butuh%20bantuan%20terkait%20pemesanan%20barang...";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cara Pemesanan Barang - <?php echo $brandShort; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- CSS GLOBAL -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="fixed w-full z-50 glass py-5">
    <div class="max-w-7xl mx-auto px-8 flex items-center justify-between">
        <!-- LEFT: Brand + Menu -->
        <div class="flex items-center gap-12">
            <div class="flex items-center gap-3">
                <div class="bg-orange-600 p-2 rounded-xl">
                    <i class="fas fa-hammer text-white"></i>
                </div>
                <span class="font-extrabold text-sm tracking-tight uppercase italic">
                    <?php echo $brandShort; ?>
                </span>
            </div>

            <div class="hidden lg:flex gap-10 text-[10px] font-bold uppercase tracking-widest">
                <a href="index.php" class="text-gray-400 hover:text-orange-500 transition">Beranda</a>
                <a href="produk.php" class="text-gray-400 hover:text-orange-500 transition">Katalog Produk</a>
                <a href="layanan.php" class="text-gray-400 hover:text-orange-500 transition">Layanan</a>
                <a href="cara-pemesanan.php" class="text-orange-500 transition">Cara Pemesanan</a>
            </div>
        </div>

        <!-- RIGHT: Lokasi + Tombol WA -->
        <div class="flex items-center gap-4">
            <div class="hidden sm:block text-[10px] font-bold text-gray-500 uppercase tracking-widest italic">
                Kabupaten Kampar, Riau
            </div>

            <!-- Tombol baru (WA) -->
            <a href="<?php echo $linkWA; ?>" target="_blank"
               class="hidden md:inline-flex items-center gap-2 bg-orange-600 text-black px-5 py-2 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:scale-105 transition whitespace-nowrap">
                Butuh Bantuan? Hubungi Admin
                <i class="fab fa-whatsapp text-sm"></i>
            </a>
        </div>
    </div>
</nav>

<!-- KONTEN -->
<section class="pt-[150px] pb-32 px-8 max-w-7xl mx-auto">

    <!-- HEADER -->
    <div class="text-center mb-24">
        <h1 class="text-5xl md:text-6xl font-black uppercase italic tracking-tighter">
            Cara Pemesanan Barang
        </h1>
        <p class="text-gray-500 text-xs uppercase tracking-widest italic mt-6 max-w-3xl mx-auto">
            Panduan mudah dan praktis untuk melakukan pemesanan material bangunan melalui website kami.
        </p>
    </div>

    <!-- STEP LIST -->
    <div class="space-y-10">

        <!-- STEP 1 -->
        <div class="glass p-10 rounded-[3rem] border border-white/10">
            <h3 class="text-orange-500 font-black uppercase italic text-xl mb-3">
                1. Akses Website Toko Bangunan
            </h3>
            <p class="text-gray-400 text-sm leading-relaxed">
                Pelanggan mengunjungi website toko bangunan untuk melihat profil toko serta
                katalog produk yang tersedia.
            </p>
        </div>

        <!-- STEP 2 -->
        <div class="glass p-10 rounded-[3rem] border border-white/10">
            <h3 class="text-orange-500 font-black uppercase italic text-xl mb-3">
                2. Pilih Produk yang Diinginkan
            </h3>
            <p class="text-gray-400 text-sm leading-relaxed">
                Pelanggan dapat menelusuri katalog produk dan memilih material bangunan
                yang dibutuhkan. Setiap produk dilengkapi dengan informasi nama barang,
                harga, dan deskripsi.
            </p>
        </div>

        <!-- STEP 3 (DIUBAH) -->
        <div class="glass p-10 rounded-[3rem] border border-white/10">
            <h3 class="text-orange-500 font-black uppercase italic text-xl mb-3">
                3. Butuh Bantuan? Hubungi Admin
            </h3>
            <p class="text-gray-400 text-sm leading-relaxed">
                Jika ada pertanyaan atau ingin memesan lebih cepat, pelanggan dapat menghubungi admin melalui
                <span class="text-orange-500 font-bold">WhatsApp</span>.
            </p>
        </div>

        <!-- STEP 4 -->
        <div class="glass p-10 rounded-[3rem] border border-white/10">
            <h3 class="text-orange-500 font-black uppercase italic text-xl mb-3">
                4. Konfirmasi Pemesanan melalui WhatsApp
            </h3>
            <p class="text-gray-400 text-sm leading-relaxed">
                Admin akan membantu konfirmasi detail produk, jumlah pesanan, serta alamat pengiriman.
            </p>
        </div>

        <!-- STEP 5 -->
        <div class="glass p-10 rounded-[3rem] border border-white/10">
            <h3 class="text-orange-500 font-black uppercase italic text-xl mb-3">
                5. Verifikasi oleh Admin Toko
            </h3>
            <p class="text-gray-400 text-sm leading-relaxed">
                Admin toko akan menerima pesan pemesanan, melakukan pengecekan stok,
                total harga, serta biaya pengiriman, kemudian mengonfirmasikannya kepada
                pelanggan.
            </p>
        </div>

        <!-- STEP 6 -->
        <div class="glass p-10 rounded-[3rem] border border-white/10">
            <h3 class="text-orange-500 font-black uppercase italic text-xl mb-3">
                6. Proses Pembayaran
            </h3>
            <p class="text-gray-400 text-sm leading-relaxed">
                Setelah pesanan dikonfirmasi, pelanggan melakukan pembayaran sesuai
                kesepakatan yang telah ditentukan oleh pihak toko.
            </p>
        </div>

        <!-- STEP 7 -->
        <div class="glass p-10 rounded-[3rem] border border-white/10">
            <h3 class="text-orange-500 font-black uppercase italic text-xl mb-3">
                7. Pengiriman Barang
            </h3>
            <p class="text-gray-400 text-sm leading-relaxed">
                Barang yang telah dibayar akan diproses dan dikirim ke alamat pelanggan
                sesuai dengan ketentuan yang berlaku.
            </p>
        </div>

    </div>

    <!-- CTA (DIUBAH: ke produk.php) -->
    <div class="mt-24 text-center">
        <a href="produk.php"
           class="inline-flex items-center gap-4 bg-orange-600 px-12 py-6 rounded-2xl font-black uppercase tracking-widest text-sm orange-glow hover:scale-105 transition">
            Lihat Katalog Produk
            <i class="fas fa-arrow-right text-lg"></i>
        </a>
    </div>

</section>

</body>
</html>
