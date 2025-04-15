<?php
// Koneksi ke database
$host = "localhost"; // Sesuaikan dengan host database
$user = "root"; // Sesuaikan dengan username database
$password = ""; // Sesuaikan dengan password database
$database = "barbershop_db"; // Sesuaikan dengan nama database

$conn = new mysqli($host, $user, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Tambah Barang Baru
if (isset($_POST['add_item'])) {
    $name = $_POST['name'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $min_stock = $_POST['min_stock'];

    $stmt = $conn->prepare("INSERT INTO items (name, quantity, unit, min_stock) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdss", $name, $quantity, $unit, $min_stock);
    
    if ($stmt->execute()) {
        echo "<script>alert('✅ Barang baru berhasil ditambahkan!'); window.location.href='inventory.php';</script>";
    } else {
        echo "<script>alert('⚠️ Gagal menambahkan barang.');</script>";
    }
}

// Update Stok Barang
if (isset($_POST['update_stock'])) {
    $item_id = $_POST['item_id'];
    $new_quantity = $_POST['new_quantity'];

    $stmt = $conn->prepare("UPDATE items SET quantity = ? WHERE id = ?");
    $stmt->bind_param("di", $new_quantity, $item_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('✅ Stok berhasil diperbarui!'); window.location.href='inventory.php';</script>";
    } else {
        echo "<script>alert('⚠️ Gagal memperbarui stok.');</script>";
    }
}

// Ambil daftar barang
$items = $conn->query("SELECT id, name, quantity, min_stock, unit FROM items ORDER BY quantity ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Stok Barang | Kasir Barbershop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Base styles */
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
            --light: #f3f4f6;
            --dark: #1f2937;
            --gray: #9ca3af;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9fafb;
            color: #333;
            line-height: 1.6;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: var(--primary);
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding-top: 20px;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 15px;
            font-weight: 600;
            font-size: 1.75rem;
        }
        
        .sidebar a {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 12px 25px;
            margin: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .sidebar a i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar a.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 600;
        }
        
        /* Content */
        .content {
            margin-left: 280px;
            padding: 30px;
            transition: all 0.3s ease;
        }
        
        h2, h3 {
            margin-bottom: 20px;
            color: var(--dark);
            font-weight: 600;
        }
        
        h2 {
            font-size: 1.75rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }
        
        h3 {
            font-size: 1.35rem;
            margin-top: 30px;
        }
        
        /* Card styles */
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        /* Form styles */
        .form-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            width: 100%;
            transition: all 0.3s;
        }
        
        button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-sm {
            padding: 8px 12px;
            font-size: 0.9rem;
        }
        
        /* Table styles */
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            background: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background: #f9fafb;
            font-weight: 600;
            color: var(--dark);
        }
        
        tbody tr:hover {
            background-color: #f9fafb;
        }
        
        tr.low-stock {
            background-color: rgba(239, 68, 68, 0.1);
        }
        
        tr.low-stock:hover {
            background-color: rgba(239, 68, 68, 0.15);
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-low {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--danger);
        }
        
        .status-ok {
            background-color: rgba(16, 185, 129, 0.15);
            color: var(--success);
        }
        
        .update-form {
            display: flex;
            gap: 8px;
        }
        
        .update-form input {
            width: 100px;
        }
        
        .update-form button {
            white-space: nowrap;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 240px;
            }
            
            .content {
                margin-left: 240px;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow-x: hidden;
            }
            
            .sidebar h2 {
                display: none;
            }
            
            .sidebar a {
                padding: 15px;
                display: flex;
                justify-content: center;
            }
            
            .sidebar a i {
                margin-right: 0;
                font-size: 1.5rem;
            }
            
            .sidebar a span {
                display: none;
            }
            
            .content {
                margin-left: 70px;
                padding: 20px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 10px;
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .sidebar h2 {
                width: 100%;
                margin-bottom: 10px;
                display: block;
            }
            
            .sidebar a {
                width: auto;
                margin: 5px;
                padding: 10px 15px;
            }
            
            .sidebar a i {
                margin-right: 10px;
                font-size: 1rem;
            }
            
            .sidebar a span {
                display: inline;
            }
            
            .content {
                margin-left: 0;
                padding: 15px;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            h3 {
                font-size: 1.2rem;
            }
            
            .update-form {
                flex-direction: column;
            }
            
            .update-form input {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Kasir Barbershop</h2>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <a href="shifts.php"><i class="fas fa-user-clock"></i> <span>Shift Kasir</span></a>
        <a href="transactions.php"><i class="fas fa-cash-register"></i> <span>Transaksi</span></a>
        <a href="inventory.php" class="active"><i class="fas fa-boxes"></i> <span>Stok Barang</span></a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Laporan</span></a>
    </div>

    <div class="content">
        <h2><i class="fas fa-boxes"></i> Manajemen Stok Barang</h2>
        
        <h3><i class="fas fa-plus-circle"></i> Tambah Barang Baru</h3>
        <div class="form-container">
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Nama Barang:</label>
                        <input type="text" id="name" name="name" placeholder="Misalnya: Pomade, Wax, dll" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Stok Awal:</label>
                        <input type="number" id="quantity" name="quantity" min="0.1" step="0.1" placeholder="Jumlah stok awal" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="unit">Satuan:</label>
                        <input type="text" id="unit" name="unit" placeholder="Misalnya: botol, pcs, kg, dll" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="min_stock">Minimal Stok:</label>
                        <input type="number" id="min_stock" name="min_stock" min="0.1" step="0.1" placeholder="Batas minimal stok" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_item"><i class="fas fa-save"></i> Tambah Barang</button>
                    </div>
                </div>
            </form>
        </div>

        <h3><i class="fas fa-clipboard-list"></i> Daftar Barang</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Barang</th>
                        <th>Stok</th>
                        <th>Satuan</th>
                        <th>Minimal Stok</th>
                        <th>Status</th>
                        <th>Update Stok</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($items->num_rows > 0) {
                        while ($row = $items->fetch_assoc()): 
                            $is_low = $row['quantity'] <= $row['min_stock'];
                    ?>
                        <tr class="<?= $is_low ? 'low-stock' : '' ?>">
                            <td>#<?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= number_format($row['quantity'], 2) ?></td>
                            <td><?= htmlspecialchars($row['unit']) ?></td>
                            <td><?= number_format($row['min_stock'], 2) ?></td>
                            <td>
                                <?php if($is_low): ?>
                                    <span class="status-badge status-low">
                                        <i class="fas fa-exclamation-triangle"></i> Stok Rendah
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-ok">
                                        <i class="fas fa-check-circle"></i> Tersedia
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="update-form">
                                    <input type="hidden" name="item_id" value="<?= $row['id'] ?>">
                                    <input type="number" name="new_quantity" min="0" step="0.1" placeholder="Stok Baru" required>
                                    <button type="submit" name="update_stock" class="btn-sm">
                                        <i class="fas fa-sync-alt"></i> Update
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    } else {
                    ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding: 30px;">
                                <i class="fas fa-box-open" style="font-size: 3rem; color: #ccc; display: block; margin-bottom: 10px;"></i>
                                Belum ada data barang. Silakan tambahkan barang baru.
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Mark current page as active
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split("/").pop();
            const links = document.querySelectorAll('.sidebar a');
            
            links.forEach(link => {
                const href = link.getAttribute('href');
                if(href === currentPage) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>