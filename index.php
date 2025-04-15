<?php
// Include koneksi database
include 'config.php';

// Handle penambahan stok
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock'])) {
    $name = trim($_POST['name']);
    $quantity = (float) $_POST['quantity'];
    $unit = trim($_POST['unit']);
    $usage_per_customer = (float) $_POST['usage_per_customer'];
    $min_stock = (int) $_POST['min_stock'];

    // Cek apakah item sudah ada
    $check = $conn->prepare("SELECT id FROM items WHERE name = ?");
    $check->bind_param("s", $name);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Barang sudah ada! Silakan update stok.');</script>";
    } else {
        // Tambah barang baru
        $stmt = $conn->prepare("INSERT INTO items (name, quantity, unit, usage_per_customer, min_stock) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sdssi", $name, $quantity, $unit, $usage_per_customer, $min_stock);
        $stmt->execute();
        echo "<script>alert('Barang berhasil ditambahkan!'); window.location.href='index.php';</script>";
    }
}

// Handle update stok
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $id = (int) $_POST['id'];
    $quantity = (float) $_POST['quantity'];

    if ($quantity > 0) {
        $stmt = $conn->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
        $stmt->bind_param("di", $quantity, $id);
        $stmt->execute();
        echo "<script>alert('Stok berhasil diperbarui!'); window.location.href='index.php';</script>";
    }
}

// Ambil data stok
$result = $conn->query("SELECT * FROM items ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Stok</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 10px; overflow: hidden; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background-color: #007bff; color: white; }
        .low-stock { background-color: #ffcccc; }
        .btn { padding: 8px 12px; background: #28a745; color: white; border: none; cursor: pointer; }
        .btn:hover { background: #218838; }
    </style>
</head>
<body>
    <h2>Manajemen Stok</h2>

    <h3>Tambah Barang</h3>
    <form method="POST">
        <input type="text" name="name" placeholder="Nama Barang" required>
        <input type="number" step="0.01" name="quantity" placeholder="Jumlah" required>
        <input type="text" name="unit" placeholder="Satuan (ml/pcs)" required>
        <input type="number" step="0.01" name="usage_per_customer" placeholder="Pemakaian per pelanggan" required>
        <input type="number" name="min_stock" placeholder="Batas Minimal Stok" required>
        <button type="submit" name="add_stock" class="btn">Tambah</button>
    </form>

    <h3>Daftar Stok</h3>
    <table>
        <thead>
            <tr>
                <th>Nama Barang</th>
                <th>Sisa Stok</th>
                <th>Satuan</th>
                <th>Pemakaian/Pelanggan</th>
                <th>Status</th>
                <th>Update Stok</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php 
                    $status = ($row['quantity'] <= $row['min_stock']) ? "Stok Hampir Habis" : "Aman";
                    $statusClass = ($row['quantity'] <= $row['min_stock']) ? "low-stock" : "";
                ?>
                <tr class="<?= $statusClass ?>">
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= number_format($row['quantity'], 2) ?></td>
                    <td><?= htmlspecialchars($row['unit']) ?></td>
                    <td><?= number_format($row['usage_per_customer'], 2) ?></td>
                    <td><?= $status ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <input type="number" step="0.01" name="quantity" placeholder="Tambah Jumlah" required>
                            <button type="submit" name="update_stock" class="btn">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
