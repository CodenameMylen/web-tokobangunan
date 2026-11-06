<?php
// ================= INIT =================
require_once __DIR__ . '/_admin_session.php';
require_once __DIR__ . '/../koneksi/koneksi.php';

// ================= VALIDASI LOGIN =================
if (
    !isset($_SESSION['login_admin']) ||
    $_SESSION['login_admin'] !== true ||
    empty($_SESSION['admin_id'])
) {
    exit('Session admin tidak valid. Silakan login ulang.');
}

// ================= PARAMETER =================
$format = strtolower($_GET['format'] ?? 'xls');
if (!in_array($format, ['xls','excel','pdf'])) {
    $format = 'xls';
}

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to   = $_GET['to'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $from = date('Y-m-d', strtotime('-30 days'));
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $to = date('Y-m-d');
}

$fromDT = $from . " 00:00:00";
$toDT   = $to . " 23:59:59";

// ================= AMBIL DATA =================
$sql = "
    SELECT o.id, o.created_at, o.total_harga, o.metode_pembayaran,
           u.username, u.email
    FROM orders o
    LEFT JOIN users u ON u.id = o.user_id
    WHERE o.status = 'selesai'
      AND o.created_at BETWEEN ? AND ?
    ORDER BY o.created_at DESC
";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "ss", $fromDT, $toDT);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$orders = [];
$totalRevenue = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
    $totalRevenue += (float)$row['total_harga'];
}

mysqli_stmt_close($stmt);

// ================= FUNCTION AMBIL ITEM =================
function get_items($koneksi, $orderId) {
    $sql = "SELECT oi.qty, oi.harga, p.kode_stock, p.nama_stock
            FROM order_items oi
            LEFT JOIN produk p ON p.id_produk = oi.produk_id
            WHERE oi.order_id=?";
    $st = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($st, "i", $orderId);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $items = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $items[] = $r;
    }
    mysqli_stmt_close($st);
    return $items;
}

// ================= GENERATE HTML =================
ob_start();
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
body { font-family: Arial; font-size: 12px; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ccc; padding: 6px; }
th { background: #f2f2f2; }
.right { text-align: right; }
.small { font-size: 11px; color: #666; }
</style>
</head>
<body>

<h2>Laporan Penjualan</h2>
<p>
Periode: <b><?= htmlspecialchars($from) ?></b> s/d <b><?= htmlspecialchars($to) ?></b><br>
Total Order: <b><?= count($orders) ?></b><br>
Total Omzet: <b>Rp <?= number_format($totalRevenue,0,',','.') ?></b>
</p>

<table>
<tr>
<th>ID</th>
<th>Tanggal</th>
<th>Pelanggan</th>
<th>Pembayaran</th>
<th>Total</th>
<th>Detail</th>
</tr>

<?php if (empty($orders)): ?>
<tr><td colspan="6">Tidak ada data.</td></tr>
<?php else: ?>

<?php foreach ($orders as $o): ?>
<tr>
<td>#<?= $o['id'] ?></td>
<td><?= htmlspecialchars($o['created_at']) ?></td>
<td><?= htmlspecialchars($o['username'] ?? '-') ?><br><span class="small"><?= htmlspecialchars($o['email'] ?? '-') ?></span></td>
<td><?= htmlspecialchars($o['metode_pembayaran']) ?></td>
<td class="right">Rp <?= number_format($o['total_harga'],0,',','.') ?></td>
<td>

<?php
$items = get_items($koneksi, $o['id']);
if (empty($items)) {
    echo "<span class='small'>Tidak ada item</span>";
} else {
    echo "<table>";
    echo "<tr><th>Produk</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr>";
    foreach ($items as $it) {
        $sub = $it['qty'] * $it['harga'];
        echo "<tr>";
        echo "<td>".htmlspecialchars($it['nama_stock'])."</td>";
        echo "<td class='right'>".$it['qty']."</td>";
        echo "<td class='right'>Rp ".number_format($it['harga'],0,',','.')."</td>";
        echo "<td class='right'>Rp ".number_format($sub,0,',','.')."</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>

</td>
</tr>
<?php endforeach; ?>

<?php endif; ?>
</table>

</body>
</html>

<?php
$html = ob_get_clean();

// ================= OUTPUT =================
if ($format === 'xls' || $format === 'excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=laporan_{$from}_{$to}.xls");
    echo $html;
    exit;
}

if ($format === 'pdf') {
    require __DIR__ . '/../vendor/autoload.php';
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream("laporan_{$from}_{$to}.pdf", ["Attachment" => true]);
    exit;
}

// default preview
echo $html;
exit;