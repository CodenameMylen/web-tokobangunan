<?php
include 'koneksi/koneksi.php';
require_once 'user/auth.php';

// --- KONFIGURASI DATA TOKO ---
$namaToko = "Toko Bangunan Maju Usaha Bangunan";
$brandShort = "Maju Usaha Bangunan";

$waNumber = "6281371392939";
$waDisplay = "0813-7139-2939";

$alamat = "Jalan Kubang Raya, Simpang Kualu, Sukakarya, Kubang Jaya, Kec. Tambang, Kabupaten Kampar, Riau 28293";

// PATH GAMBAR
$fotoProfil = "assets/profil/home 1.jpg";
$fotoPengiriman = "assets/profil/home 2.jpg";
$fotoStok = "assets/profil/home 3.jpg";

$linkWA = "https://wa.me/$waNumber?text=Halo%20Maju%20Usaha%20Bangunan,%20saya%20ingin%20tanya%20stok%20material...";


// ===============================
// ✅ LINK SOSMED RESMI (UPDATE)
// ===============================
$igUsername = "tokobangunan_kubang";
$linkIG = "https://www.instagram.com/tokobangunan_kubang?igsh=MXVmc2d1M3I4bW5tNw==";

$linkFB = "https://www.facebook.com/profile.php?id=100074960378640";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $namaToko; ?> - Solusi Bangunan Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                    <a href="index.php" class="text-gray-400 hover:text-orange-500 transition">Beranda</a>
                    <a href="produk.php" class="text-gray-400 hover:text-orange-500 transition">Katalog Produk</a>
                    <a href="layanan.php" class="text-gray-400 hover:text-orange-500 transition">Layanan</a>
                    <a href="cara-pemesanan.php" class="text-gray-400 hover:text-orange-500 transition">Cara Pemesanan</a>
                </div>
            </div>

            <!-- ✅ BAGIAN INI SUDAH DISESUAIKAN DENGAN user/auth.php -->
            <div class="hidden sm:flex items-center gap-4 text-[10px] font-bold uppercase tracking-widest italic">
                <span class="text-gray-500">Kabupaten Kampar, Riau</span>
                <span class="text-white/10">|</span>

                <?php if (is_logged_in()): ?>

                    <span class="text-orange-500">
                        Halo, <?php echo htmlspecialchars(current_username()); ?>
                    </span>

                    <span class="text-white/10">|</span>

                    <!-- Tombol Riwayat -->
                    <a href="user/riwayat.php"
                    class="text-gray-400 hover:text-orange-500 transition">
                        Riwayat
                    </a>

                    <span class="text-white/10">|</span>

                    <!-- Logout -->
                    <a href="user/logout.php"
                    class="text-gray-400 hover:text-orange-500 transition">
                        Logout
                    </a>

                <?php else: ?>

                    <a href="user/login.php"
                    class="text-gray-400 hover:text-orange-500 transition">
                        Login
                    </a>

                    <span class="text-white/10">|</span>

                    <a href="user/register.php"
                    class="text-gray-400 hover:text-orange-500 transition">
                        Daftar
                    </a>

                <?php endif; ?>

            </div>
        </div>
    </nav>

    <section id="beranda" class="pt-32 pb-20 px-8 max-w-7xl mx-auto flex items-center min-h-screen">
        <div class="grid lg:grid-cols-12 gap-16 items-center w-full">

            <div class="lg:col-span-7 order-2 lg:order-1">
                <div class="glass p-8 rounded-[3.5rem] shadow-2xl relative border border-white/10">
                    <div class="relative w-full h-[600px] rounded-[2.5rem] overflow-hidden mb-8 group border border-white/5">
                        <img src="<?php echo $fotoProfil; ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" alt="Toko Maju Usaha">
                        <div class="absolute inset-0 bg-gradient-to-t from-[#0b0f1a]/80 via-transparent to-transparent"></div>
                    </div>

                    <div class="text-center space-y-4 text-orange-500">
                        <h3 class="text-3xl font-extrabold italic uppercase tracking-tighter">Profil Toko Kami</h3>
                        <p class="text-gray-400 text-xs px-10 italic leading-relaxed uppercase tracking-widest">
                            "Melayani sepenuh hati dengan material berkualitas untuk bangunan impian Anda."
                        </p>

                        <div class="flex justify-center gap-10 pt-8 mt-6 border-t border-white/5 text-center">
                            <div><span class="block text-3xl font-black uppercase">100%</span><span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Terpercaya</span></div>
                            <div class="w-px h-10 bg-white/10 self-center"></div>
                            <div><span class="block text-3xl font-black uppercase">Fast</span><span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Respon</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 space-y-10 order-1 lg:order-2">
                <div class="space-y-6">
                    <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-500 text-[10px] font-bold uppercase tracking-widest">
                        <span class="flex h-2 w-2 rounded-full bg-orange-500 animate-pulse"></span>
                        🏅 MATERIAL BERKUALITAS & TERPERCAYA
                    </div>
                    <h1 class="text-5xl md:text-7xl font-black leading-none tracking-tighter uppercase italic">
                        Toko Bangunan<br> <span class="orange-accent">Maju Usaha Bangunan</span>
                    </h1>
                    <p class="text-gray-400 text-sm md:text-base leading-loose mb-10">
                        Kami melayani pembelian berbagai kebutuhan material bangunan Anda secara langsung maupun online dengan pengiriman cepat ke lokasi tujuan.
                    </p>

                    <div class="glass-inner p-8 rounded-[2.5rem] border-l-8 border-orange-600 space-y-5 shadow-xl">
                        <h3 class="text-orange-500 font-bold uppercase text-xs tracking-[0.2em] italic mb-2 text-left">Keunggulan Kami:</h3>
                        <div class="grid gap-4">
                            <div class="flex items-center gap-4 text-xs font-bold uppercase tracking-wide">
                                <i class="fas fa-check-circle text-orange-500 text-lg"></i>
                                <span>Harga Bersahabat & Terpercaya</span>
                            </div>
                            <div class="flex items-center gap-4 text-xs font-bold uppercase tracking-wide">
                                <i class="fas fa-check-circle text-orange-500 text-lg"></i>
                                <span>Stok Material Selalu Ready</span>
                            </div>
                            <div class="flex items-center gap-4 text-xs font-bold uppercase tracking-wide">
                                <i class="fas fa-check-circle text-orange-500 text-lg"></i>
                                <span>Pengiriman Cepat</span>
                            </div>
                            <div class="flex items-center gap-4 text-xs font-bold uppercase tracking-wide">
                                <i class="fas fa-check-circle text-orange-500 text-lg"></i>
                                <span>Eceran hingga Grosir</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <a href="<?php echo $linkWA; ?>" target="_blank" class="bg-orange-600 px-10 py-5 rounded-2xl font-black inline-flex items-center gap-4 orange-glow hover:scale-105 transition uppercase text-sm tracking-widest">
                        Pesan Sekarang <i class="fab fa-whatsapp text-2xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="py-24 px-8 bg-black/20 border-t border-white/5 text-center">
        <h2 class="text-5xl font-black uppercase italic tracking-tighter mb-20 tracking-widest uppercase">FASILITAS KAMI</h2>

        <div class="max-w-7xl mx-auto grid md:grid-cols-2 gap-10 text-left">
            <div class="glass p-4 rounded-[3.5rem] group border border-white/5 hover:border-orange-500 transition duration-500 relative">
                <div class="h-[500px] w-full rounded-[3rem] overflow-hidden relative">
                    <img src="<?php echo $fotoPengiriman; ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#0b0f1a] to-transparent"></div>
                </div>
                <div class="absolute bottom-10 left-10">
                    <h4 class="text-3xl font-black uppercase italic text-orange-500">Pengiriman Cepat</h4>
                    <p class="text-white text-[10px] font-bold uppercase tracking-[0.3em] mt-2 italic">Siap Antar Ke Lokasi </p>
                </div>
            </div>

            <div class="glass p-4 rounded-[3.5rem] group border border-white/5 hover:border-orange-500 transition duration-500 relative">
                <div class="h-[500px] w-full rounded-[3rem] overflow-hidden relative">
                    <img src="<?php echo $fotoStok; ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#0b0f1a] to-transparent"></div>
                </div>
                <div class="absolute bottom-10 left-10">
                    <h4 class="text-3xl font-black uppercase italic text-orange-500">Stok Gudang Luas</h4>
                    <p class="text-white text-[10px] font-bold uppercase tracking-[0.3em] mt-2 italic">Material Bangunan Terlengkap</p>
                </div>
            </div>
        </div>
    </section>

    <section id="lokasi" class="py-24 px-8 max-w-7xl mx-auto border-t border-white/5">
        <div class="grid md:grid-cols-2 gap-20 items-start">

            <div class="rounded-[4rem] overflow-hidden glass h-[550px] relative border border-white/10 grayscale brightness-75 hover:grayscale-0 transition duration-700 shadow-2xl order-2 md:order-1">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15958.836715935402!2d101.35209560394287!3d0.42379588881074365!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31d5a7dbb6f96091%3A0x410fa4633ffd420!2sMaju%20Usaha%20Bangunan!5e0!3m2!1sen!2sid!4v1765596478631!5m2!1sen!2sid" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>

            <div class="space-y-12 order-1 md:order-2">
                <div class="space-y-4">
                    <h2 class="text-5xl font-black uppercase italic tracking-tighter">KONTAK KAMI</h2>
                    <p class="text-gray-500 text-xs italic tracking-widest uppercase italic">Hubungi tim kami melalui sosial media kami di bawah ini.</p>
                </div>

                <div class="space-y-6">
                    <div class="social-card">
                        <div class="icon-box orange-glow"><i class="fab fa-whatsapp"></i></div>
                        <div>
                            <p class="text-white font-extrabold uppercase text-[10px] leading-none mb-1 tracking-widest uppercase">WhatsApp Official</p>
                            <a href="<?php echo $linkWA; ?>" target="_blank" class="text-orange-500 hover:underline text-[10px] font-bold italic uppercase tracking-widest"><?php echo $waDisplay; ?></a>
                        </div>
                    </div>

                    <div class="social-card">
                        <div class="icon-box orange-glow"><i class="fab fa-instagram"></i></div>
                        <div>
                            <p class="text-white font-extrabold uppercase text-[10px] leading-none mb-1 tracking-widest uppercase">Instagram Kami</p>

                            <!-- ✅ IG SUDAH BISA DI KLIK (LINK ASLI) -->
                            <a href="<?php echo $linkIG; ?>" target="_blank" rel="noopener noreferrer"
                               class="text-orange-500 hover:underline text-[10px] font-bold italic uppercase tracking-widest uppercase">
                                @<?php echo $igUsername; ?>
                            </a>
                        </div>
                    </div>

                    <div class="social-card">
                        <div class="icon-box orange-glow"><i class="fab fa-facebook-f"></i></div>
                        <div>
                            <p class="text-white font-extrabold uppercase text-[10px] leading-none mb-1 tracking-widest uppercase">Facebook Kami</p>

                            <!-- ✅ FB SUDAH BISA DI KLIK (LINK ASLI) -->
                            <a href="<?php echo $linkFB; ?>" target="_blank" rel="noopener noreferrer"
                               class="text-orange-500 hover:underline text-[10px] font-bold italic uppercase tracking-widest uppercase">
                                Maju Usaha Bangunan
                            </a>
                        </div>
                    </div>

                    <div class="flex items-start gap-5 text-gray-400 text-xs border-t border-white/5 pt-10 mt-10">
                        <i class="fas fa-map-marker-alt text-orange-500 text-2xl mt-1"></i>
                        <p class="leading-relaxed uppercase tracking-wider font-semibold italic"><?php echo $alamat; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-32 text-center text-gray-700 text-[10px] font-bold tracking-[1.5em] uppercase italic">
            <?php echo $namaToko; ?>
        </div>
    </section>

</body>
</html>