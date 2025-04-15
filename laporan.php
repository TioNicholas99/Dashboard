<?php
include 'config.php';

// Ambil tanggal filter dari input user
$filter_date = $_GET['date'] ?? date("Y-m-d");

// Ambil transaksi berdasarkan tanggal
$transactions_query = "
    SELECT t.id, t.total_price, t.created_at, 
           COALESCE(s.cashier_name, 'Tidak Diketahui') AS cashier_name
    FROM transactions t
    LEFT JOIN shift s ON t.shift_id = s.id
    WHERE DATE(t.created_at) = '$filter_date'
    ORDER BY t.created_at DESC
";

$transactions = $conn->query($transactions_query);
if (!$transactions) {
    die("Query Error: " . $conn->error);
}

// Ambil total pendapatan harian
$total_query = "
    SELECT SUM(total_price) AS total FROM transactions 
    WHERE DATE(created_at) = '$filter_date'
";

$total_result = $conn->query($total_query);
if (!$total_result) {
    die("Query Error: " . $conn->error);
}
$total_day = $total_result->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi | Kasir Barbershop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --success: #2ecc71;
            --text-dark: #333;
            --text-light: #f8f9fa;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --radius: 8px;
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 280px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: var(--primary);
            color: var(--text-light);
            padding-top: 20px;
            overflow-y: auto;
            transition: var(--transition);
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 15px;
        }

        .sidebar h2 {
            text-align: center;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .sidebar-nav {
            padding: 0 10px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 12px 20px;
            margin: 8px 0;
            border-radius: var(--radius);
            transition: var(--transition);
        }

        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .sidebar a:hover, .sidebar a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        /* Main Content */
        .content {
            margin-left: 280px;
            padding: 30px;
            transition: var(--transition);
        }

        .content-header {
            margin-bottom: 30px;
        }

        h2, h3 {
            color: var(--primary);
            margin-bottom: 20px;
        }

        /* Filter Box */
        .filter-box {
            background: white;
            padding: 20px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .filter-form {
            display: flex;
            align-items: flex-end;
            gap: 15px;
        }

        .form-group {
            flex: 1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }

        input[type="date"] {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: var(--radius);
            font-size: 16px;
            transition: var(--transition);
        }

        input[type="date"]:focus {
            border-color: var(--secondary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        button {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        button:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        /* Income Summary */
        .income-summary {
            background: var(--secondary);
            color: white;
            padding: 20px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .income-summary h3 {
            color: white;
            margin: 0;
            font-size: 22px;
        }

        .income-amount {
            font-size: 24px;
            font-weight: 700;
        }

        /* Table Styling */
        .table-container {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: var(--light);
            font-weight: 600;
            color: var(--dark);
        }

        tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }

        td {
            vertical-align: middle;
        }

        .id-column {
            font-weight: 600;
            color: var(--primary);
        }

        .price-column {
            font-weight: 600;
            color: var(--success);
        }

        .action-link {
            display: inline-block;
            padding: 8px 15px;
            background: var(--light);
            color: var(--secondary);
            border-radius: var(--radius);
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }

        .action-link:hover {
            background: var(--secondary);
            color: white;
            transform: translateY(-2px);
        }

        /* Responsive design */
        @media (max-width: 1024px) {
            .sidebar {
                width: 250px;
            }
            .content {
                margin-left: 250px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding: 0;
            }
            .sidebar.active {
                width: 250px;
                padding-top: 20px;
            }
            .content {
                margin-left: 0;
            }
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            .mobile-menu-toggle {
                display: block;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1001;
                background: var(--primary);
                color: white;
                width: 45px;
                height: 45px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                box-shadow: var(--shadow);
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle (only visible on small screens) -->
    <div class="mobile-menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-cut"></i> Kasir Barbershop</h2>
        </div>
        <div class="sidebar-nav">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="transaksi.php"><i class="fas fa-cash-register"></i> Transaksi</a>
            <a href="riwayat_transaksi.php"><i class="fas fa-history"></i> Riwayat Transaksi</a>
            <a href="input_stock.php"><i class="fas fa-boxes"></i> Input Stok</a>
            <a href="stock.php"><i class="fas fa-box"></i> Stok Barang</a>
            <a href="laporan.php" class="active"><i class="fas fa-chart-bar"></i> Laporan</a>
            <a href="laporan_stock.php"><i class="fas fa-clipboard-list"></i> Laporan Stok</a>
            <a href="laporan_shift.php"><i class="fas fa-user-clock"></i> Laporan Shift</a>
            <a href="export_pdf.php"><i class="fas fa-file-pdf"></i> Export PDF</a>
            <a href="exportlaporan.php"><i class="fas fa-file-export"></i> Export Laporan</a>
            <a href="shift.php"><i class="fas fa-exchange-alt"></i> Shift Kerja</a>
            <a href="proses_transaksi.php"><i class="fas fa-tasks"></i> Proses Transaksi</a>
        </div>
    </div>

    <!-- Konten utama -->
    <div class="content">
        <div class="content-header">
            <h2><i class="fas fa-chart-line"></i> Laporan Transaksi</h2>
        </div>

        <div class="filter-box">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="date"><i class="fas fa-calendar-alt"></i> Pilih Tanggal:</label>
                    <input type="date" name="date" id="date" value="<?= $filter_date ?>">
                </div>
                <button type="submit"><i class="fas fa-filter"></i> Filter</button>
            </form>
        </div>

        <div class="income-summary">
            <h3><i class="fas fa-money-bill-wave"></i> Total Pendapatan Harian</h3>
            <div class="income-amount">Rp <?= number_format($total_day, 0, ',', '.') ?></div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> ID Transaksi</th>
                        <th><i class="fas fa-user"></i> Kasir</th>
                        <th><i class="fas fa-tag"></i> Total Harga</th>
                        <th><i class="fas fa-clock"></i> Waktu</th>
                        <th><i class="fas fa-info-circle"></i> Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td class="id-column">#<?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['cashier_name']) ?></td>
                            <td class="price-column">Rp <?= number_format($row['total_price'], 0, ',', '.') ?></td>
                            <td><?= $row['created_at'] ?></td>
                            <td><a href="laporan_detail.php?id=<?= $row['id'] ?>" class="action-link"><i class="fas fa-eye"></i> Lihat Detail</a></td>
                        </tr>
                    <?php endwhile; ?>

                    <?php if ($transactions->num_rows == 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 30px;">
                                <i class="fas fa-info-circle" style="font-size: 48px; color: #ddd; display: block; margin-bottom: 15px;"></i>
                                Tidak ada transaksi pada tanggal ini
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Mobile menu toggle function
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !menuToggle.contains(event.target) &&
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>