<?php
include 'config.php';

// Set header untuk file Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Transaksi_" . date("Ymd") . ".xls");

// Ambil data transaksi
$filter_date = $_GET['date'] ?? date("Y-m-d");
$transactions = $conn->query("
    SELECT t.id, t.total_price, t.created_at, s.cashier_name
    FROM transactions t
    LEFT JOIN shifts s ON DATE(t.created_at) = DATE(s.start_time)
    WHERE DATE(t.created_at) = '$filter_date'
    ORDER BY t.created_at DESC
");

// Ambil total transaksi harian
$total_day = $conn->query("
    SELECT SUM(total_price) AS total FROM transactions 
    WHERE DATE(created_at) = '$filter_date'
")->fetch_assoc()['total'] ?? 0;
?>
<a href="export_excel.php?date=<?= $filter_date ?>">Download Excel</a>


<table border="1">
    <tr>
        <th colspan="4">Laporan Transaksi - <?= $filter_date ?></th>
    </tr>
    <tr>
        <th>ID Transaksi</th>
        <th>Kasir</th>
        <th>Total Harga</th>
        <th>Waktu</th>
    </tr>
    <?php while ($row = $transactions->fetch_assoc()): ?>
        <tr>
            <td>#<?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['cashier_name'] ?? 'Tidak Diketahui') ?></td>
            <td>Rp <?= number_format($row['total_price'], 0, ',', '.') ?></td>
            <td><?= $row['created_at'] ?></td>
        </tr>
    <?php endwhile; ?>
    <tr>
        <th colspan="2">Total Pendapatan</th>
        <th colspan="2">Rp <?= number_format($total_day, 0, ',', '.') ?></th>
    </tr>
</table>
