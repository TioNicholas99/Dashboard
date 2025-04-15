<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $service_id = $_POST['service_id']; // ID layanan yang dipilih
    $customer_count = $_POST['customer_count']; // Jumlah pelanggan

    // Simpan transaksi
    $conn->query("INSERT INTO transactions (service_id, customer_count, created_at) VALUES ('$service_id', '$customer_count', NOW())");
    $transaction_id = $conn->insert_id;

    // Ambil bahan yang digunakan untuk layanan ini
    $items = $conn->query("SELECT item_id, usage_per_service FROM service_items WHERE service_id = '$service_id'");

    while ($row = $items->fetch_assoc()) {
        $item_id = $row['item_id'];
        $usage = $row['usage_per_service'] * $customer_count;

        // Kurangi stok bahan
        $conn->query("UPDATE items SET quantity = quantity - $usage WHERE id = '$item_id'");

        // Simpan ke histori penggunaan stok
        $conn->query("
            INSERT INTO stock_usage (item_id, usage_date, total_used)
            VALUES ('$item_id', CURDATE(), '$usage')
            ON DUPLICATE KEY UPDATE total_used = total_used + '$usage'
        ");
    }

    echo "Transaksi berhasil! Stok diperbarui dan dicatat dalam histori.";
}
?>
