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

// Get current user info from session if available
$current_user = $_SESSION['user_name'] ?? 'Staff';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Shift | Barbershop Management System</title>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #6c63ff;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #3498db;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --radius: 8px;
            --radius-sm: 4px;
            --radius-lg: 12px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--gray-800);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Improved Sidebar Styles */
        .sidebar {
            width: 280px;
            height: 100vh;
            position: fixed;
            background: linear-gradient(135deg, #2c3e50, #1a252f);
            color: white;
            padding-top: 20px;
            z-index: 100;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            transition: var(--transition);
        }
        
        .sidebar h2 {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .sidebar-categories {
            margin-bottom: 10px;
            padding-left: 20px;
            color: rgba(255,255,255,0.6);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
            margin-top: 20px;
        }
        
        .sidebar a {
            display: flex;
            align-items: center;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 12px 20px;
            margin: 2px 8px;
            border-radius: var(--radius-sm);
            transition: var(--transition);
        }
        
        .sidebar a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar a.active {
            background: rgba(0,123,255,0.6);
            color: white;
            font-weight: 500;
        }
        
        .sidebar-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 20px;
            font-size: 13px;
            color: rgba(255,255,255,0.6);
            text-align: center;
            background: rgba(0,0,0,0.2);
        }
        
        /* Toggle Sidebar Button */
        .toggle-sidebar {
            display: none;
            position: fixed;
            left: 20px;
            top: 20px;
            z-index: 101;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            padding: 10px 12px;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .toggle-sidebar:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        /* Main Content */
        .content {
            margin-left: 280px;
            padding: 20px;
            transition: var(--transition);
        }
        
        /* Header */
        header {
            background-color: white;
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            border-radius: var(--radius);
        }
        
        .page-title {
            color: var(--gray-800);
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .breadcrumb {
            color: var(--gray-600);
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        /* Cards */
        .card {
            background-color: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-5px);
        }
        
        .card-header {
            padding: 20px;
            background-color: white;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            color: var(--gray-800);
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Forms */
        .filter-form {
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 15px;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--gray-700);
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.3);
        }
        
        /* Tables */
        .table-responsive {
            overflow-x: auto;
            border-radius: var(--radius-sm);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            background-color: white;
        }
        
        th {
            background-color: var(--gray-100);
            color: var(--gray-800);
            font-weight: 600;
            text-align: left;
            padding: 14px 15px;
            border-bottom: 2px solid var(--gray-200);
            position: sticky;
            top: 0;
        }
        
        td {
            padding: 14px 15px;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
        }
        
        tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        /* Summary Cards */
        .summary-card {
            background-color: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
            transition: var(--transition);
        }
        
        .summary-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-5px);
        }
        
        .summary-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--gray-800);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .summary-title i {
            color: var(--primary);
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        
        .summary-item {
            text-align: center;
            padding: 20px;
            border-radius: var(--radius-sm);
            background-color: var(--gray-100);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 140px;
        }
        
        .summary-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }
        
        .summary-icon {
            font-size: 24px;
            margin-bottom: 10px;
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .summary-value {
            font-size: 28px;
            font-weight: 700;
            margin: 10px 0;
            color: var(--gray-800);
        }
        
        .summary-label {
            font-size: 14px;
            color: var(--gray-600);
        }
        
        /* Status Badges */
        .status-active {
            color: var(--success);
            font-weight: 600;
            background-color: rgba(46, 204, 113, 0.1);
            padding: 6px 12px;
            border-radius: 50px;
            display: inline-block;
        }
        
        .status-closed {
            color: var(--gray-600);
            font-weight: 600;
            background-color: rgba(108, 117, 125, 0.1);
            padding: 6px 12px;
            border-radius: 50px;
            display: inline-block;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            background-color: var(--primary);
            color: white;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-danger {
            background-color: var(--danger);
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .btn-secondary {
            background-color: var(--gray-600);
        }
        
        .btn-secondary:hover {
            background-color: var(--gray-700);
        }
        
        .btn-success {
            background-color: var(--success);
        }
        
        .btn-success:hover {
            background-color: #27ae60;
        }
        
        .btn-sm {
            padding: 6px 10px;
            font-size: 12px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .money {
            font-family: 'Consolas', monospace;
            font-weight: 600;
            color: var(--gray-800);
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }
        
        .badge-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success);
        }
        
        .badge-danger {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }
        
        .badge-warning {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }
        
        .badge-info {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--info);
        }
        
        .export-btn {
            margin-left: 5px;
        }
        
        /* Footer */
        footer {
            text-align: center;
            padding: 25px;
            color: var(--gray-600);
            font-size: 14px;
            margin-top: 40px;
            margin-left: 280px;
            background-color: white;
            border-top: 1px solid var(--gray-200);
            transition: var(--transition);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray-600);
        }
        
        .empty-state i {
            font-size: 48px;
            color: var(--gray-400);
            margin-bottom: 15px;
        }
        
        .empty-state h4 {
            font-size: 18px;
            color: var(--gray-700);
            margin-bottom: 10px;
        }
        
        .empty-state p {
            max-width: 400px;
            margin: 0 auto;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            list-style: none;
            margin: 30px 0;
            padding: 0;
        }
        
        .pagination li {
            margin: 0 5px;
        }
        
        .pagination a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            background-color: white;
            color: var(--gray-700);
            text-decoration: none;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }
        
        .pagination a:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .pagination li.active a {
            background-color: var(--primary);
            color: white;
        }
        
        /* Alerts */
        .alert {
            padding: 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            border-left: 4px solid transparent;
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            border-color: var(--success);
            color: #27ae60;
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            border-color: var(--danger);
            color: #c0392b;
        }
        
        .alert-warning {
            background-color: rgba(243, 156, 18, 0.1);
            border-color: var(--warning);
            color: #d35400;
        }
        
        .alert-info {
            background-color: rgba(52, 152, 219, 0.1);
            border-color: var(--info);
            color: #2980b9;
        }
        
        /* Tooltips */
        .tooltip {
            position: relative;
            display: inline-block;
        }
        
        .tooltip .tooltip-text {
            visibility: hidden;
            width: 120px;
            background-color: var(--gray-800);
            color: white;
            text-align: center;
            border-radius: var(--radius-sm);
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
            pointer-events: none;
        }
        
        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        /* Dropdown */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 160px;
            box-shadow: var(--shadow);
            border-radius: var(--radius-sm);
            z-index: 1;
            right: 0;
        }
        
        .dropdown-content a {
            color: var(--gray-700);
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .dropdown-content a:hover {
            background-color: var(--gray-100);
        }
        
        .dropdown:hover .dropdown-content {
            display: block;
        }
        
        /* Media Queries for Responsive Design */
        @media (max-width: 1200px) {
            .summary-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 240px;
            }
            .content, footer {
                margin-left: 240px;
            }
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .toggle-sidebar {
                display: block;
            }
            .sidebar {
                width: 0;
                opacity: 0;
                visibility: hidden;
            }
            .sidebar.active {
                width: 260px;
                opacity: 1;
                visibility: visible;
            }
            .content, footer {
                margin-left: 0;
            }
            .container {
                padding: 10px;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
                margin-top: 10px;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Toggle Sidebar Button for Mobile -->
    <button class="toggle-sidebar">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <h2><i class="fas fa-cut"></i> Barbershop</h2>
        
        <div class="sidebar-categories">Dashboard</div>
        <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <span class="sidebar-icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard
        </a>
        
        <div class="sidebar-categories">Transaksi</div>
        <a href="transaksi.php" class="<?= $current_page == 'transaksi.php' ? 'active' : '' ?>">
            <span class="sidebar-icon"><i class="fas fa-cash-register"></i></span> Buat Transaksi
        </a>
        <a href="proses_transaksi.php" class="<?= $current_page == 'proses_transaksi.php' ? 'active' : '' ?>">
            <span class="sidebar-icon"><i class="fas fa-money-bill-wave"></i></span> Proses Transaksi
        </a>
        <a href="riwayat_transaksi.php" class="<?= $current_page == 'riwayat_transaksi.php' ? 'active' : '' ?>">
            <span class="sidebar-icon"><i class="fas fa-history"></i></span> Riwayat Transaksi
        </a>
        
        <div class="sidebar-categories">Inventori</div>
        <a href="input_stock.php" class="<?= $current_page == 'input_stock.php' ? 'active' : '' ?>">
            <span class="sidebar-icon"><i class="fas fa-box"></i></span> Input Stok
        </a>
        <a href="stock.php" class="<?= $current_page == 'stock.php' ? 'active' : '' ?>">
            <span class="sidebar-icon"><i class="fas fa-boxes"></i></span> Stok Barang
        </a>
        
        <div class="sidebar-categories">Laporan</div>
        <a href="laporan.php" class="<?= $current_page == 'laporan.php' ? 'active' : '' ?>">
            <span class="sidebar-icon"><i class="fas fa-chart-line"></i></span> Laporan Umum
        </a>
        <a href="laporan_stock.php" class="<?= $current_page == 'laporan_stock.php' ? 'active' : '' ?>">
            <span class="sidebar-icon"><i class="fas fa-clipboard-list"></i></span> Laporan Stok
        </a>
        <a href="laporan_shift.php" class="<?= $current_page == 'laporan_shift.php' ? 'active' : '' ?>">
            <span class="sidebar-icon"><i class="fas fa-user-clock"></i></span> Laporan Shift
        </a>
        
        <div class="sidebar-categories">Shift</div>
        <a href="shift.php" class="<?= $current_page == 'shift.php' ? 'active' : '' ?>">
            <span class="sidebar-icon"><i class="fas fa-calendar-alt"></i></span> Shift Kerja
        </a>
        
        <div class="sidebar-categories">Export</div>
        <a href="export_pdf.php" class="<?= $current_page == 'export_pdf.php' ? 'active' : '' ?>">
            <span class="sidebar-icon"><i class="fas fa-file-pdf"></i></span> Export PDF
        </a>
        <a href="exportlaporan.php" class="<?= $current_page == 'exportlaporan.php' ? 'active' : '' ?>">
            <span class="sidebar-icon"><i class="fas fa-file-excel"></i></span> Export Laporan
        </a>
        
        <div class="sidebar-footer">
            <div><i class="fas fa-user"></i> <?= $current_user ?></div>
            <a href="logout.php" style="display: inline-block; margin-top: 5px; color: #FF6B6B;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="content">
        <!-- Header -->
        <header>
            <div class="container">
                <h1 class="page-title"><i class="fas fa-user-clock"></i> Laporan Shift Kasir</h1>
                <div class="breadcrumb">
                    <i class="fas fa-home"></i> Dashboard / Laporan / Shift Kasir
                </div>
            </div>
        </header>
        
        <div class="container">
            <!-- Ringkasan Laporan -->
            <div class="summary-card">
                <h3 class="summary-title"><i class="fas fa-chart-pie"></i> Ringkasan Laporan</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="summary-value"><?= count($shifts) ?></div>
                        <div class="summary-label">Total Shift</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-icon"><i class="fas fa-receipt"></i></div>
                        <div class="summary-value"><?= $total_all_transaksi ?></div><div class="summary-label">Total Transaksi</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-icon"><i class="fas fa-user-friends"></i></div>
                        <div class="summary-value"><?= $total_all_pelanggan ?></div>
                        <div class="summary-label">Total Pelanggan</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-icon"><i class="fas fa-money-bill-wave"></i></div>
                        <div class="summary-value">Rp <?= number_format($total_all_pendapatan, 0, ',', '.') ?></div>
                        <div class="summary-label">Total Pendapatan</div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter"></i> Filter Data</h3>
                </div>
                <div class="card-body">
                    <form action="" method="GET" class="filter-form">
                        <input type="hidden" name="filter" value="date">
                        <div class="form-group">
                            <label for="start_date">Tanggal Mulai</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" value="<?= $start_date ?>">
                        </div>
                        <div class="form-group">
                            <label for="end_date">Tanggal Selesai</label>
                            <input type="date" id="end_date" name="end_date" class="form-control" value="<?= $end_date ?>">
                        </div>
                        <button type="submit" class="btn">
                            <i class="fas fa-search"></i> Cari
                        </button>
                        <a href="laporan_shift.php" class="btn btn-secondary">
                            <i class="fas fa-sync-alt"></i> Reset
                        </a>
                        <a href="export_shift_pdf.php<?= !empty($date_filter) ? '?start_date=' . $start_date . '&end_date=' . $end_date : '' ?>" target="_blank" class="btn btn-success export-btn">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                        <a href="export_shift_excel.php<?= !empty($date_filter) ? '?start_date=' . $start_date . '&end_date=' . $end_date : '' ?>" class="btn btn-success export-btn">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                    </form>
                </div>
            </div>
            
            <!-- Data Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clipboard-list"></i> Data Shift Kasir</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($shifts)): ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h4>Data tidak ditemukan</h4>
                        <p>Tidak ada data shift yang sesuai dengan filter yang dipilih. Silakan ubah filter atau reset pencarian.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>ID Shift</th>
                                    <th>Nama Kasir</th>
                                    <th>Mulai Shift</th>
                                    <th>Selesai Shift</th>
                                    <th>Durasi</th>
                                    <th>Transaksi</th>
                                    <th>Pelanggan</th>
                                    <th>Pendapatan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                foreach($shifts as $shift): 
                                    $shift_id = $shift['id'];
                                    $transaksi = $transaction_data[$shift_id]['total_transaksi'] ?? 0;
                                    $pendapatan = $transaction_data[$shift_id]['total_pendapatan'] ?? 0;
                                    $pelanggan = $transaction_data[$shift_id]['total_pelanggan'] ?? 0;
                                    
                                    // Hitung durasi shift
                                    $start_time = new DateTime($shift['start_time']);
                                    if ($shift['end_time']) {
                                        $end_time = new DateTime($shift['end_time']);
                                        $duration = $start_time->diff($end_time);
                                        $durasi = $duration->format('%H jam %i menit');
                                    } else {
                                        $durasi = "Belum selesai";
                                    }
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $shift['id'] ?></td>
                                    <td><?= $shift['cashier_name'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($shift['start_time'])) ?></td>
                                    <td><?= $shift['end_time'] ? date('d/m/Y H:i', strtotime($shift['end_time'])) : '-' ?></td>
                                    <td><?= $durasi ?></td>
                                    <td class="text-right"><?= $transaksi ?></td>
                                    <td class="text-right"><?= $pelanggan ?></td>
                                    <td class="text-right money">Rp <?= number_format($pendapatan, 0, ',', '.') ?></td>
                                    <td>
                                        <?php if($shift['status'] == 'Aktif'): ?>
                                            <span class="status-active">Aktif</span>
                                        <?php else: ?>
                                            <span class="status-closed">Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="detail_shift.php?id=<?= $shift['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Grafik Performa Shift -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar"></i> Grafik Performa Shift</h3>
                </div>
                <div class="card-body">
                    <canvas id="shiftPerformanceChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer>
            &copy; 2023 Barbershop Management System. All rights reserved.
        </footer>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    
    <script>
        // Toggle Sidebar on Mobile
        document.querySelector('.toggle-sidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Chart.js Implementation
        $(document).ready(function() {
            // Prepare data for chart
            const shiftLabels = [
                <?php 
                $counter = 0;
                foreach(array_slice($shifts, 0, 7) as $shift) {
                    echo '"' . date('d/m', strtotime($shift['start_time'])) . ' - ' . $shift['cashier_name'] . '"';
                    if (++$counter < min(count($shifts), 7)) echo ", ";
                } 
                ?>
            ];
            
            const transactionData = [
                <?php 
                $counter = 0;
                foreach(array_slice($shifts, 0, 7) as $shift) {
                    $shift_id = $shift['id'];
                    echo ($transaction_data[$shift_id]['total_transaksi'] ?? 0);
                    if (++$counter < min(count($shifts), 7)) echo ", ";
                } 
                ?>
            ];
            
            const revenueData = [
                <?php 
                $counter = 0;
                foreach(array_slice($shifts, 0, 7) as $shift) {
                    $shift_id = $shift['id'];
                    echo ($transaction_data[$shift_id]['total_pendapatan'] ?? 0);
                    if (++$counter < min(count($shifts), 7)) echo ", ";
                } 
                ?>
            ];
            
            const customerData = [
                <?php 
                $counter = 0;
                foreach(array_slice($shifts, 0, 7) as $shift) {
                    $shift_id = $shift['id'];
                    echo ($transaction_data[$shift_id]['total_pelanggan'] ?? 0);
                    if (++$counter < min(count($shifts), 7)) echo ", ";
                } 
                ?>
            ];
            
            // Create chart
            const ctx = document.getElementById('shiftPerformanceChart').getContext('2d');
            const shiftPerformanceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: shiftLabels,
                    datasets: [
                        {
                            label: 'Transaksi',
                            data: transactionData,
                            backgroundColor: 'rgba(52, 152, 219, 0.7)',
                            borderColor: 'rgba(52, 152, 219, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Pelanggan',
                            data: customerData,
                            backgroundColor: 'rgba(46, 204, 113, 0.7)',
                            borderColor: 'rgba(46, 204, 113, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Pendapatan (dalam ribuan)',
                            data: revenueData.map(value => value / 1000),
                            type: 'line',
                            backgroundColor: 'rgba(243, 156, 18, 0.2)',
                            borderColor: 'rgba(243, 156, 18, 1)',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: true,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Jumlah'
                            }
                        },
                        y1: {
                            position: 'right',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Pendapatan (Ribuan Rupiah)'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Performa Shift Kasir (7 Shift Terakhir)',
                            font: {
                                size: 16
                            }
                        },
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.dataset.label === 'Pendapatan (dalam ribuan)') {
                                        return label + 'Rp ' + (context.raw * 1000).toLocaleString('id-ID');
                                    }
                                    return label + context.raw;
                                }
                            }
                        }
                    }
                }
            });
            
            // Data Table Hover Effect
            $('table tbody tr').hover(
                function() {
                    $(this).css('background-color', 'rgba(67, 97, 238, 0.05)');
                },
                function() {
                    $(this).css('background-color', '');
                }
            );
        });
    </script>
</body>
</html>
                        
