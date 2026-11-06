    <?php
    require_once __DIR__ . '/_admin_session.php';

    if (empty($_SESSION['login_admin'])) {
        header("Location: login.php");
        exit;
    }

    include '../koneksi/koneksi.php';

    function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

    /* =====================
    SEARCH + FILTER
    ===================== */
    $search = isset($_GET['search'])
        ? mysqli_real_escape_string($koneksi, trim($_GET['search']))
        : '';

    $status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : '';
    $allowedStatus = ['pending','diproses','siap diambil','dikirim','selesai','batal'];

    /* =====================
    UPDATE STATUS + RESTOCK + LOCK FINAL
    ===================== */
    $flash = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
        $id = (int)($_POST['order_id'] ?? 0);
        $newStatus = strtolower(trim($_POST['status'] ?? ''));

        if ($id <= 0 || !in_array($newStatus, $allowedStatus, true)) {
            $flash = ['type'=>'error','msg'=>'Permintaan tidak valid.'];
        } else {

            mysqli_begin_transaction($koneksi);

            try {
                // ambil status lama (biar bisa lock)
                $stmt = mysqli_prepare($koneksi, "SELECT status FROM orders WHERE id=? LIMIT 1 FOR UPDATE");
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = $res ? mysqli_fetch_assoc($res) : null;
                mysqli_stmt_close($stmt);

                if (!$row) {
                    throw new Exception("Order tidak ditemukan.");
                }

                $oldStatus = strtolower(trim($row['status'] ?? 'pending'));

                // LOCK: kalau sudah batal / selesai, tidak boleh diubah lagi
                if (in_array($oldStatus, ['batal','selesai'], true)) {
                    throw new Exception("Order sudah $oldStatus. Status tidak bisa diubah lagi.");
                }

                // kalau status sama, gak ngapa-ngapain
                if ($newStatus === $oldStatus) {
                    mysqli_commit($koneksi);
                    $flash = ['type'=>'success','msg'=>'Status tidak berubah.'];
                } else {

                    // kalau jadi batal -> balikin stok
                    if ($newStatus === 'batal') {

                        // ambil item order
                        $stmtIt = mysqli_prepare($koneksi, "SELECT produk_id, qty FROM order_items WHERE order_id=?");
                        mysqli_stmt_bind_param($stmtIt, "i", $id);
                        mysqli_stmt_execute($stmtIt);
                        $resIt = mysqli_stmt_get_result($stmtIt);

                        while ($it = mysqli_fetch_assoc($resIt)) {
                            $pid = (int)$it['produk_id'];
                            $q   = (int)$it['qty'];

                            if ($pid <= 0 || $q <= 0) continue;

                            // inventory: tambah stok (kalau belum ada row, insert)
                            $sqlInv = "
                                INSERT INTO inventory (produk_id, stok, updated_at)
                                VALUES (?, ?, NOW())
                                ON DUPLICATE KEY UPDATE
                                    stok = COALESCE(stok,0) + VALUES(stok),
                                    updated_at = NOW()
                            ";
                            $stInv = mysqli_prepare($koneksi, $sqlInv);
                            mysqli_stmt_bind_param($stInv, "ii", $pid, $q);
                            if (!mysqli_stmt_execute($stInv)) {
                                throw new Exception("Gagal update inventory: " . mysqli_stmt_error($stInv));
                            }
                            mysqli_stmt_close($stInv);

                            // produk.qty ikut ditambah biar konsisten (karena dashboard fallback ke qty)
                            $stP = mysqli_prepare($koneksi, "UPDATE produk SET qty = COALESCE(qty,0) + ? WHERE id_produk=?");
                            mysqli_stmt_bind_param($stP, "ii", $q, $pid);
                            if (!mysqli_stmt_execute($stP)) {
                                throw new Exception("Gagal update produk: " . mysqli_stmt_error($stP));
                            }
                            mysqli_stmt_close($stP);
                        }

                        mysqli_stmt_close($stmtIt);
                    }

                    // update status order
                    $stmtUp = mysqli_prepare($koneksi, "UPDATE orders SET status=? WHERE id=?");
                    mysqli_stmt_bind_param($stmtUp, "si", $newStatus, $id);
                    if (!mysqli_stmt_execute($stmtUp)) {
                        throw new Exception("Gagal update status: " . mysqli_stmt_error($stmtUp));
                    }
                    mysqli_stmt_close($stmtUp);

                    mysqli_commit($koneksi);
                    $flash = ['type'=>'success','msg'=>'Status pesanan berhasil diperbarui.'];
                }

            } catch (Exception $e) {
                mysqli_rollback($koneksi);
                $flash = ['type'=>'error','msg'=>$e->getMessage()];
            }
        }
    }


    /* =====================
    WHERE
    ===================== */
    $where = "WHERE 1=1 ";
    if ($search !== '') {
        $like = "%{$search}%";
        $where .= " AND (CAST(o.id AS CHAR) LIKE '$like'
                    OR u.username LIKE '$like'
                    OR u.email LIKE '$like'
                    OR o.metode_pembayaran LIKE '$like')";
    }
    if ($status !== '' && in_array($status, $allowedStatus, true)) {
        $where .= " AND o.status = '$status' ";
    }

    /* =====================
    QUERY ORDERS
    ===================== */
    $sql = "
        SELECT o.id, o.user_id, o.total_harga, o.alamat, o.metode_pembayaran, o.status, o.created_at, o.no_resi,
            u.username, u.email
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id
        $where
        ORDER BY o.created_at DESC
    ";
    $query_orders = mysqli_query($koneksi, $sql);
    if (!$query_orders) die("Query orders gagal: " . mysqli_error($koneksi));

    /* =====================
    HELPER: ITEMS
    ===================== */
    function get_order_items($koneksi, $orderId){
        $items = [];
        $sql = "SELECT
                    oi.produk_id, oi.harga, oi.qty,
                    p.nama_stock AS produk_nama,
                    p.kode_stock AS produk_kode
                FROM order_items oi
                LEFT JOIN produk p ON p.id_produk = oi.produk_id
                WHERE oi.order_id = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "i", $orderId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while($r = mysqli_fetch_assoc($res)) $items[] = $r;
        mysqli_stmt_close($stmt);
        return $items;
    }

    $username = $_SESSION['username'] ?? 'Administrator';
    $current = basename($_SERVER['PHP_SELF']);
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
    <meta charset="UTF-8">
    <title>Pengelolaan Pesanan</title>
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

    /* dropdown dark */
    select option { background:#1A1D23; color:#fff; }
    </style>
    </head>

    <body class="font-sans">
    <div class="flex h-screen">

    <!-- SIDEBAR (sama persis users.php) -->
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
                class="flex items-center gap-3 p-3 rounded-xl <?= $current === 'page_edit.php' ? 'bg-orange-maju/10 text-orange-500 font-bold' : 'text-gray-400 hover:bg-gray-800' ?>">
                    <i data-lucide="file-text"></i> Kelola Page User
                </a>
            </div>

            <div class="space-y-2">
                <p class="text-[10px] text-gray-500 uppercase tracking-widest px-3">Pengelolaan Produk</p>

                <a href="pengelolaan_pesanan.php"
                class="flex items-center gap-3 p-3 rounded-xl <?= $current === 'pengelolaan_pesanan.php' ? 'bg-orange-maju/10 text-orange-500 font-bold' : 'text-gray-400 hover:bg-gray-800' ?>">
                    <i data-lucide="shopping-cart"></i> Pesanan
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
        <!-- SEARCH + FILTER -->
        <div class="flex items-center gap-3">
            <form method="GET" class="relative flex gap-3 items-center">
                <div class="relative">
                    <i data-lucide="search" class="absolute left-4 top-3 text-gray-500" size="18"></i>
                    <input type="text"
                        name="search"
                        value="<?= esc($search) ?>"
                        placeholder="Cari ID order / username / email / metode bayar..."
                        class="w-96 bg-[#1A1D23] border border-gray-800 rounded-full py-3 pl-12 pr-4 text-sm outline-none">
                </div>

                <select name="status"
                        class="bg-[#1A1D23] border border-gray-800 rounded-full py-3 px-4 text-sm outline-none text-gray-200">
                    <option value="">Semua Status</option>
                    <?php foreach($allowedStatus as $st): ?>
                        <option value="<?= $st ?>" <?= $status===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                    <?php endforeach; ?>
                </select>

                <button class="px-5 py-3 rounded-full bg-orange-maju/10 text-orange-400 border border-orange-500/20 hover:bg-orange-maju/20 text-sm flex items-center gap-2">
                    <i data-lucide="filter" size="16"></i> Terapkan
                </button>
            </form>

            <?php if (!empty($search) || !empty($status)): ?>
                <a href="pengelolaan_pesanan.php"
                class="px-4 py-3 rounded-full bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 text-sm flex items-center gap-2">
                    <i data-lucide="rotate-ccw" size="16"></i>
                </a>
            <?php endif; ?>
        </div>

        <!-- USER -->
        <div class="text-right">
            <p class="font-bold"><?= esc($username) ?></p>
            <p class="text-[10px] text-orange-500 uppercase">Administrator</p>
        </div>
    </header>

    <?php if ($flash): ?>
    <div class="mb-6 px-5 py-4 rounded-2xl border
        <?= $flash['type']==='success' ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-200' : 'border-red-500/20 bg-red-500/10 text-red-200' ?>">
        <?= esc($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <!-- TABLE ORDERS (kembar users.php) -->
    <div class="card-glass rounded-3xl overflow-hidden">
    <table class="w-full">
    <thead class="bg-black/30 text-gray-400 text-[10px] uppercase">
    <tr>
        <th class="p-6 text-left">Order</th>
        <th class="p-6 text-left">Pelanggan</th>
        <th class="p-6 text-left">Alamat / Pembayaran</th>
        <th class="p-6 text-left">Harga</th>
        <th class="p-6 text-left">Resi</th>
        <th class="p-6 text-left">Status</th>
        <th class="p-6 text-left">Aksi</th>
    </tr>
    </thead>

    <tbody class="divide-y divide-gray-800">
    <?php if (mysqli_num_rows($query_orders) == 0): ?>
    <tr>
        <td colspan="6" class="p-8 text-center text-gray-400">Tidak ada pesanan.</td>
    </tr>
    <?php else: ?>
    <?php while ($o = mysqli_fetch_assoc($query_orders)): ?>
    <?php
    $oid = (int)$o['id'];
    $u   = $o['username'] ?? '-';
    $em  = $o['email'] ?? '-';
    $al  = $o['alamat'] ?? '-';
    $mt  = $o['metode_pembayaran'] ?? '-';
    $tt  = (float)($o['total_harga'] ?? 0);
    $st  = strtolower($o['status'] ?? 'pending');
    ?>
    <tr class="hover:bg-white/[0.03] align-top">
        <td class="p-6 font-bold">#<?= $oid ?><div class="text-xs text-gray-500 font-normal mt-1"><?= esc($o['created_at'] ?? '') ?></div></td>
        <td class="p-6">
            <div class="font-bold"><?= esc($u) ?></div>
            <div class="text-gray-300 text-sm"><?= esc($em) ?></div>
        </td>
        <td class="p-6 text-sm text-gray-300">
            <div class="max-w-[320px] truncate" title="<?= esc($al) ?>"><?= esc($al) ?></div>
            <div class="text-xs text-gray-500 mt-1">Metode: <?= esc($mt) ?></div>
        </td>
        <td class="p-6 font-bold">Rp <?= number_format($tt,0,',','.') ?></td>
        <td class="p-6">
            <?php if (!empty($o['no_resi'])): ?>
                <div class="font-mono text-indigo-400 text-sm">
                    <?= esc($o['no_resi']) ?>
                </div>
            <?php else: ?>
                <span class="text-gray-500 text-xs italic">Belum ada</span>
            <?php endif; ?>
        </td>

        <td class="p-6">
            <span class="px-3 py-1 rounded-full text-xs border
            <?= $st==='pending'  ? 'bg-yellow-500/10 border-yellow-500/20 text-yellow-300' : '' ?>
            <?= $st==='diproses' ? 'bg-sky-500/10 border-sky-500/20 text-sky-300' : '' ?>
            <?= $st==='dikirim'  ? 'bg-indigo-500/10 border-indigo-500/20 text-indigo-300' : '' ?>
            <?= $st==='selesai'  ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-300' : '' ?>
            <?= $st==='siap diambil' ? 'bg-purple-500/10 border-purple-500/20 text-purple-300' : '' ?>
            <?= $st==='batal'    ? 'bg-red-500/10 border-red-500/20 text-red-300' : '' ?>
            ">
            <?= esc($st) ?>
            </span>
        </td>

        <td class="p-6">
            <div class="flex flex-col gap-2">
                <!-- DETAIL -->
                <details class="group">
                    <summary class="cursor-pointer text-orange-400 text-sm hover:text-orange-300 flex items-center gap-2">
                        <i data-lucide="eye" size="16"></i> Detail
                    </summary>
                    <div class="mt-3 p-4 rounded-2xl bg-black/20 border border-gray-800">
                        <?php $items = get_order_items($koneksi, $oid); ?>
                        <?php if (count($items) === 0): ?>
                        <div class="text-gray-400 text-sm">Item tidak ditemukan.</div>
                        <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach($items as $it): ?>
                            <?php
                                $pn = $it['produk_nama'] ?? ('Produk ID '.$it['produk_id']);
                                $pk = $it['produk_kode'] ?? ('ID '.$it['produk_id']);
                                $qty = (int)$it['qty'];
                                $hg  = (float)$it['harga'];
                                $sub = $qty * $hg;
                            ?>
                            <div class="flex justify-between gap-4">
                                <div>
                                <div class="font-bold text-sm"><?= esc($pn) ?></div>
                                <div class="text-xs text-gray-500"><?= esc($pk) ?> • Qty <?= $qty ?></div>
                                </div>
                                <div class="text-right">
                                <div class="text-xs text-gray-400">Rp <?= number_format($hg,0,',','.') ?></div>
                                <div class="font-bold text-sm">Rp <?= number_format($sub,0,',','.') ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </details>

                <?php $isLocked = in_array($st, ['batal','selesai'], true); ?>

                <!-- UPDATE STATUS -->
                <form method="post" class="flex items-center gap-2">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" value="<?= $oid ?>">

                    <select name="status"
                            <?= $isLocked ? 'disabled' : '' ?>
                            class="bg-[#1A1D23] border border-gray-800 rounded-full py-2 px-4 text-sm outline-none text-gray-200 w-full
                                <?= $isLocked ? 'opacity-50 cursor-not-allowed' : '' ?>">
                        <?php foreach($allowedStatus as $opt): ?>
                            <option value="<?= $opt ?>" <?= $st===$opt?'selected':'' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button <?= $isLocked ? 'disabled' : '' ?>
                            class="px-4 py-2 rounded-full bg-orange-maju/10 text-orange-400 border border-orange-500/20 hover:bg-orange-maju/20 text-sm
                                <?= $isLocked ? 'opacity-50 cursor-not-allowed' : '' ?>">
                        Update
                    </button>
                </form>

                <?php if ($isLocked): ?>
                <div class="text-[11px] text-gray-500 mt-1">
                    Order sudah <span class="text-orange-400 font-semibold"><?= esc($st) ?></span>, tidak bisa diubah lagi.
                </div>
                <?php endif; ?>

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
