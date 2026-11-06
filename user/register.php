<?php
session_start();
require_once '../koneksi/koneksi.php';
require_once 'auth.php';

$namaToko = "Toko Bangunan Maju Usaha Bangunan";
$brandShort = "Maju Usaha Bangunan";

$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($username === '' || $email === '' || $password === '' || $password2 === '') {
        $error = "Semua kolom wajib diisi.";
    } elseif ($password !== $password2) {
        $error = "Konfirmasi password tidak sama.";
    } else {
        // cek email/username sudah ada belum
        $stmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ss", $email, $username);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $exists = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($exists) {
            $error = "Email atau username sudah terdaftar.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt2 = mysqli_prepare($koneksi, "INSERT INTO users (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt2, "sss", $username, $email, $hash);
            $ok = mysqli_stmt_execute($stmt2);

            if ($ok) {
                $newUserId = mysqli_insert_id($koneksi);
                $_SESSION['pending_phone_user_id'] = $newUserId;

                mysqli_stmt_close($stmt2);

                header("Location: input_hp.php");
                exit;
            } else {
                mysqli_stmt_close($stmt2);
                $error = "Gagal daftar. Coba lagi.";
            }
        }
    }
}
?>

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - <?php echo $namaToko; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<nav class="fixed w-full z-50 glass py-5">
    <div class="max-w-7xl mx-auto px-8 flex items-center justify-between">
        <div class="flex items-center gap-12">
            <div class="flex items-center gap-3">
                <div class="bg-orange-600 p-2 rounded-xl"><i class="fas fa-hammer text-white"></i></div>
                <a href="../index.php" class="font-extrabold text-sm tracking-tight uppercase italic">
                    <?php echo $brandShort; ?>
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
            <span class="text-orange-500">Daftar</span>
            <span class="text-white/10">|</span>
            <a href="login.php" class="text-gray-400 hover:text-orange-500 transition">Login</a>
        </div>
    </div>
</nav>

<section class="pt-32 pb-20 px-8 max-w-7xl mx-auto min-h-screen flex items-center">
    <div class="w-full grid lg:grid-cols-12 gap-16 items-center">

        <div class="lg:col-span-6 order-2 lg:order-1">
            <div class="glass p-10 rounded-[3.5rem] shadow-2xl relative border border-white/10">
                <div class="space-y-3 mb-8">
                    <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-500 text-[10px] font-bold uppercase tracking-widest">
                        <span class="flex h-2 w-2 rounded-full bg-orange-500 animate-pulse"></span>
                        BUAT AKUN
                    </div>
                    <h1 class="text-4xl md:text-5xl font-black leading-none tracking-tighter uppercase italic">
                        DAFTAR <span class="orange-accent">USER</span>
                    </h1>
                    <p class="text-gray-400 text-xs italic tracking-widest uppercase">
                        Daftar untuk akses pemesanan lebih cepat.
                    </p>
                </div>

                <?php if ($error): ?>
                    <div class="glass-inner p-4 rounded-2xl border-l-8 border-red-500/80 mb-6">
                        <p class="text-red-300 text-xs font-bold uppercase tracking-widest italic">
                            <?php echo htmlspecialchars($error); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400 italic">Username</label>
                        <input type="text" name="username" required
                               class="w-full glass-inner px-5 py-4 rounded-2xl border border-white/10 focus:border-orange-500/60 outline-none text-sm"
                               placeholder="namauser">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400 italic">Email</label>
                        <input type="email" name="email" required
                               class="w-full glass-inner px-5 py-4 rounded-2xl border border-white/10 focus:border-orange-500/60 outline-none text-sm"
                               placeholder="contoh@email.com">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400 italic">Password</label>
                        <input type="password" name="password" required
                               class="w-full glass-inner px-5 py-4 rounded-2xl border border-white/10 focus:border-orange-500/60 outline-none text-sm"
                               placeholder="********">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400 italic">Konfirmasi Password</label>
                        <input type="password" name="password2" required
                               class="w-full glass-inner px-5 py-4 rounded-2xl border border-white/10 focus:border-orange-500/60 outline-none text-sm"
                               placeholder="********">
                    </div>

                    <button type="submit"
                            class="w-full bg-orange-600 px-10 py-5 rounded-2xl font-black inline-flex items-center justify-center gap-4 orange-glow hover:scale-[1.01] transition uppercase text-sm tracking-widest">
                        Daftar <i class="fas fa-user-plus text-lg"></i>
                    </button>

                    <div class="text-center pt-4 text-[10px] font-bold uppercase tracking-widest italic text-gray-500">
                        Sudah punya akun?
                        <a href="login.php" class="text-orange-500 hover:underline">Login di sini</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="lg:col-span-6 order-1 lg:order-2">
            <div class="glass p-10 rounded-[3.5rem] shadow-2xl border border-white/10">
                <h3 class="text-3xl font-extrabold italic uppercase tracking-tighter text-orange-500">
                    Membuka Fitur Belanja
                </h3>
                <p class="text-gray-400 text-sm leading-loose mt-4">
                    Dengan login, kamu bisa mengakses fitur belanja, menyimpan data akun, dan proses pemesanan jadi lebih praktis.
                </p>

                <div class="mt-8 grid gap-4">
                    <div class="flex items-center gap-4 text-xs font-bold uppercase tracking-wide">
                        <i class="fas fa-check-circle text-orange-500 text-lg"></i>
                        <span>Membuka fitur belanja</span>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-bold uppercase tracking-wide">
                        <i class="fas fa-check-circle text-orange-500 text-lg"></i>
                        <span>Menyimpan data akun (riwayat belanja dan riwayat pembelian)</span>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-bold uppercase tracking-wide">
                        <i class="fas fa-check-circle text-orange-500 text-lg"></i>
                        <span>Membuka fitur checkout</span>
                    </div>
                </div>


                <div class="mt-10 text-[10px] font-bold tracking-[1.2em] uppercase italic text-gray-700 text-center">
                    <?php echo $namaToko; ?>
                </div>
            </div>
        </div>

    </div>
</section>

</body>
</html>
