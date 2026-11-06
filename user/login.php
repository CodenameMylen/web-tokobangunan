<?php
require_once '../koneksi/koneksi.php';
require_once 'auth.php';

$namaToko = "Toko Bangunan Maju Usaha Bangunan";
$brandShort = "Maju Usaha Bangunan";

$error = null;

// ✅ NOTIF SUKSES SETELAH REGISTER
$success = null;
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success = "Akun berhasil dibuat! Silakan login.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Username dan password wajib diisi.";
    } else {
        // login pakai username
        $stmt = mysqli_prepare($koneksi, "SELECT id, username, email, password_hash FROM users WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
    
            $to = redirect_after_login();
            clear_redirect_after_login();
            header("Location: " . $to);
            exit;
        } else {
            $error = "Username atau password salah.";
        }
        mysqli_stmt_close($stmt);
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo $namaToko; ?></title>
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
            <a href="register.php" class="text-gray-400 hover:text-orange-500 transition">Daftar</a>
            <span class="text-white/10">|</span>
            <span class="text-orange-500">Login</span>
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
                        AKUN USER
                    </div>
                    <h1 class="text-4xl md:text-5xl font-black leading-none tracking-tighter uppercase italic">
                        LOGIN <span class="orange-accent">USER</span>
                    </h1>
                    <p class="text-gray-400 text-xs italic tracking-widest uppercase">
                        Masuk untuk melanjutkan pemesanan & akses fitur khusus.
                    </p>
                </div>

                <!-- ✅ NOTIF SUKSES DARI REGISTER -->
                <?php if ($success): ?>
                    <div class="glass-inner p-4 rounded-2xl border-l-8 border-green-500/80 mb-6">
                        <p class="text-green-300 text-xs font-bold uppercase tracking-widest italic">
                            <?php echo htmlspecialchars($success); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="glass-inner p-4 rounded-2xl border-l-8 border-red-500/80 mb-6">
                        <p class="text-red-300 text-xs font-bold uppercase tracking-widest italic">
                            <?php echo htmlspecialchars($error); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400 italic">
                            Username
                        </label>
                        <input type="text" 
                            name="username" 
                            required
                            class="w-full glass-inner px-5 py-4 rounded-2xl border border-white/10 focus:border-orange-500/60 outline-none text-sm"
                            placeholder="Masukkan username">
                    </div>


                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400 italic">Password</label>
                        <input type="password" name="password" required
                               class="w-full glass-inner px-5 py-4 rounded-2xl border border-white/10 focus:border-orange-500/60 outline-none text-sm"
                               placeholder="********">
                    </div>

                    <button type="submit"
                            class="w-full bg-orange-600 px-10 py-5 rounded-2xl font-black inline-flex items-center justify-center gap-4 orange-glow hover:scale-[1.01] transition uppercase text-sm tracking-widest">
                        Masuk <i class="fas fa-right-to-bracket text-lg"></i>
                    </button>

                    <div class="text-center pt-4 text-[10px] font-bold uppercase tracking-widest italic text-gray-500">
                        Belum punya akun?
                        <a href="register.php" class="text-orange-500 hover:underline">Daftar di sini</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="lg:col-span-6 order-1 lg:order-2 space-y-10">
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
