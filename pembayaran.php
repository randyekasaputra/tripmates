<?php
session_start();
require_once '../config/database.php';

// Ambil parameter dari URL
$trip_id = isset($_GET['trip_id']) ? $_GET['trip_id'] : null;
$price = isset($_GET['price']) ? $_GET['price'] : null;

// Cek apakah trip_id dan price ada
if (!$trip_id || !$price) {
    header('Location: ../index.php');
    exit();
}

// Ambil detail trip untuk konfirmasi
$stmt = $db->prepare("SELECT destination FROM trips WHERE id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch();

if (!$trip) {
    header('Location: ../index.php');
    exit();
}

// Proses pembayaran (misalnya, simpan ke database atau lakukan integrasi dengan payment gateway)
// Di sini hanya contoh sederhana
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Simulasi proses pembayaran
    $payment_status = 'success'; // Ganti dengan logika pembayaran yang sebenarnya

    if ($payment_status == 'success') {
        // Pembayaran berhasil, lakukan tindakan yang diperlukan
        // Misalnya, simpan ke database atau kirim notifikasi
        $success_message = "Pembayaran untuk trip " . htmlspecialchars($trip['destination']) . " sebesar Rp " . number_format($price, 0, ',', '.') . " berhasil.";
    } else {
        $error_message = "Pembayaran gagal. Silakan coba lagi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - TripMates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h2 class="text-center mb-4">Pembayaran untuk Trip</h2>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($trip['destination']); ?></h5>
                <p class="card-text">Harga: Rp <?php echo number_format($price, 0, ',', '.'); ?></p>
                <form method="POST" action="">
                    <button type="submit" class="btn btn-primary">Konfirmasi Pembayaran</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 