<?php
session_start();
require_once '../koneksi/koneksi.php';

$namaToko = "Toko Bangunan Maju Usaha Bangunan";
$brandShort = "Maju Usaha Bangunan";

$userId = $_SESSION['pending_phone_user_id'] ?? null;
if (!$userId) {
    // kalau user langsung akses tanpa dari register
    header("Location: register.php");
    exit;
}

$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $phone = trim($_POST['phone'] ?? '');

    // validasi sederhana (boleh kamu sesuaikan)
    // terima +62 atau angka, minimal 9 digit
    $clean = preg_replace('/[^0-9+]/', '', $phone);

    if ($clean === '') {
        $error = "Nomor HP wajib diisi.";
    } elseif (!preg_match('/^\+?[0-9]{9,15}$/', $clean)) {
        $error = "Format nomor HP tidak valid. Contoh: 08xxxxxxxxxx atau +628xxxxxxxxxx";
    } else {

    // 1) CEK NOMOR HP SUDAH DIPAKAI ATAU BELUM
    $cekStmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE phone = ? LIMIT 1");
    mysqli_stmt_bind_param($cekStmt, "s", $clean);
    mysqli_stmt_execute($cekStmt);
    $cekRes = mysqli_stmt_get_result($cekStmt);
    $phoneExists = mysqli_fetch_assoc($cekRes);
    mysqli_stmt_close($cekStmt);

    // Kalau nomor sudah ada dan itu bukan milik user ini
    if ($phoneExists && (int)$phoneExists['id'] !== (int)$userId) {
        $error = "Nomor HP sudah digunakan.";
    } else {

        // 2) UPDATE NOMOR HP
        $stmt = mysqli_prepare($koneksi, "UPDATE users SET phone = ? WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "si", $clean, $userId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($ok) {
            // hapus session pending
            unset($_SESSION['pending_phone_user_id']);

            // setelah isi hp, arahkan ke login
            header("Location: login.php?registered=1&phone=1");
            exit;
        } else {
            $error = "Gagal menyimpan nomor HP. Coba lagi.";
        }
    }
}

}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Input Nomor HP - <?php echo $namaToko; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<nav class="fixed w-full z-50 glass py-5">
  <div class="max-w-7xl mx-auto px-8 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="bg-orange-600 p-2 rounded-xl"><i class="fas fa-hammer text-white"></i></div>
      <a href="../index.php" class="font-extrabold text-sm tracking-tight uppercase italic">
        <?php echo $brandShort; ?>
      </a>
    </div>
    <div class="hidden sm:flex items-center gap-4 text-[10px] font-bold uppercase tracking-widest italic">
      <span class="text-orange-500">Input HP</span>
    </div>
  </div>
</nav>

<section class="pt-32 pb-20 px-8 max-w-3xl mx-auto min-h-screen flex items-center">
  <div class="w-full">
    <div class="glass p-10 rounded-[3.5rem] shadow-2xl relative border border-white/10">
      <div class="space-y-3 mb-8">
        <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-500 text-[10px] font-bold uppercase tracking-widest">
          <span class="flex h-2 w-2 rounded-full bg-orange-500 animate-pulse"></span>
          LANGKAH TERAKHIR
        </div>
        <h1 class="text-3xl md:text-4xl font-black leading-none tracking-tighter uppercase italic">
          INPUT  <span class="orange-accent"> NOMOR HP</span>
        </h1>
        <p class="text-gray-400 text-xs italic tracking-widest uppercase">
          Nomor HP dipakai untuk data akun & proses pemesanan.
        </p>
      </div>

      <?php if ($error): ?>
        <div class="glass-inner p-4 rounded-2xl border-l-8 border-red-500/80 mb-6">
          <p class="text-red-300 text-xs font-bold uppercase tracking-widest italic">
            <?php echo htmlspecialchars($error); ?>
          </p>
        </div>
      <?php endif; ?>

        <form id="formHp" method="POST" class="space-y-5">
            <div class="space-y-2">
            <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400 italic">Nomor HP</label>
            <input type="text" name="phone" required
                    class="w-full glass-inner px-5 py-4 rounded-2xl border border-white/10 focus:border-orange-500/60 outline-none text-sm"
                    placeholder="Contoh : 081234567890">
            </div>

            <button type="submit"
                class="w-full bg-orange-600 px-10 py-5 rounded-2xl font-black inline-flex items-center justify-center gap-4 orange-glow hover:scale-[1.01] transition uppercase text-sm tracking-widest">
            Daftar <i class="fas fa-user-plus text-lg"></i>
            </button>


            <div class="text-center pt-4 text-[10px] font-bold uppercase tracking-widest italic text-gray-500">
            Setelah disimpan, kamu akan diarahkan ke halaman login.
            </div>
        </form>
    </div>
  </div>
</section>
<div id="loaderOverlay" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/60 backdrop-blur-sm">
  <div class="glass-inner rounded-3xl px-10 py-8 border border-white/10 text-center">
    <div class="mx-auto h-12 w-12 rounded-full border-4 border-white/20 border-t-orange-500 animate-spin"></div>
    <p class="mt-5 text-xs font-bold uppercase tracking-widest italic text-gray-200">
      Menyimpan data...
    </p>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formHp');
  const overlay = document.getElementById('loaderOverlay');
  const btn = form ? form.querySelector('button[type="submit"]') : null;

  if (!form || !overlay) return;

  // pastikan overlay hidden saat load
  overlay.classList.add('hidden');
  overlay.classList.remove('flex');

  form.addEventListener('submit', (e) => {
    e.preventDefault(); // tahan submit dulu biar loader sempat tampil

    overlay.classList.remove('hidden');
    overlay.classList.add('flex');

    if (btn) {
      btn.disabled = true;
      btn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    // kasih waktu render
    setTimeout(() => {
      form.submit(); // submit beneran
    }, 150);
  });
});
</script>



</body>
</html>
