<?php
include 'config.php';

// Ambil shift aktif terakhir
$active_shift = $conn->query("SELECT id FROM shift WHERE status = 'Aktif' ORDER BY start_time DESC LIMIT 1");
if (!$active_shift) {
    die("Query Error (Shift): " . $conn->error);
}
if ($active_shift->num_rows > 0) {
    $shift_data = $active_shift->fetch_assoc();
    $shift_id = $shift_data['id'];
} else {
    die("<script>alert('Tidak ada shift aktif. Harap buka shift terlebih dahulu.'); window.location.href='shift.php';</script>");
}

// Ambil daftar barang
$items = $conn->query("SELECT * FROM items");

// Handle transaksi baru
if (isset($_POST['submit_transaction'])) {
    $total_price = floatval($_POST['total_price']);
    $customer_count = intval($_POST['customer_count']);
    $selected_items = isset($_POST['items']) && is_array($_POST['items']) ? $_POST['items'] : [];

    if ($customer_count < 1) {
        die("<script>alert('Masukkan jumlah pelanggan yang valid.');</script>");
    }
    if (empty($selected_items)) {
        die("<script>alert('Pilih minimal 1 barang untuk transaksi.');</script>");
    }

    // Simpan transaksi ke tabel transactions
    $stmt = $conn->prepare("INSERT INTO transactions (total_price, customer_count, shift_id, transaction_date, created_at) VALUES (?, ?, ?, NOW(), NOW())");
    if (!$stmt) {
        die("Query Error (Insert transactions): " . $conn->error);
    }
    if (!$stmt->bind_param("dii", $total_price, $customer_count, $shift_id)) {
        die("Bind param error: " . $stmt->error);
    }
    if (!$stmt->execute()) {
        die("Execute error (Insert transactions): " . $stmt->error);
    }
    $transaction_id = $stmt->insert_id;

    // Proses setiap item yang dipilih
    foreach ($selected_items as $item_id => $quantity) {
        $quantity = floatval($quantity);
        if ($quantity > 0) {
            $stmt = $conn->prepare("SELECT name, quantity FROM items WHERE id = ?");
            if (!$stmt) {
                die("Query Error (Select items): " . $conn->error);
            }
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $item = $result->fetch_assoc();

            if ($item && $item['quantity'] >= $quantity) {
                // Kurangi stok barang
                $update = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE id = ?");
                if (!$update) {
                    die("Query Error (Update stock): " . $conn->error);
                }
                $update->bind_param("di", $quantity, $item_id);
                if (!$update->execute()) {
                    die("Execute error (Update stock): " . $update->error);
                }

                // Simpan ke tabel transaction_items
                $stmt = $conn->prepare("INSERT INTO transaction_items (transaction_id, item_id, quantity_used) VALUES (?, ?, ?)");
                if (!$stmt) {
                    die("Query Error (Insert transaction_items): " . $conn->error);
                }
                $stmt->bind_param("iid", $transaction_id, $item_id, $quantity);
                if (!$stmt->execute()) {
                    die("Execute error (Insert transaction_items): " . $stmt->error);
                }
            } else {
                echo "<script>alert('Stok tidak cukup untuk item ID $item_id');</script>";
            }
        }
    }

    echo "<script>alert('Transaksi berhasil!'); window.location.href='riwayat_transaksi.php';</script>";
}

// Ambil ulang data stok
$items = $conn->query("SELECT * FROM items");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi | Kasir Barbershop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        :root {
            --primary-color: #1e3a8a;
            --primary-dark: #152b64;
            --secondary-color: #f0f4ff;
            --text-dark: #333;
            --text-light: #767676;
            --danger: #ff3860;
            --success: #23d160;
            --warning: #ffdd57;
            --white: #ffffff;
            --light-bg: #f5f7fb;
        }

        body {
            background-color: #f5f7fb;
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 230px;
            height: 100vh;
            position: fixed;
            background: var(--primary-color);
            color: white;
            padding-top: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            padding: 0 20px;
            margin-bottom: 30px;
        }

        .sidebar-logo i {
            font-size: 24px;
            margin-right: 10px;
        }

        .sidebar-logo h2 {
            font-weight: 600;
            font-size: 20px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            margin: 5px 0;
            border-left: 4px solid transparent;
            transition: all 0.2s;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.7);
        }

        .menu-item i {
            margin-right: 12px;
            font-size: 18px;
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 4px solid white;
        }

        .menu-item.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 500;
            border-left: 4px solid white;
        }

        /* Content Area */
        .content {
            margin-left: 230px;
            padding: 30px;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 15px;
        }

        .page-title {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .page-title i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        /* Card Styles */
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card-header {
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }

        .card-title {
            font-size: 18px;
            color: var(--primary-color);
            margin: 0;
        }

        .card-body {
            padding: 20px;
        }

        /* Two column layout */
        .transaction-layout {
            display: flex;
            gap: 25px;
        }

        .transaction-left {
            flex: 1;
        }

        .transaction-right {
            flex: 1;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Search bar */
        .search-container {
            position: relative;
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 10px 15px;
            padding-right: 50px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
        }

        .search-button {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            width: 50px;
            background: var(--primary-color);
            border: none;
            border-radius: 0 6px 6px 0;
            color: white;
            cursor: pointer;
        }

        /* Product selection */
        .product-tabs {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .tab-button {
            padding: 8px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: var(--text-light);
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .tab-button.active {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .product-item:nth-child(even) {
            background-color: #f9f9f9;
        }

        .product-item.out-of-stock {
            background-color: #fff2f2;
        }

        .product-details {
            flex: 1;
        }

        .product-name {
            font-weight: 500;
            margin-bottom: 3px;
        }

        .product-stock {
            color: var(--text-light);
            font-size: 14px;
        }

        .product-price {
            font-weight: 500;
            color: var(--primary-color);
            margin: 0 20px;
        }

        .quantity-input {
            width: 70px;
            padding: 5px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        /* Transaction summary */
        .transaction-summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 18px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn:hover {
            background: var(--primary-dark);
        }

        .btn-block {
            width: 100%;
            padding: 12px;
        }

        .btn-process {
            background: var(--primary-color);
            margin-top: 20px;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .transaction-layout {
                flex-direction: column;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }
            .sidebar.active {
                width: 230px;
                transform: translateX(0);
            }
            .content {
                margin-left: 0;
            }
        }

        /* Mobile menu toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <i class="fas fa-cut"></i>
            <h2>Kasir Barbershop</h2>
        </div>
        
        <a href="dashboard.php" class="menu-item">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="transaksi.php" class="menu-item active">
            <i class="fas fa-cash-register"></i>
            <span>Transaksi</span>
        </a>
        
        <a href="riwayat_transaksi.php" class="menu-item">
            <i class="fas fa-history"></i>
            <span>Riwayat Transaksi</span>
        </a>
        
        <a href="stock.php" class="menu-item">
            <i class="fas fa-boxes"></i>
            <span>Stok Barang</span>
        </a>
        
        <a href="shift.php" class="menu-item">
            <i class="fas fa-clock"></i>
            <span>Shift Kerja</span>
        </a>
        
        <a href="logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>

    <div class="content">
        <div class="page-header">
            <h2 class="page-title">
                <i class="fas fa-file-invoice"></i>
                Transaksi Baru
            </h2>
        </div>

        <form method="POST">
            <div class="transaction-layout">
                <div class="transaction-left">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Informasi Transaksi</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Total Harga (Rp)</label>
                                <input type="number" name="total_price" id="total_price" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Jumlah Pelanggan</label>
                                <input type="number" name="customer_count" min="1" class="form-control" required>
                            </div>

                            <div class="transaction-summary">
                                <h4 style="margin-bottom: 15px;">Ringkasan Transaksi</h4>
                                <div id="itemSummary">
                                    <p>Belum ada item yang dipilih</p>
                                </div>
                                <div class="summary-total">
                                    <span>Total:</span>
                                    <span id="summaryTotal">Rp 0</span>
                                </div>
                            </div>

                            <button type="submit" name="submit_transaction" class="btn btn-block btn-process">
                                <i class="fas fa-check-circle"></i>
                                PROSES TRANSAKSI
                            </button>
                        </div>
                    </div>
                </div>

                <div class="transaction-right">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Pilih Produk/Layanan</h3>
                        </div>
                        <div class="card-body">
                            <div class="search-container">
                                <input type="text" class="search-input" placeholder="Cari produk atau layanan..." id="searchProduct">
                                <button type="button" class="search-button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>

                            <div class="product-tabs">
                                <button type="button" class="tab-button active">Semua</button>
                                <button type="button" class="tab-button">Lainnya</button>
                            </div>

                            <div class="product-list">
                                <?php while ($row = $items->fetch_assoc()): 
                                    $outOfStock = $row['quantity'] <= 0;
                                    $lowStock = $row['quantity'] > 0 && $row['quantity'] <= 5;
                                ?>
                                <div class="product-item <?= $outOfStock ? 'out-of-stock' : '' ?>">
                                    <div class="product-details">
                                        <div class="product-name"><?= htmlspecialchars($row['name']) ?></div>
                                        <div class="product-stock">
                                            Sisa Stok: 
                                            <span <?= $lowStock ? 'style="color: #ff3860;"' : '' ?>>
                                                <?= number_format($row['quantity'], 2) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="product-price" data-price="<?= isset($row['total_price']) ? $row['total_price'] : 0 ?>">
                                         Rp <?= isset($row['total_price']) ? number_format($row['total_price'], 2) : '0.00' ?>
                                    </div>

                                    <input 
                                        type="number" 
                                        class="quantity-input" 
                                        name="items[<?= $row['id'] ?>]" 
                                        min="0" 
                                        max="<?= $row['quantity'] ?>" 
                                        step="0.01" 
                                        value="0"
                                        <?= $outOfStock ? 'disabled' : '' ?>
                                    >
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>




    <script>
        

     
document.addEventListener("DOMContentLoaded", function () {
    // Event listener untuk update total harga saat input jumlah berubah
    document.querySelectorAll(".product-quantity").forEach(input => {
        input.addEventListener("input", updateSummary);
        input.addEventListener("change", updateSummary);
    });

    

    

    function updateSummary() {
        let total = 0;
        let summaryHTML = "";
        let hasItems = false;

        document.querySelectorAll(".product-item").forEach(item => {
            let priceElement = item.querySelector(".product-price");
            let price = parseFloat(priceElement?.dataset.price) || 0;
            let qtyElement = item.querySelector(".product-quantity");
            let quantity = parseInt(qtyElement?.value) || 0;

            if (quantity > 0) {
                hasItems = true;
                let productName = item.querySelector(".product-name").textContent;
                let itemTotal = price * quantity;
                total += itemTotal;

                summaryHTML += `
                    <div class="summary-item">
                        <span>${productName} (${quantity})</span>
                        <span>Rp ${itemTotal.toLocaleString("id-ID")}</span>
                    </div>`;
            }
        });

        // Jika tidak ada item yang dipilih
        if (!hasItems) {
            summaryHTML = '<p>Belum ada item yang dipilih</p>';
        }

        // Update ringkasan transaksi
        document.getElementById("itemSummary").innerHTML = summaryHTML;
        
        // Update total harga di tampilan
        document.getElementById("total-price").textContent = "Rp " + total.toLocaleString("id-ID");

        // Update total harga di input hidden (misalnya untuk proses checkout)
        let totalInput = document.getElementById("total_price");
        if (totalInput) totalInput.value = total;
        
        // Log total untuk debugging
        console.log("Total Price: ", total);
    }

    // Jalankan updateSummary saat halaman dimuat
    updateSummary();

    // Mobile menu toggle
    document.getElementById("mobileMenuToggle")?.addEventListener("click", function () {
        document.getElementById("sidebar")?.classList.toggle("active");
    });

    // Pencarian produk
    document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchProduct");

    if (!searchInput) {
        console.error("Elemen #searchProduct tidak ditemukan!");
        return;
    }

    searchInput.addEventListener("input", function () {
        const searchValue = this.value.toLowerCase().trim();

        document.querySelectorAll(".product-item").forEach(item => {
            const productNameElement = item.querySelector(".product-name");

            if (!productNameElement) {
                console.warn("Elemen .product-name tidak ditemukan dalam:", item);
                return;
            }

            const productName = productNameElement.textContent.toLowerCase();
            const match = productName.includes(searchValue);

            item.style.display = match ? "flex" : "none"; // Sesuaikan jika pakai flex/grid
        });
    });
});

    // Tab switching
    document.querySelectorAll(".tab-button").forEach(button => {
        button.addEventListener("click", function () {
            document.querySelectorAll(".tab-button").forEach(btn => btn.classList.remove("active"));
            this.classList.add("active");
        });
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const totalPriceInput = document.getElementById("total_price");
    const summaryTotal = document.getElementById("summaryTotal");

    if (totalPriceInput && summaryTotal) {
        totalPriceInput.addEventListener("input", function () {
            let total = parseFloat(this.value) || 0;
            summaryTotal.textContent = "Rp " + total.toLocaleString("id-ID");
        });
    }
});



    </script>
</body>
</html>

