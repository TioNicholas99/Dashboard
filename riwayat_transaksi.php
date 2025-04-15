<?php
include 'config.php';

// Ambil riwayat transaksi terbaru
$transactions = $conn->query("
SELECT t.id, t.total_price, t.transaction_date AS created_at, 
       COALESCE(GROUP_CONCAT(DISTINCT i.name ORDER BY i.name SEPARATOR ', '), 'No Items') AS items
FROM transactions t
LEFT JOIN transaction_items ti ON t.id = ti.transaction_id
LEFT JOIN items i ON ti.item_id = i.id
GROUP BY t.id, t.total_price, t.transaction_date
ORDER BY t.transaction_date DESC, t.id DESC;





");

if (!$transactions) {
    die("Error dalam query: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi | Kasir Barbershop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #f3f4f6;
            --dark: #1f2937;
            --light: #ffffff;
            --accent: #3b82f6;
            --danger: #ef4444;
            --success: #10b981;
            --text-dark: #374151;
            --text-light: #9ca3af;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9fafb;
            color: var(--text-dark);
            line-height: 1.6;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: linear-gradient(to bottom, var(--primary), var(--primary-dark));
            color: var(--light);
            padding-top: 1.5rem;
            overflow-y: auto;
            transition: all 0.3s ease;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1.5rem;
        }
        
        .logo-container i {
            font-size: 1.8rem;
            margin-right: 0.8rem;
        }
        
        .logo-container h2 {
            font-weight: 600;
            font-size: 1.4rem;
            letter-spacing: 0.5px;
        }
        
        .nav-group {
            margin-bottom: 1.5rem;
        }
        
        .nav-title {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0.5rem 1.5rem;
            opacity: 0.7;
        }
        
        .sidebar a {
            display: flex;
            align-items: center;
            color: var(--light);
            text-decoration: none;
            padding: 0.8rem 1.5rem;
            margin: 0.2rem 0;
            border-radius: 0 2rem 2rem 0;
            transition: all 0.2s;
        }
        
        .sidebar a i {
            margin-right: 0.8rem;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
        
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .sidebar a.active {
            background: var(--light);
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        /* Content area */
        .content {
            margin-left: 280px;
            padding: 2rem;
            transition: all 0.3s ease;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .page-actions {
            display: flex;
            gap: 1rem;
        }
        
        .action-btn {
            padding: 0.6rem 1.2rem;
            background: var(--primary);
            color: var(--light);
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .action-btn i {
            margin-right: 0.5rem;
        }
        
        .action-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        /* Card styling */
        .card {
            background: var(--light);
            border-radius: 0.8rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .card-body {
            padding: 1rem;
        }
        
        /* Table styling */
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
        }
        
        th {
            background-color: #f8fafc;
            font-weight: 600;
            text-align: left;
            padding: 1rem;
            font-size: 0.9rem;
            color: var(--text-dark);
            border-bottom: 2px solid #e5e7eb;
            position: sticky;
            top: 0;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.95rem;
            vertical-align: middle;
        }
        
        tbody tr:hover {
            background-color: #f9fafb;
        }
        
        .table-id {
            font-weight: 600;
            color: var(--primary);
        }
        
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .price-value {
            font-weight: 600;
        }
        
        .timestamp {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .item-list {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn i {
            margin-right: 0.5rem;
        }
        
        /* Footer */
        footer {
            margin-left: 280px;
            padding: 1.5rem 2rem;
            text-align: center;
            color: var(--text-light);
            font-size: 0.9rem;
            border-top: 1px solid #e5e7eb;
            background-color: var(--light);
        }
        
        /* Responsive */
        @media screen and (max-width: 1024px) {
            .sidebar {
                width: 240px;
            }
            
            .content, footer {
                margin-left: 240px;
            }
        }
        
        @media screen and (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding-top: 1rem;
            }
            
            .logo-container {
                justify-content: center;
                padding: 0.5rem;
                margin-bottom: 1rem;
            }
            
            .logo-container h2, .nav-title, .sidebar a span {
                display: none;
            }
            
            .logo-container i {
                margin-right: 0;
                font-size: 1.5rem;
            }
            
            .sidebar a {
                justify-content: center;
                padding: 0.8rem;
                border-radius: 50%;
                margin: 0.5rem auto;
                width: 40px;
                height: 40px;
            }
            
            .sidebar a i {
                margin-right: 0;
            }
            
            .content, footer {
                margin-left: 70px;
                padding: 1.5rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
        }
        
        @media screen and (max-width: 576px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 0.5rem;
                display: flex;
                overflow-x: auto;
            }
            
            .logo-container {
                display: none;
            }
            
            .nav-group {
                display: flex;
                margin-bottom: 0;
                margin-right: 1rem;
            }
            
            .nav-title {
                display: none;
            }
            
            .sidebar a {
                padding: 0.5rem;
                margin: 0 0.2rem;
                border-radius: 0.3rem;
                font-size: 0.8rem;
                min-width: fit-content;
                height: auto;
                flex-direction: column;
            }
            
            .sidebar a i {
                margin: 0 0 0.3rem 0;
            }
            
            .sidebar a span {
                display: block;
                font-size: 0.7rem;
            }
            
            .content, footer {
                margin-left: 0;
            }
            
            .card {
                margin-bottom: 1rem;
            }
            
            .card-header {
                padding: 1rem;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo-container">
        <i class="fas fa-cut"></i>
        <h2>Kasir Barbershop</h2>
    </div>
    
    <div class="nav-group">
        <div class="nav-title">Dashboard</div>
        <a href="dashboard.php">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
    </div>
    
    <div class="nav-group">
        <div class="nav-title">Transaksi</div>
        <a href="transaksi.php">
            <i class="fas fa-cash-register"></i>
            <span>Transaksi Baru</span>
        </a>
        <a href="riwayat_transaksi.php" class="active">
            <i class="fas fa-history"></i>
            <span>Riwayat Transaksi</span>
        </a>
        <a href="proses_transaksi.php">
            <i class="fas fa-receipt"></i>
            <span>Proses Transaksi</span>
        </a>
    </div>
    
    <div class="nav-group">
        <div class="nav-title">Inventori</div>
        <a href="input_stock.php">
            <i class="fas fa-boxes"></i>
            <span>Input Stok</span>
        </a>
        <a href="stock.php">
            <i class="fas fa-box"></i>
            <span>Stok Barang</span>
        </a>
    </div>
    
    <div class="nav-group">
        <div class="nav-title">Laporan</div>
        <a href="laporan.php">
            <i class="fas fa-chart-bar"></i>
            <span>Laporan Utama</span>
        </a>
        <a href="laporan_stock.php">
            <i class="fas fa-clipboard-list"></i>
            <span>Laporan Stok</span>
        </a>
        <a href="laporan_shift.php">
            <i class="fas fa-user-clock"></i>
            <span>Laporan Shift</span>
        </a>
    </div>
    
    <div class="nav-group">
        <div class="nav-title">Export</div>
        <a href="export_pdf.php">
            <i class="fas fa-file-pdf"></i>
            <span>Export PDF</span>
        </a>
        <a href="exportlaporan.php">
            <i class="fas fa-file-export"></i>
            <span>Export Laporan</span>
        </a>
    </div>
    
    <div class="nav-group">
        <div class="nav-title">Karyawan</div>
        <a href="shift.php">
            <i class="fas fa-calendar-alt"></i>
            <span>Shift Kerja</span>
        </a>
    </div>
</div>

<!-- Konten utama -->
<div class="content">
    <div class="page-header">
        <h1 class="page-title">Riwayat Transaksi</h1>
        <div class="page-actions">
            <button class="action-btn">
                <i class="fas fa-filter"></i> Filter
            </button>
            <button class="action-btn">
                <i class="fas fa-print"></i> Cetak
            </button>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Transaksi Terbaru</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID Transaksi</th>
                            <th>Total Harga</th>
                            <th>Waktu</th>
                            <th>Barang</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $transactions->fetch_assoc()): ?>
                            <tr>
                                <td class="table-id">#<?= htmlspecialchars($row['id']) ?></td>
                                <td class="price-value">Rp <?= number_format($row['total_price'], 0, ',', '.') ?></td>
                                <td class="timestamp"><?= $row['created_at'] ?></td>
                                <td class="item-list" title="<?= htmlspecialchars($row['items'] ?? '-') ?>">
                                    <?= htmlspecialchars($row['items'] ?? '-') ?>
                                </td>
                                <td>
                                    <a href="detail_transaksi.php?id=<?= $row['id'] ?>" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<footer>
    &copy; 2025 Kasir Barbershop - Aplikasi Manajemen Barbershop
</footer>

</body>
</html>