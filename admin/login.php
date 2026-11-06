<?php
// konsisten dengan semua halaman admin
session_name('ADMINSESSID');
session_start();

$title = 'Login Admin';

// kalau auth_style.php berisi HTML template, cukup sekali
include_once __DIR__ . '/auth_style.php';

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>

<div class="card-glass rounded-3xl p-10 shadow-xl w-[560px]">
    <!-- LOGO -->
    <div class="flex items-center gap-3 mb-6">
        <div class="bg-orange-maju p-2 rounded-xl">
            <i data-lucide="hammer"></i>
        </div>
        <h1 class="font-bold text-xl italic">
            MAJU <span class="accent-orange">USAHA</span>
        </h1>
    </div>

    <h2 class="text-xl font-bold mb-6">Login Admin</h2>

    <!-- ERROR LOGIN -->
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="mb-4 bg-red-500/10 border border-red-500 text-red-400 text-sm px-4 py-3 rounded-xl">
            <?= esc($_SESSION['error']); ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- FORM LOGIN -->
    <form method="post" action="proses/proses_login.php" class="space-y-4">
        <div>
            <label class="text-xs text-gray-400">Username</label>
            <input type="text" name="username" required autofocus
                class="w-full bg-[#1A1D23] border border-gray-800 rounded-xl px-4 py-3 outline-none">
        </div>

        <div>
            <label class="text-xs text-gray-400">Password</label>
            <input type="password" name="password" required
                class="w-full bg-[#1A1D23] border border-gray-800 rounded-xl px-4 py-3 outline-none">
        </div>

        <button class="w-full bg-orange-maju py-3 rounded-xl font-bold mt-4">
            Login
        </button>
    </form>
</div>