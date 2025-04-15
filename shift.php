<?php
// Database connection configuration
$config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'barbershop_db'
];

// Establish database connection
$conn = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set default timezone
date_default_timezone_set('Asia/Jakarta');

/**
 * Get all shifts from database
 * 
 * @param mysqli $conn Database connection
 * @return array Array of shifts
 */
function getAllShifts($conn) {
    $sql = "SELECT * FROM shift ORDER BY start_time DESC";
    $result = $conn->query($sql);
    return $result && $result->num_rows > 0 ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get active shifts from database
 * 
 * @param mysqli $conn Database connection
 * @return array Array of active shifts
 */
function getActiveShifts($conn) {
    $sql = "SELECT * FROM shift WHERE status = 'Aktif' ORDER BY start_time DESC";
    $result = $conn->query($sql);
    return $result && $result->num_rows > 0 ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get completed shifts from database
 * 
 * @param mysqli $conn Database connection
 * @return array Array of completed shifts
 */
function getCompletedShifts($conn) {
    $sql = "SELECT * FROM shift WHERE status = 'Selesai' ORDER BY end_time DESC";
    $result = $conn->query($sql);
    return $result && $result->num_rows > 0 ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Format date to readable format
 * 
 * @param string $dateTime MySQL datetime string
 * @return string Formatted datetime
 */
function formatDateTime($dateTime) {
    return date('d-m-Y H:i:s', strtotime($dateTime));
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Start new shift
    if (isset($_POST['action']) && $_POST['action'] === 'start_shift') {
        $cashierId = $conn->real_escape_string($_POST['cashier_id']);
        $cashierName = $conn->real_escape_string($_POST['cashier_name']);
        $startTime = date('Y-m-d H:i:s');
        
        // Check if cashier already has an active shift
        $checkSql = "SELECT * FROM shift WHERE cashier_id = '$cashierId' AND status = 'Aktif'";
        $checkResult = $conn->query($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Kasir ini sudah memiliki shift aktif!']);
            exit;
        }
        
        $sql = "INSERT INTO shift (cashier_id, cashier_name, start_time, status) 
                VALUES ('$cashierId', '$cashierName', '$startTime', 'Aktif')";
        
        if ($conn->query($sql) === TRUE) {
            echo json_encode(['success' => true, 'message' => 'Shift berhasil dimulai']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
        }
        exit;
    }
    
    // End shift
    if (isset($_POST['action']) && $_POST['action'] === 'end_shift') {
        $shiftId = (int)$_POST['shift_id'];
        $endTime = date('Y-m-d H:i:s');
        
        $sql = "UPDATE shift SET end_time = '$endTime', status = 'Selesai' WHERE id = $shiftId";
        
        if ($conn->query($sql) === TRUE) {
            echo json_encode(['success' => true, 'message' => 'Shift berhasil diakhiri']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
        }
        exit;
    }
}

// Get shift data for display
$activeShifts = getActiveShifts($conn);
$completedShifts = getCompletedShifts($conn);

// Pagination for completed shifts
$shiftsPerPage = 10;
$totalCompletedShifts = count($completedShifts);
$totalPages = ceil($totalCompletedShifts / $shiftsPerPage);

$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $shiftsPerPage;

$paginatedShifts = array_slice($completedShifts, $offset, $shiftsPerPage);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Shift Kasir - Barbershop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --primary-dark: #1a252f;
            --secondary: #3498db;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --text: #333;
            --text-light: #7f8c8d;
            --radius-sm: 4px;
            --radius-md: 8px;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: var(--text);
            line-height: 1.6;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            height: 100vh;
            position: fixed;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding-top: 20px;
            z-index: 100;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            transition: all 0.3s ease;
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
        
        .sidebar a {
            display: flex;
            align-items: center;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 12px 20px;
            margin: 2px 8px;
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
        }
        
        .sidebar a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar a.active {
            background: rgba(52,152,219,0.6);
            color: white;
            font-weight: 500;
        }
        
        .sidebar i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .content {
            margin-left: 280px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .container {
    max-width: 12000px; /* Lebarkan container */
    margin: 0 auto;
    background-color: white;
    padding: 30px;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow);
}
        
        .page-header {
            margin-bottom: 30px;
            border-bottom: 2px solid var(--light);
            padding-bottom: 15px;
        }
        
        .page-header h1 {
            color: var(--primary);
            font-size: 28px;
            font-weight: 600;
        }
        
        .section {
            margin-bottom: 30px;
            position: relative;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .section-header h2 {
            font-size: 20px;
            color: var(--primary);
            font-weight: 600;
        }
        
        .card {
            background-color: white;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--secondary);
        }
        
        /* Forms */
        .form-card {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            border-radius: var(--radius-md);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .form-title {
            font-size: 20px;
            margin-bottom: 20px;
            color: var(--primary);
            font-weight: 600;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text);
        }
        
        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: var(--radius-sm);
            font-size: 15px;
            transition: all 0.2s ease;
        }
        
        input:focus, select:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 2px rgba(52,152,219,0.2);
            outline: none;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 20px;
            background-color: var(--secondary);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            text-align: center;
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background-color: var(--success);
        }
        
        .btn-success:hover {
            background-color: #27ae60;
        }
        
        .btn-danger {
            background-color: var(--danger);
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .btn-full {
            width: 100%;
        }
        
        /* Tables */
        .table-container {
            overflow-x: auto;
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f8f8f8;
            font-weight: 600;
            color: var(--primary);
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .active-shift {
            background-color: rgba(46, 204, 113, 0.1);
        }
        
        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: rgba(46, 204, 113, 0.2);
            color: #27ae60;
        }
        
        .status-ended {
            background-color: rgba(149, 165, 166, 0.2);
            color: #7f8c8d;
        }
        
        /* Alerts */
        .alert-container {
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: var(--radius-sm);
            margin-bottom: 15px;
            position: relative;
            animation: fadeIn 0.3s ease;
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.2);
            border-left: 4px solid var(--success);
            color: #27ae60;
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.2);
            border-left: 4px solid var(--danger);
            color: #c0392b;
        }
        
        .alert-close {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            font-size: 18px;
            color: rgba(0,0,0,0.4);
        }
        
        /* Loading indicator */
        .loading {
            display: none;
            text-align: center;
            padding: 15px;
            background-color: rgba(0,0,0,0.05);
            border-radius: var(--radius-sm);
            margin: 20px 0;
        }
        
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0,0,0,0.1);
            border-radius: 50%;
            border-top-color: var(--secondary);
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
            vertical-align: middle;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--text-light);
            font-style: italic;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination-item {
            display: inline-block;
            padding: 8px 12px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .pagination-item:hover, .pagination-item.active {
            background-color: var(--secondary);
            color: white;
            border-color: var(--secondary);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Mobile responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .content {
                margin-left: 0;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .toggle-sidebar {
                display: block;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2><i class="fas fa-cut"></i> Barbershop</h2>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="transaksi.php"><i class="fas fa-cash-register"></i> Transaksi</a>
        <a href="riwayat_transaksi.php"><i class="fas fa-history"></i> Riwayat Transaksi</a>
        <a href="input_stock.php"><i class="fas fa-plus-circle"></i> Input</a>
        <a href="stock.php"><i class="fas fa-box"></i> Stok Barang</a>
        <a href="laporan.php"><i class="fas fa-chart-bar"></i> Laporan</a>
        <a href="laporan_stock.php"><i class="fas fa-clipboard-list"></i> Laporan Stok</a>
        <a href="laporan_shift.php"><i class="fas fa-user-clock"></i> Laporan Shift</a>
        <a href="export_pdf.php"><i class="fas fa-file-pdf"></i> Export PDF</a>
        <a href="exportlaporan.php"><i class="fas fa-file-export"></i> Export Laporan</a>
        <a href="shift.php" class="active"><i class="fas fa-exchange-alt"></i> Shift Kerja</a>
        <a href="proses_transaksi.php"><i class="fas fa-shopping-cart"></i> Proses Transaksi</a>
    </div>

    <!-- Main content -->
    <div class="content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-exchange-alt"></i> Sistem Shift Kasir</h1>
            </div>
            
            <div id="alert-container" class="alert-container"></div>
            
            <div class="section">
                <div class="form-card">
                    <h2 class="form-title"><i class="fas fa-play-circle"></i> Mulai Shift Baru</h2>
                    <form id="start-shift-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cashier-id">ID Kasir:</label>
                                <input type="text" id="cashier-id" name="cashier_id" placeholder="Masukkan ID Kasir" required>
                            </div>
                            <div class="form-group">
                                <label for="cashier-name">Nama Kasir:</label>
                                <input type="text" id="cashier-name" name="cashier_name" placeholder="Masukkan Nama Kasir" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success btn-full">
                            <i class="fas fa-play"></i> Mulai Shift
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="section">
                <div class="section-header">
                    <h2><i class="fas fa-user-clock"></i> Daftar Shift Aktif</h2>
                </div>
                <div class="table-container">
                    <table id="active-shifts">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ID Kasir</th>
                                <th>Nama Kasir</th>
                                <th>Waktu Mulai</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($activeShifts) > 0): ?>
                                <?php foreach ($activeShifts as $shift): ?>
                                    <tr class="active-shift">
                                        <td><?php echo $shift['id']; ?></td>
                                        <td><?php echo $shift['cashier_id']; ?></td>
                                        <td><?php echo $shift['cashier_name']; ?></td>
                                        <td><?php echo formatDateTime($shift['start_time']); ?></td>
                                        <td><span class="status status-active"><?php echo $shift['status']; ?></span></td>
                                        <td>
                                            <button class="btn btn-danger end-shift" data-id="<?php echo $shift['id']; ?>">
                                                <i class="fas fa-stop-circle"></i> Akhiri
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <i class="fas fa-info-circle"></i> Tidak ada shift aktif saat ini
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="section">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Riwayat Shift</h2>
                </div>
                <div class="table-container">
                    <table id="shift-history">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ID Kasir</th>
                                <th>Nama Kasir</th>
                                <th>Waktu Mulai</th>
                                <th>Waktu Selesai</th>
                                <th>Durasi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($paginatedShifts) > 0): ?>
                                <?php foreach ($paginatedShifts as $shift): ?>
                                    <?php 
                                        // Calculate shift duration
                                        $startTime = new DateTime($shift['start_time']);
                                        $endTime = new DateTime($shift['end_time']);
                                        $duration = $startTime->diff($endTime);
                                        $durationStr = sprintf(
                                            '%02d:%02d:%02d', 
                                            $duration->h + ($duration->days * 24), 
                                            $duration->i, 
                                            $duration->s
                                        );
                                    ?>
                                    <tr>
                                        <td><?php echo $shift['id']; ?></td>
                                        <td><?php echo $shift['cashier_id']; ?></td>
                                        <td><?php echo $shift['cashier_name']; ?></td>
                                        <td><?php echo formatDateTime($shift['start_time']); ?></td>
                                        <td><?php echo formatDateTime($shift['end_time']); ?></td>
                                        <td><?php echo $durationStr; ?></td>
                                        <td><span class="status status-ended"><?php echo $shift['status']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        <i class="fas fa-info-circle"></i> Belum ada riwayat shift
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?php echo $currentPage - 1; ?>" class="pagination-item">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="pagination-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?php echo $currentPage + 1; ?>" class="pagination-item">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div id="loading" class="loading">
                <div class="spinner"></div> Memproses...
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Function to show alerts
            function showAlert(message, type) {
                const alertContainer = document.getElementById('alert-container');
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type}`;
                alertDiv.innerHTML = `
                    ${message}
                    <span class="alert-close">&times;</span>
                `;
                
                alertContainer.innerHTML = '';
                alertContainer.appendChild(alertDiv);
                
                // Add event listener to close button
                const closeBtn = alertDiv.querySelector('.alert-close');
                closeBtn.addEventListener('click', function() {
                    alertDiv.remove();
                });
                
                // Auto hide after 5 seconds
                setTimeout(function() {
                    alertDiv.style.opacity = '0';
                    alertDiv.style.transform = 'translateY(-10px)';
                    alertDiv.style.transition = 'all 0.3s ease';
                    setTimeout(() => alertDiv.remove(), 300);
                }, 5000);
            }
            
            // Handle start shift form submission
            document.getElementById('start-shift-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const cashierId = document.getElementById('cashier-id').value.trim();
                const cashierName = document.getElementById('cashier-name').value.trim();
                
                if (!cashierId || !cashierName) {
                    showAlert('<i class="fas fa-exclamation-circle"></i> ID dan Nama Kasir harus diisi!', 'danger');
                    return;
                }
                
                document.getElementById('loading').style.display = 'block';
                
                // Send data using fetch API
                const formData = new FormData();
                formData.append('action', 'start_shift');
                formData.append('cashier_id', cashierId);
                formData.append('cashier_name', cashierName);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading').style.display = 'none';
                    
                    if (data.success) {
                        showAlert('<i class="fas fa-check-circle"></i> ' + data.message, 'success');
                        // Reset form
                        document.getElementById('start-shift-form').reset();
                        // Refresh page after a brief delay
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showAlert('<i class="fas fa-exclamation-circle"></i> ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    document.getElementById('loading').style.display = 'none';
                    showAlert('<i class="fas fa-exclamation-triangle"></i> Terjadi kesalahan: ' + error, 'danger');
                });
            });
            
            // Handle end shift button clicks
            const endShiftButtons = document.querySelectorAll('.end-shift');
            endShiftButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const shiftId = this.getAttribute('data-id');
                    
                    if (confirm('Apakah Anda yakin ingin mengakhiri shift ini?')) {
                        document.getElementById('loading').style.display = 'block';
                        
                        // Send data using fetch API
                        const formData = new FormData();
                        formData.append('action', 'end_shift');
                        formData.append('shift_id', shiftId);
                        
                        fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            document.getElementById('loading').style.display = 'none';
                            
                            if (data.success) {
                                showAlert('<i class="fas fa-check-circle"></i> ' + data.message, 'success');
                                // Refresh page after a brief delay
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                showAlert('<i class="fas fa-exclamation-circle"></i> ' + data.message, 'danger');
                            }
                        })
                        .catch(error => {
                            document.getElementById('loading').style.display = 'none';
                            showAlert('<i class="fas fa-exclamation-triangle"></i> Terjadi kesalahan: ' + error, 'danger');
                        });
                    }
                });
            });
            
            // Toggle sidebar on mobile
            const toggleSidebar = document.createElement('button');
            toggleSidebar.className = 'toggle-sidebar';
            toggleSidebar.innerHTML = '<i class="fas fa-bars"></i>';
            toggleSidebar.style.position = 'fixed';
toggleSidebar.style.top = '10px';
toggleSidebar.style.left = '10px';
toggleSidebar.style.zIndex = '101';
toggleSidebar.style.padding = '10px';
toggleSidebar.style.backgroundColor = 'var(--primary)';
toggleSidebar.style.color = 'white';
toggleSidebar.style.border = 'none';
toggleSidebar.style.borderRadius = 'var(--radius-sm)';
toggleSidebar.style.cursor = 'pointer';
toggleSidebar.style.display = 'none';

document.body.appendChild(toggleSidebar);

// Show toggle button on mobile
if (window.innerWidth <= 992) {
    toggleSidebar.style.display = 'block';
}

// Toggle sidebar on button click
toggleSidebar.addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('active');
});

// Hide sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    if (window.innerWidth <= 992 && 
        !event.target.closest('.sidebar') && 
        !event.target.closest('.toggle-sidebar')) {
        document.querySelector('.sidebar').classList.remove('active');
    }
});

// Adjust on window resize
window.addEventListener('resize', function() {
    if (window.innerWidth <= 992) {
        toggleSidebar.style.display = 'block';
    } else {
        toggleSidebar.style.display = 'none';
        document.querySelector('.sidebar').classList.remove('active');
    }
});
});
</script>
</body>
</html>