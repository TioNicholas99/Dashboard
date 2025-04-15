<?php
require('fpdf/fpdf.php');
include 'config.php';

// Ambil data transaksi berdasarkan tanggal
$filter_date = $_GET['date'] ?? date("Y-m-d");
$transactions = $conn->query("
    SELECT t.id, t.total_price, t.created_at, s.cashier_name
    FROM transactions t
    LEFT JOIN shifts s ON DATE(t.created_at) = DATE(s.start_time)
    WHERE DATE(t.created_at) = '$filter_date'
    ORDER BY t.created_at DESC
");

// Ambil total transaksi harian
$total_day = $conn->query("
    SELECT SUM(total_price) AS total FROM transactions 
    WHERE DATE(created_at) = '$filter_date'
")->fetch_assoc()['total'] ?? 0;

// Buat PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(190, 10, "Laporan Transaksi - $filter_date", 1, 1, 'C');

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 10, 'ID Transaksi', 1);
$pdf->Cell(50, 10, 'Kasir', 1);
$pdf->Cell(50, 10, 'Total Harga', 1);
$pdf->Cell(50, 10, 'Waktu', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);
while ($row = $transactions->fetch_assoc()) {
    $pdf->Cell(40, 10, "#".$row['id'], 1);
    $pdf->Cell(50, 10, $row['cashier_name'] ?? 'Tidak Diketahui', 1);
    $pdf->Cell(50, 10, "Rp " . number_format($row['total_price'], 0, ',', '.'), 1);
    $pdf->Cell(50, 10, $row['created_at'], 1);
    $pdf->Ln();
}

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(90, 10, "Total Pendapatan", 1);
$pdf->Cell(100, 10, "Rp " . number_format($total_day, 0, ',', '.'), 1);
$pdf->Output();
