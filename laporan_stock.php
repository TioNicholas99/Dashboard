<?php
include 'config.php';

// Eksekusi query dengan pengecekan error
$query = "
    SELECT su.usage_date, i.name, su.total_used, i.unit 
    FROM stock_usage su
    JOIN items i ON su.item_id = i.id
    ORDER BY su.usage_date DESC
";

$result = $conn->query($query);

if (!$result) {
    die("Error dalam query: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penggunaan Stok</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .content { margin-left: 260px; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background-color: #f4f4f4; }
        .filter-box { margin-bottom: 20px; }
        a { text-decoration: none; color: #007bff; }
        a:hover { text-decoration: underline; }
        body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
}

/* Sidebar */
.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    background: #007bff;
    color: white;
    padding-top: 20px;
    overflow-y: auto;
}

.sidebar h2 {
    text-align: center;
    margin-bottom: 20px;
}

.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 10px 20px;
    margin: 5px 0;
}

.sidebar a:hover {
    background: #0056b3;
}

/* Konten utama agar tidak tertimpa sidebar */
.content {
    margin-left: 260px; /* Memberikan ruang agar konten tidak tertutup sidebar */
    padding: 20px;
}

/* Form */
.container {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    margin-top: 20px;
}

form {
    width: 100%;
    max-width: 400px;
    padding: 20px;
    border: 1px solid #ccc;
    border-radius: 8px;
    background: #f9f9f9;
}

label, select, input {
    display: block;
    margin-bottom: 10px;
    width: 100%;
    padding: 8px;
}

button {
    background: #007bff;
    color: white;
    border: none;
    padding: 10px;
    cursor: pointer;
    width: 100%;
}

button:hover {
    background: #0056b3;
}

/* Tabel */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
}

th {
    background: #f4f4f4;
}

/* Responsif */
@media (max-width: 768px) {
    .sidebar {
        width: 200px;
    }

    .content {
        margin-left: 210px;
    }

    .container {
        flex-direction: column;
    }
}

@media (max-width: 576px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }

    .content {
        margin-left: 0;
    }

    .container {
        flex-direction: column;
    }
}

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
    <h2>Laporan Penggunaan Stok</h2>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Nama Barang</th>
                <th>Jumlah Dipakai</th>
                <th>Satuan</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['usage_date']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= number_format($row['total_used'], 2) ?></td>
                    <td><?= htmlspecialchars($row['unit']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
