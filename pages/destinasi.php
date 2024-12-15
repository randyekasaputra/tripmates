<?php
session_start();
require_once '../config/database.php';

// Ambil ID trip dari parameter URL
$trip_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$trip_id) {
    header('Location: ../index.php');
    exit();
}

// Ambil detail trip
$stmt = $db->prepare("
    SELECT t.*, u.username, u.profile_image as user_image,
           (SELECT COUNT(*) FROM trip_participants WHERE trip_id = t.id AND status = 'approved') as current_participants
    FROM trips t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = ?
");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch();

if (!$trip) {
    header('Location: ../index.php'); 
    exit();
}

// Cek apakah user sudah bergabung dengan trip ini
$is_joined = false;
$join_status = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("
        SELECT status FROM trip_participants 
        WHERE trip_id = ? AND user_id = ?
    ");
    $stmt->execute([$trip_id, $_SESSION['user_id']]);
    $participant = $stmt->fetch();
    if ($participant) {
        $is_joined = true;
        $join_status = $participant['status'];
    }
}

// Handle permintaan bergabung
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    // Cek apakah masih ada slot tersedia
    if ($trip['current_participants'] >= $trip['max_participants']) {
        $error = "Maaf, trip ini sudah penuh!";
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO trip_participants (trip_id, user_id, status) 
                VALUES (?, ?, 'pending')
            ");
            $stmt->execute([$trip_id, $_SESSION['user_id']]);
            
            $success = "Permintaan bergabung berhasil dikirim. Menunggu persetujuan pembuat trip.";
            $is_joined = true;
            $join_status = 'pending';
        } catch(PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Tambahkan setelah mengambil detail trip
if (isset($_SESSION['user_id'])) {
    // Cek status pembayaran
    $stmt = $db->prepare("
        SELECT cp.* 
        FROM collection_payments cp
        WHERE cp.trip_id = ? AND cp.user_id = ?
        ORDER BY cp.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$trip_id, $_SESSION['user_id']]);
    $payment = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($trip['destination']); ?> - TripMates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container my-5">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <img src="../assets/images/trips/<?php echo htmlspecialchars($trip['image'] ?: 'default-trip.jpg'); ?>" 
                     class="img-fluid rounded mb-4" 
                     alt="<?php echo htmlspecialchars($trip['destination']); ?>">
                
                <h1 class="mb-4"><?php echo htmlspecialchars($trip['destination']); ?></h1>
                
                <div class="d-flex align-items-center mb-4">
                    <img src="../assets/images/profiles/<?php echo htmlspecialchars($trip['user_image'] ?: 'default-avatar.jpg'); ?>" 
                         class="rounded-circle me-2" 
                         width="40" 
                         alt="Profile">
                    <div>
                        <p class="mb-0">Dibuat oleh: @<?php echo htmlspecialchars($trip['username']); ?></p>
                        <small class="text-muted">
                            <?php echo date('d M Y', strtotime($trip['created_at'])); ?>
                        </small>
                    </div>
                </div>

                <div class="mb-4">
                    <h5>Deskripsi:</h5>
                    <p><?php echo nl2br(htmlspecialchars($trip['description'])); ?></p>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Informasi Trip</h5>
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <i class="bi bi-calendar me-2"></i>
                                <strong>Tanggal:</strong><br>
                                <?php echo date('d M Y', strtotime($trip['start_date'])); ?> - 
                                <?php echo date('d M Y', strtotime($trip['end_date'])); ?>
                            </li>
                            <li class="mb-3">
                                <i class="bi bi-people me-2"></i>
                                <strong>Peserta:</strong><br>
                                <?php echo $trip['current_participants']; ?>/<?php echo $trip['max_participants']; ?> orang
                            </li>
                            <li class="mb-4">
                                <i class="bi bi-cash me-2"></i>
                                <strong>Harga:</strong><br>
                                Rp <?php echo number_format($trip['price'], 0, ',', '.'); ?> per orang
                            </li>
                        </ul>
                        <br>

                        <?php if(!isset($_SESSION['user_id'])): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                Silakan <a href="login.php" class="alert-link">login</a> terlebih dahulu untuk bergabung dengan trip ini.
                            </div>
                        <?php elseif($is_joined): ?>
                            <?php if(!$payment): ?>
                                <!-- Belum bayar -->
                                <div class="alert alert-warning mb-3">
                                    <h6 class="alert-heading">
                                        <i class="bi bi-exclamation-circle"></i> Pembayaran Diperlukan
                                    </h6>
                                    <p class="mb-0">Silakan lakukan pembayaran untuk bergabung dengan trip ini.</p>
                                </div>
                                <a href="pembayaran.php?trip_id=<?php echo $trip_id; ?>&price=<?php echo $trip['price']; ?>" 
                                   class="btn btn-primary w-100">
                                    <i class="bi bi-credit-card"></i> Lakukan Pembayaran
                                </a>
                            <?php else: ?>
                                <?php
                                $status_class = 
                                    $payment['status'] == 'verified' ? 'success' : 
                                    ($payment['status'] == 'rejected' ? 'danger' : 'warning');
                                $status_text = 
                                    $payment['status'] == 'verified' ? 'Pembayaran Terverifikasi' : 
                                    ($payment['status'] == 'rejected' ? 'Pembayaran Ditolak' : 'Menunggu Verifikasi');
                                $status_icon = 
                                    $payment['status'] == 'verified' ? 'check-circle' : 
                                    ($payment['status'] == 'rejected' ? 'x-circle' : 'clock');
                                ?>
                                <div class="alert alert-<?php echo $status_class; ?> mb-3">
                                    <h6 class="alert-heading">
                                        <i class="bi bi-<?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                                    </h6>
                                    <?php if($payment['status'] == 'verified'): ?>
                                        <p class="mb-0">Anda telah resmi bergabung dengan trip ini!</p>
                                        <!-- Tampilkan informasi grup WhatsApp dll -->
                                    <?php elseif($payment['status'] == 'rejected'): ?>
                                        <p class="mb-0">
                                            <?php echo $payment['notes'] ? htmlspecialchars($payment['notes']) : 'Silakan upload ulang bukti pembayaran yang valid.'; ?>
                                        </p>
                                        <div class="mt-2">
                                            <a href="pembayaran.php?trip_id=<?php echo $trip_id; ?>&price=<?php echo $trip['price']; ?>" 
                                               class="btn btn-danger btn-sm">
                                                <i class="bi bi-upload"></i> Upload Ulang Bukti Pembayaran
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <p class="mb-0">Pembayaran Anda sedang diverifikasi oleh admin.</p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php elseif($trip['current_participants'] >= $trip['max_participants']): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-circle"></i> Trip ini sudah penuh
                            </div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-person-plus"></i> Gabung Trip
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Informasi Tambahan -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Ketentuan Trip</h5>
                        <ul class="small">
                            <li>Pembayaran dilakukan setelah permintaan disetujui</li>
                            <li>Konfirmasi keikutsertaan maksimal H-7 trip</li>
                            <li>Pembatalan H-3 tidak mendapat refund</li>
                            <li>Perubahan jadwal akan diinformasikan</li>
                        </ul>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 