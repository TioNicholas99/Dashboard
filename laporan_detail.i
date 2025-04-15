<?php
include 'config.php';

$transaction_id = $_GET['id'] ?? 0;

// Ambil detail transaksi
$transaction = $conn->query("SELECT * FROM transactions WHERE id = $transaction_id")->fetch_assoc();

// Ambil item yang digunakan dalam transaksi
$items = $conn->query("
    SELECT i.name, ti.quantity_used, i.unit 
    FROM transaction_items ti
    JOIN items i ON ti.item_id = i.id
    WHERE ti.transaction_id = $transaction_id
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Transaksi #<?= $transaction_id ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .content { margin-left: 260px; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background-color: #f4f4f4; }
        a { display: inline-block; margin-top: 20px; padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        a:hover { background: #0056b3; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>Kasir Barbershop</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="transaksi.php">Transaksi</a>
    <a href="riwayat_transaksi.php">Riwayat Transaksi</a>
    <a href="input_stock.php">Input</a>
    <a href="stock.php">Stok Barang</a>
    <a href="laporan.php">Laporan</a>
    <a href="laporan_stock.php">Laporan Stok</a>
    <a href="laporan_shift.php">Laporan Shift</a>
    <a href="export_pdf.php">Export PDF</a>
    <a href="exportlaporan.php">Export Laporan</a>
    <a href="shift.php">Shift Kerja</a>
    <a href="proses_transaksi.php">Proses Transaksi</a>
</div>

<!-- Konten utama -->
<div class="content">
    <h2>Detail Transaksi #<?= $transaction_id ?></h2>

    <p><strong>Total Harga:</strong> Rp <?= number_format($transaction['total_price'], 0, ',', '.') ?></p>
    <p><strong>Waktu:</strong> <?= $transaction['created_at'] ?></p>

    <h3>Barang yang Digunakan</h3>
    <table>
        <thead>
            <tr>
                <th>Nama Barang</th>
                <th>Jumlah Digunakan</th>
                <th>Satuan</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $items->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= number_format($row['quantity_used'], 2) ?></td>
                    <td><?= htmlspecialchars($row['unit']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <a href="laporan.php">Kembali ke Laporan</a>
</div>

</body>
</html>
