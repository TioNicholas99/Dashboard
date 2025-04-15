<?php
include 'config.php';

// Ambil shift aktif terakhir
$active_shift = $conn->query("SELECT id FROM shift WHERE status = 'Aktif' ORDER BY start_time DESC LIMIT 1");
if (!$active_shift) {
    die("Query error: " . $conn->error);
}
$shift_data = $active_shift->fetch_assoc();
$shift_id = $shift_data['id'] ?? null;

// Ambil daftar shift
$shifts = $conn->query("  
    SELECT s.id, s.cashier_id, s.cashier_name, s.start_time, s.end_time, s.status
    FROM shift s  
    ORDER BY s.start_time DESC  
");
if (!$shifts) {
    die("Query error: " . $conn->error);
}

// Ambil data transaksi berdasarkan shift
$transactions = $conn->query("  
    SELECT shift_id, COUNT(id) AS total_transaksi,   
           SUM(total_price) AS total_pendapatan,   
           SUM(customer_count) AS total_pelanggan  
    FROM transactions  
    GROUP BY shift_id  
");
if (!$transactions) {
    die("Query error: " . $conn->error);
}

// Konversi transaksi ke array untuk akses cepat
$transaction_data = [];
while ($row = $transactions->fetch_assoc()) {
    $transaction_data[$row['shift_id']] = $row;
}

// Filter berdasarkan tanggal jika ada
$date_filter = '';
$start_date = '';
$end_date = '';

if (isset($_GET['filter']) && $_GET['filter'] == 'date') {
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    
    if (!empty($start_date) && !empty($end_date)) {
        $date_filter = " WHERE s.start_time >= '$start_date 00:00:00' AND s.start_time <= '$end_date 23:59:59'";
        
        // Requery dengan filter tanggal
        $shifts = $conn->query("  
            SELECT s.id, s.cashier_id, s.cashier_name, s.start_time, s.end_time, s.status
            FROM shift s  
            $date_filter
            ORDER BY s.start_time DESC  
        ");
        
        if (!$shifts) {
            die("Query error: " . $conn->error);
        }
    }
}

// Hitung total pendapatan dan transaksi untuk semua shift yang ditampilkan
$total_all_pendapatan = 0;
$total_all_transaksi = 0;
$total_all_pelanggan = 0;

$temp_shifts = [];
while ($row = $shifts->fetch_assoc()) {
    $shift_id = $row['id'];
    if (isset($transaction_data[$shift_id])) {
        $total_all_pendapatan += $transaction_data[$shift_id]['total_pendapatan'] ?? 0;
        $total_all_transaksi += $transaction_data[$shift_id]['total_transaksi'] ?? 0;
        $total_all_pelanggan += $transaction_data[$shift_id]['total_pelanggan'] ?? 0;
    }
    $temp_shifts[] = $row;
}

// Reset pointer shifts untuk iterasi di HTML
$shifts = $temp_shifts;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Shift</title>
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --success: #2ecc71;
            --danger: #e74c3c;
            --gray-light: #f8f9fa;
            --gray: #e9ecef;
            --gray-dark: #343a40;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            padding: 0;
            margin: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Sidebar style */
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            background: #007bff;
            color: white;
            padding-top: 20px;
            z-index: 100;
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
        
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        
        header {
            background-color: white;
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .page-title {
            color: var(--gray-dark);
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .breadcrumb {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 16px 20px;
            background-color: white;
            border-bottom: 1px solid var(--gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .filter-form {
            margin-bottom: 20px;
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }
        
        .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--gray);
            border-radius: 4px;
            font-family: inherit;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        th {
            background-color: var(--gray-light);
            color: var(--gray-dark);
            font-weight: 600;
            text-align: left;
            padding: 12px 15px;
            border-bottom: 2px solid var(--gray);
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--gray);
            vertical-align: middle;
        }
        
        tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .summary-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .summary-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--gray-dark);
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .summary-item {
            text-align: center;
            padding: 15px;
            border-radius: 6px;
            background-color: var(--gray-light);
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .summary-label {
            font-size: 14px;
            color: #6c757d;
        }
        
        .status-active {
            color: var(--success);
            font-weight: 600;
            background-color: rgba(46, 204, 113, 0.1);
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .status-closed {
            color: var(--danger);
            font-weight: 600;
            background-color: rgba(231, 76, 60, 0.1);
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: var(--primary);
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: background-color 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-danger {
            background-color: var(--danger);
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .text-right {
            text-align: right;
        }
        
        .money {
            font-family: 'Consolas', monospace;
            font-weight: 600;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }
        
        footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-size: 14px;
            margin-top: 40px;
            margin-left: 250px;
        }
        
        .export-btn {
            margin-left: 10px;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 200px;
            }
            .content {
                margin-left: 200px;
            }
            footer {
                margin-left: 200px;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                display: none;
            }
            .content {
                margin-left: 0;
            }
            .container {
                padding: 10px;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .btn {
                margin-top: 10px;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            footer {
                margin-left: 0;
            }
            
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
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
    
    <div class="content">
        <header>
            <div class="container">
                <h1 class="page-title">Laporan Shift Kasir</h1>
                <div class="breadcrumb">Dashboard / Laporan / Shift Kasir</div>
            </div>
        </header>
        
        <div class="container">
            <!-- Ringkasan Laporan -->
            <div class="summary-card">
                <h3 class="summary-title">Ringkasan Laporan</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-value"><?= count($shifts) ?></div>
                        <div class="summary-label">Total Shift</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value"><?= $total_all_transaksi ?></div>
                        <div class="summary-label">Total Transaksi</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value"><?= $total_all_pelanggan ?></div>
                        <div class="summary-label">Total Pelanggan</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value money">Rp <?= number_format($total_all_pendapatan, 0, ',', '.') ?></div>
                        <div class="summary-label">Total Pendapatan</div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Filter Laporan</h2>
                </div>
                <div class="card-body">
                    <form action="" method="GET" class="filter-form">
                        <input type="hidden" name="filter" value="date">
                        <div class="form-group">
                            <label for="start_date">Tanggal Mulai</label>
                            <input type="date" id="start_date" name="start_date" value="<?= $start_date ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="end_date">Tanggal Akhir</label>
                            <input type="date" id="end_date" name="end_date" value="<?= $end_date ?>" class="form-control">
                        </div>
                        <button type="submit" class="btn">Filter</button>
                        <a href="laporan_shift.php" class="btn" style="background-color: #6c757d;">Reset</a>
                        <a href="export_shift.php<?= !empty($date_filter) ? '?start_date=' . $start_date . '&end_date=' . $end_date : '' ?>" class="btn export-btn">Export Excel</a>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Data Shift</h2>
                    <a href="shift.php" class="btn">+ Buka Shift Baru</a>
                </div>
                <div class="card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Shift</th>
                                <th>Kasir</th>
                                <th>Waktu Mulai</th>
                                <th>Waktu Selesai</th>
                                <th>Status</th>
                                <th>Total Transaksi</th>
                                <th>Total Pelanggan</th>
                                <th>Total Pendapatan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($shifts) > 0): ?>
                                <?php foreach ($shifts as $row): ?>
                                    <?php
                                    $shift_id = $row['id'];
                                    $total_transaksi = $transaction_data[$shift_id]['total_transaksi'] ?? 0;
                                    $total_pendapatan = $transaction_data[$shift_id]['total_pendapatan'] ?? 0;
                                    $total_pelanggan = $transaction_data[$shift_id]['total_pelanggan'] ?? 0;
                                    ?>
                                    <tr>
                                        <td><span class="badge badge-primary">#<?= $row['id'] ?></span></td>
                                        <td>
                                            <strong><?= $row['cashier_name'] ?></strong><br>
                                            <small>ID: <?= $row['cashier_id'] ?></small>
                                        </td>
                                        <td><?= date('d-m-Y H:i', strtotime($row['start_time'])) ?></td>
                                        <td><?= $row['end_time'] ? date('d-m-Y H:i', strtotime($row['end_time'])) : '-' ?></td>
                                        <td>
                                            <span class="<?= $row['status'] == 'Aktif' ? 'status-active' : 'status-closed' ?>">
                                                <?= $row['status'] ?>
                                            </span>
                                        </td>
                                        <td class="text-right"><?= $total_transaksi ?></td>
                                        <td class="text-right"><?= $total_pelanggan ?></td>
                                        <td class="text-right money">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
                                        <td>
                                            <?php if ($row['status'] == 'Aktif'): ?>
                                                <a href="end_shift.php?id=<?= $row['id'] ?>" class="btn btn-danger">Tutup Shift</a>
                                            <?php else: ?>
                                                <a href="shift_detail.php?id=<?= $row['id'] ?>" class="btn">Detail</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 20px;">Tidak ada data shift dengan transaksi</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        &copy; <?= date('Y') ?> Sistem Manajemen Kasir Barbershop
    </footer>
    
    <script>
        // Date filter validation
        document.querySelector('form.filter-form').addEventListener('submit', function(e) {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            if (startDate && endDate) {
                if (new Date(startDate) > new Date(endDate)) {
                    e.preventDefault();
                    alert('Tanggal Mulai tidak boleh lebih besar dari Tanggal Akhir');
                    return false;
                }
            }
            
            if ((startDate && !endDate) || (!startDate && endDate)) {
                e.preventDefault();
                alert('Mohon lengkapi kedua tanggal untuk melakukan filter');
                return false;
            }
        });
    </script>
</body>
</html>