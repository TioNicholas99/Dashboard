<?php
include 'config.php';

// Get today's transactions
$today = date("Y-m-d");
$total_today = $conn->query("SELECT COALESCE(SUM(total_price), 0) AS total FROM transactions WHERE DATE(transaction_date) = '$today'")->fetch_assoc()['total'];
$count_today = $conn->query("SELECT COALESCE(COUNT(*), 0) AS count FROM transactions WHERE DATE(transaction_date) = '$today'")->fetch_assoc()['count'];

// Get this month's transactions
$current_month = date("Y-m");
$total_month = $conn->query("SELECT SUM(total_price) AS total FROM transactions WHERE DATE_FORMAT(created_at, '%Y-%m') = '$current_month'")->fetch_assoc()['total'] ?? 0;
$count_month = $conn->query("SELECT COUNT(*) AS count FROM transactions WHERE DATE_FORMAT(created_at, '%Y-%m') = '$current_month'")->fetch_assoc()['count'] ?? 0;

// Get latest transactions
$transactions = $conn->query("SELECT * FROM transactions ORDER BY transaction_date DESC LIMIT 10");

// Get low stock items
$low_stock_items = $conn->query("SELECT * FROM items WHERE quantity <= min_stock ORDER BY quantity ASC");

// Get income data for the last 7 days
$income_data = $conn->query("SELECT DATE(created_at) AS date, SUM(total_price) AS total FROM transactions WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date ASC");

$dates = [];
$totals = [];
while ($row = $income_data->fetch_assoc()) {
    $formatted_date = date('d M', strtotime($row['date']));
    $dates[] = $formatted_date;
    $totals[] = $row['total'] ?? 0;
}

// Create chart URL with QuickChart
$chart_url = "https://quickchart.io/chart?c=" . urlencode(json_encode([
    "type" => "bar",
    "data" => [
        "labels" => $dates,
        "datasets" => [[
            "label" => "Pendapatan",
            "data" => $totals,
            "backgroundColor" => "rgba(54, 162, 235, 0.7)",
            "borderColor" => "rgba(54, 162, 235, 1)",
            "borderWidth" => 1
        ]]
    ],
    "options" => [
        "responsive" => true,
        "legend" => ["position" => "top"],
        "title" => [
            "display" => true,
            "text" => "Pendapatan 7 Hari Terakhir"
        ],
        "scales" => [
            "yAxes" => [[
                "ticks" => ["beginAtZero" => true],
                "gridLines" => ["color" => "rgba(0, 0, 0, 0.05)"]
            ]],
            "xAxes" => [[
                "gridLines" => ["color" => "rgba(0, 0, 0, 0.05)"]
            ]]
        ]
    ]
]));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kasir</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --danger-color: #e63946;
            --warning-color: #ffb703;
            --success-color: #2a9d8f;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
            line-height: 1.6;
        }
        
        .sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding-top: 20px;
            transition: var(--transition);
            z-index: 1000;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }
        
        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 20px;
            font-weight: 600;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            transition: var(--transition);
            border-left: 4px solid transparent;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: white;
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-divider {
            height: 1px;
            background-color: rgba(255, 255, 255, 0.2);
            margin: 10px 20px;
        }
        
        .sidebar-category {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.6);
            padding: 10px 20px 5px;
            margin-top: 5px;
        }
        
        .content {
            margin-left: 260px;
            padding: 25px;
            transition: var(--transition);
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .dashboard-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .date-display {
            font-size: 14px;
            color: #6c757d;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .stat-title {
            font-size: 16px;
            color: #6c757d;
            font-weight: 500;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: white;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 600;
            margin: 10px 0;
        }
        
        .stat-subtitle {
            font-size: 14px;
            color: #6c757d;
        }
        
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
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
        
        .chart-container {
            width: 100%;
            height: 300px;
            display: flex;
            justify-content: center;
        }
        
        .chart-container img {
            max-width: 100%;
            height: auto;
            border-radius: var(--border-radius);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .low-stock {
            background-color: rgba(230, 57, 70, 0.1);
        }
        
        .stock-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            width: fit-content;
        }
        
        .status-low {
            background-color: rgba(230, 57, 70, 0.1);
            color: var(--danger-color);
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            text-align: center;
            margin-right: 10px;
        }
        
        .btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #d90429;
        }
        
        .action-buttons {
            margin-top: 15px;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }
        
        .badge-success {
            background-color: rgba(42, 157, 143, 0.1);
            color: var(--success-color);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding: 0;
            }
            
            .content {
                margin-left: 0;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            /* Hamburger menu for mobile */
            .menu-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                cursor: pointer;
                background-color: var(--primary-color);
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                box-shadow: var(--box-shadow);
            }
            
            .sidebar.active {
                width: 260px;
                padding-top: 20px;
            }
        }

        
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Sistem Kasir</h2>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            
            <div class="sidebar-category">Transaksi</div>
            <li><a href="transaksi.php"><i class="fas fa-shopping-cart"></i> Transaksi Baru</a></li>
            <li><a href="riwayat_transaksi.php"><i class="fas fa-history"></i> Riwayat Transaksi</a></li>
            <li><a href="proses_transaksi.php"><i class="fas fa-cogs"></i> Proses Transaksi</a></li>
            
            <div class="sidebar-category">Inventori</div>
            <li><a href="input_stock.php"><i class="fas fa-plus-circle"></i> Input Barang</a></li>
            <li><a href="stock.php"><i class="fas fa-boxes"></i> Stok Barang</a></li>
            
            <div class="sidebar-category">Laporan</div>
            <li><a href="laporan.php"><i class="fas fa-file-invoice"></i> Laporan Umum</a></li>
            <li><a href="laporan_stock.php"><i class="fas fa-clipboard-list"></i> Laporan Stok</a></li>
            <li><a href="laporan_shift.php"><i class="fas fa-user-clock"></i> Laporan Shift</a></li>
            
            <div class="sidebar-category">Ekspor Data</div>
            <li><a href="export_pdf.php"><i class="fas fa-file-pdf"></i> Export PDF</a></li>
            <li><a href="exportlaporan.php"><i class="fas fa-file-export"></i> Export Laporan</a></li>
            
            <div class="sidebar-category">Lainnya</div>
            <li><a href="shift.php"><i class="fas fa-clock"></i> Shift Kerja</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Keluar</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Dashboard Kasir</h1>
                <div class="date-display"><?= date('l, d F Y') ?></div>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Pendapatan Hari Ini</div>
                    <div class="stat-icon" style="background-color: var(--primary-color);">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
                <div class="stat-value">Rp <?= number_format($total_today, 0, ',', '.') ?></div>
                <div class="stat-subtitle"><?= $count_today ?> transaksi hari ini</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Pendapatan Bulan Ini</div>
                    <div class="stat-icon" style="background-color: var(--success-color);">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
                <div class="stat-value">Rp <?= number_format($total_month, 0, ',', '.') ?></div>
                <div class="stat-subtitle"><?= $count_month ?> transaksi bulan ini</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Barang Stok Rendah</div>
                    <div class="stat-icon" style="background-color: var(--danger-color);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
                <div class="stat-value">
                    <?php
                    $low_stock_count = $conn->query("SELECT COUNT(*) AS count FROM items WHERE quantity <= min_stock")->fetch_assoc()['count'] ?? 0;
                    echo $low_stock_count;
                    ?>
                </div>
                <div class="stat-subtitle">Barang perlu diisi ulang</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Pendapatan 7 Hari Terakhir</h2>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <img src="<?= $chart_url ?>" alt="Grafik Pendapatan 7 Hari Terakhir">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Transaksi Terbaru</h2>
            </div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>ID Transaksi</th>
                            <th>Total</th>
                            <th>Waktu</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($transactions->num_rows > 0): ?>
                            <?php while ($row = $transactions->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= $row['id'] ?></td>
                                    <td>Rp <?= number_format($row['total_price'], 0, ',', '.') ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                    <td><span class="badge badge-success">Selesai</span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">Tidak ada transaksi terbaru</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Barang Hampir Habis</h2>
            </div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Barang</th>
                            <th>Sisa Stok</th>
                            <th>Minimal Stok</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($low_stock_items->num_rows > 0): ?>
                            <?php while ($row = $low_stock_items->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= number_format($row['quantity'], 2) ?></td>
                                    <td><?= number_format($row['min_stock'], 2) ?></td>
                                    <td>
                                        <div class="stock-status status-low">
                                            <i class="fas fa-exclamation-circle"></i> Stok Rendah
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">Semua barang memiliki stok yang cukup</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Aksi Cepat</h2>
            </div>
            <div class="card-body">
                <div class="action-buttons">
                    <a href="transaksi.php" class="btn"><i class="fas fa-plus-circle"></i> Tambah Transaksi</a>
                    <a href="input_stock.php" class="btn"><i class="fas fa-box-open"></i> Input Barang</a>
                    <a href="export_pdf.php" class="btn"><i class="fas fa-file-pdf"></i> Export Laporan</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile menu toggle (only visible on small screens) -->
    <div class="menu-toggle" style="display: none;">
        <i class="fas fa-bars"></i>
    </div>

    <script>
        // For mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
            
            // Close sidebar when clicking outside (for mobile)
            document.addEventListener('click', function(e) {
                if (!sidebar.contains(e.target) && !menuToggle.contains(e.target) && window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                }
            });
            
            // Show menu toggle on small screens
            function checkScreenSize() {
                if (window.innerWidth <= 768) {
                    menuToggle.style.display = 'flex';
                } else {
                    menuToggle.style.display = 'none';
                }
            }
            
            window.addEventListener('resize', checkScreenSize);
            checkScreenSize(); // Initial check
        });
    </script>
</body>
</html>