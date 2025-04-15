<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_item'])) {
        $name = $_POST['name'];
        $quantity = $_POST['quantity'];
        $unit = $_POST['unit'];
        $min_stock = $_POST['min_stock'];

        $stmt = $conn->prepare("INSERT INTO items (name, quantity, unit, min_stock) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdss", $name, $quantity, $unit, $min_stock);
        
        if ($stmt->execute()) {
            echo "<script>alert('✅ Barang baru berhasil ditambahkan!'); window.location.href='stok.php';</script>";
        } else {
            echo "<script>alert('⚠️ Gagal menambahkan barang.');</script>";
        }
    } elseif (isset($_POST['use_stock'])) {
        $item_id = $_POST['item_id'];
        $total_used = $_POST['total_used'];
        $usage_date = date("Y-m-d");

        $check_stock = $conn->query("SELECT quantity FROM items WHERE id = $item_id");
        $item = $check_stock->fetch_assoc();

        if ($item && $item['quantity'] >= $total_used) {
            $conn->query("UPDATE items SET quantity = quantity - $total_used WHERE id = $item_id");
            $conn->query("INSERT INTO stock_usage (item_id, total_used, usage_date) VALUES ($item_id, $total_used, '$usage_date')");
            echo "<script>alert('✅ Penggunaan stok berhasil disimpan!'); window.location.href='stok.php';</script>";
        } else {
            echo "<script>alert('⚠️ Stok tidak cukup atau item tidak ditemukan!');</script>";
        }
    }
}

$items = $conn->query("SELECT id, name, quantity, unit, min_stock FROM items ORDER BY quantity ASC");

// Get inventory statistics
$total_items = $conn->query("SELECT COUNT(*) as total FROM items")->fetch_assoc()['total'];
$low_stock_items = $conn->query("SELECT COUNT(*) as total FROM items WHERE quantity <= min_stock")->fetch_assoc()['total'];
$safe_stock_items = $total_items - $low_stock_items;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Stok | Kasir Barbershop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Modern Color Palette */
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #e0e7ff;
            --secondary: #0ea5e9;
            --secondary-light: #e0f2fe;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --success: #10b981;
            --success-light: #d1fae5;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --dark: #0f172a;
            --gray-light: #f1f5f9;
            --gray: #94a3b8;
            --gray-dark: #64748b;
            --border: #e2e8f0;
            --white: #ffffff;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: var(--dark);
            line-height: 1.6;
        }
        
        /* Modern sidebar design */
        .sidebar {
            width: 270px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #4338ca;
            border-right: 1px solid var(--border);
            padding: 1.5rem 0;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 0 1.5rem 1.5rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        
        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .sidebar-logo i {
            font-size: 1.75rem;
        }
        
        .sidebar-menu {
            padding: 0 0.75rem;
        }
        
        .menu-category {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--gray-dark);
            font-weight: 600;
            padding: 0.75rem 1rem;
            margin-top: 1rem;
            letter-spacing: 0.05em;
        }
        
        .sidebar a {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            padding: 0.75rem 1rem;
            margin: 0.25rem 0;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .sidebar a i {
            width: 1.5rem;
            font-size: 1.1rem;
            margin-right: 0.75rem;
            text-align: center;
        }
        
        .sidebar a:hover {
            background-color: var(--gray-light);
            color: var(--primary);
        }
        
        .sidebar a.active {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
        }
        
        /* Content area */
        .content {
            margin-left: 270px;
            padding: 2rem;
            transition: all 0.3s ease;
        }
        
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .page-title i {
            color: var(--primary);
        }
        
        /* Stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--white);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            display: flex;
            gap: 1rem;
            align-items: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
        }
        
        .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-icon.blue {
            background-color: var(--primary-light);
            color: var(--primary);
        }
        
        .stat-icon.red {
            background-color: var(--danger-light);
            color: var(--danger);
        }
        
        .stat-icon.green {
            background-color: var(--success-light);
            color: var(--success);
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-dark);
            margin-bottom: 0.25rem;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        /* Card styles */
        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: var(--white);
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.08);
        }
        
        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background-color: #fafafa;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .card-title i {
            color: var(--primary);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Form components */
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        input, select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 1rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #fafafa;
            transition: all 0.2s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
            background-color: var(--white);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            gap: 0.5rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-block {
            width: 100%;
        }
        
        /* Table design */
        .table-container {
            background: var(--white);
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid var(--border);
        }
        
        .table-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background-color: #fafafa;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .table-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
        }
        
        .table-header h3 i {
            color: var(--primary);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem 1.5rem;
            text-align: left;
        }
        
        th {
            font-weight: 600;
            color: var(--gray-dark);
            background-color: #fafafa;
            border-bottom: 1px solid var(--border);
        }
        
        td {
            border-bottom: 1px solid var(--border);
        }
        
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        tbody tr {
            transition: background-color 0.2s;
        }
        
        tbody tr:hover {
            background-color: var(--gray-light);
        }
        
        tr.low-stock {
            background-color: var(--danger-light);
        }
        
        tr.low-stock:hover {
            background-color: #fecaca;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .badge-danger {
            background-color: var(--danger-light);
            color: var(--danger);
        }
        
        .badge-success {
            background-color: var(--success-light);
            color: var(--success);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        
        .empty-state p {
            font-size: 1rem;
            max-width: 25rem;
            margin: 0 auto;
        }
        
        /* Responsive design */
        @media (max-width: 992px) {
            .sidebar {
                width: 220px;
            }
            
            .content {
                margin-left: 220px;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }
            
            .sidebar.open {
                width: 270px;
                transform: translateX(0);
            }
            
            .content {
                margin-left: 0;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            .action-cards {
                grid-template-columns: 1fr;
            }
        }
        
        /* Mobile menu toggle */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--dark);
            margin-right: 1rem;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }
        
        .fade-in-1 { animation-delay: 0.1s; }
        .fade-in-2 { animation-delay: 0.2s; }
        .fade-in-3 { animation-delay: 0.3s; }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-cut"></i>
                <span>Kasir Barbershop</span>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <div class="menu-category">Menu Utama</div>
            <a href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="transaksi.php">
                <i class="fas fa-cash-register"></i>
                <span>Transaksi</span>
            </a>
            <a href="riwayat_transaksi.php">
                <i class="fas fa-history"></i>
                <span>Riwayat Transaksi</span>
            </a>
            
            <div class="menu-category">Manajemen Stok</div>
            <a href="input_stock.php">
                <i class="fas fa-plus-circle"></i>
                <span>Input Barang</span>
            </a>
            <a href="stok.php" class="active">
                <i class="fas fa-boxes"></i>
                <span>Stok Barang</span>
            </a>
            
            <div class="menu-category">Laporan</div>
            <a href="laporan.php">
                <i class="fas fa-chart-bar"></i>
                <span>Laporan Keuangan</span>
            </a>
            <a href="laporan_stock.php">
                <i class="fas fa-clipboard-list"></i>
                <span>Laporan Stok</span>
            </a>
            <a href="laporan_shift.php">
                <i class="fas fa-user-clock"></i>
                <span>Laporan Shift</span>
            </a>
            
            <div class="menu-category">Utilitas</div>
            <a href="export_pdf.php">
                <i class="fas fa-file-pdf"></i>
                <span>Export PDF</span>
            </a>
            <a href="exportlaporan.php">
                <i class="fas fa-file-export"></i>
                <span>Export Laporan</span>
            </a>
            <a href="shift.php">
                <i class="fas fa-calendar-alt"></i>
                <span>Shift Kerja</span>
            </a>
            <a href="proses_transaksi.php">
                <i class="fas fa-receipt"></i>
                <span>Proses Transaksi</span>
            </a>
        </div>
    </div>
    
    <!-- Content -->
    <div class="content">
        <div class="page-header">
            <button class="mobile-menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="page-title">
                <i class="fas fa-boxes"></i>
                Manajemen Stok
            </h1>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid fade-in fade-in-1">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-cubes"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Barang</div>
                    <div class="stat-value"><?= $total_items ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Stok Rendah</div>
                    <div class="stat-value"><?= $low_stock_items ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Stok Aman</div>
                    <div class="stat-value"><?= $safe_stock_items ?></div>
                </div>
            </div>
        </div>
        
        <!-- Action Cards -->
        <div class="action-cards fade-in fade-in-2">
            <!-- Add New Item -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-plus"></i>
                        Tambah Barang Baru
                    </h2>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-group">
                            <label for="name">Nama Barang:</label>
                            <input type="text" name="name" id="name" placeholder="Misalnya: Pomade, Wax, dll" required autocomplete="off">
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity">Jumlah:</label>
                            <input type="number" name="quantity" id="quantity" min="0.1" step="0.1" placeholder="Jumlah stok awal" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="unit">Satuan:</label>
                            <input type="text" name="unit" id="unit" placeholder="Misalnya: botol, pcs, kg, dll" required autocomplete="off">
                        </div>
                        
                        <div class="form-group">
                            <label for="min_stock">Minimal Stok:</label>
                            <input type="number" name="min_stock" id="min_stock" min="0.1" step="0.1" placeholder="Batas minimal stok" required>
                        </div>
                        
                        <button type="submit" name="add_item" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i>
                            Tambah Barang
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Use Stock -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-minus-circle"></i>
                        Penggunaan Stok
                    </h2>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-group">
                            <label for="item_id">Pilih Barang:</label>
                            <select name="item_id" id="item_id" required>
                                <option value="" disabled selected>-- Pilih Barang --</option>
                                <?php
                                $item_select = $conn->query("SELECT id, name, quantity, unit FROM items ORDER BY name ASC");
                                while ($row = $item_select->fetch_assoc()): 
                                ?>
                                    <option value="<?= $row['id'] ?>">
                                        <?= htmlspecialchars($row['name']) ?> (Stok: <?= number_format($row['quantity'], 2) ?> <?= htmlspecialchars($row['unit']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="total_used">Jumlah Dipakai:</label>
                            <input type="number" name="total_used" id="total_used" min="0.1" step="0.1" placeholder="Jumlah yang digunakan" required>
                        </div>
                        
                        <button type="submit" name="use_stock" class="btn btn-primary btn-block">
                            <i class="fas fa-check-circle"></i>
                            Simpan Penggunaan
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Items Table -->
        <div class="table-container fade-in fade-in-3">
            <div class="table-header">
                <h3>
                    <i class="fas fa-clipboard-list"></i>
                    Daftar Barang
                </h3>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Barang</th>
                        <th>Jumlah</th>
                        <th>Satuan</th>
                        <th>Minimal Stok</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    // Reset the pointer to the first row
                    $items = $conn->query("SELECT id, name, quantity, unit, min_stock FROM items ORDER BY quantity ASC");
                    
                    if($items->num_rows > 0):
                        while ($row = $items->fetch_assoc()): 
                            $is_low = $row['quantity'] <= $row['min_stock'];
                    ?>
                        <tr class="<?= $is_low ? 'low-stock' : '' ?>">
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= number_format($row['quantity'], 2) ?></td>
                            <td><?= htmlspecialchars($row['unit']) ?></td>
                            <td><?= number_format($row['min_stock'], 2) ?></td>
                            <td>
                                <?php if($is_low): ?>
                                    <span class="badge badge-danger">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Stok Rendah
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-check-circle"></i>
                                        Stok Aman
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fas fa-box-open"></i>
                                <p>Belum ada data barang. Silakan tambahkan barang baru menggunakan form di atas.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
            });
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 768 && 
                    !sidebar.contains(event.target) && 
                    !menuToggle.contains(event.target) && 
                    sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                }
            });
            
            // Mark current page as active
            const currentPage = window.location.pathname.split("/").pop();
            const links = document.querySelectorAll('.sidebar a');
            
            links.forEach(link => {
                const href = link.getAttribute('href');
                if(href === currentPage) {
                    link.classList.add('active');
                }
            });
            
            // Focus first input in the first form
            document.querySelector('form input:first-of-type').focus();
        });
    </script>
</body>
</html>