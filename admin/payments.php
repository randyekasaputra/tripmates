<?php
session_start();
require_once '../config/database.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit();
}

// Handle verifikasi/penolakan pembayaran
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['payment_id'])) {
    $payment_id = $_POST['payment_id'];
    $action = $_POST['action'];
    $notes = $_POST['notes'] ?? '';
    $status = ($action === 'verify') ? 'verified' : 'rejected';
    
    try {
        // Update status pembayaran
        $stmt = $db->prepare("
            UPDATE collection_payments 
            SET status = ?, notes = ? 
            WHERE id = ?
        ");
        $stmt->execute([$status, $notes, $payment_id]);

        // Catat history pembayaran
        $stmt = $db->prepare("
            INSERT INTO payment_history (collection_payment_id, status, notes, created_by) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$payment_id, $status, $notes, $_SESSION['user_id']]);

        $_SESSION['success'] = "Status pembayaran berhasil diperbarui.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch(PDOException $e) {
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Ambil semua pembayaran
$stmt = $db->query("
    SELECT 
        cp.*,
        t.destination,
        u.username,
        u.full_name,
        t.price as trip_price
    FROM collection_payments cp
    JOIN trips t ON cp.trip_id = t.id
    JOIN users u ON cp.user_id = u.id
    ORDER BY cp.created_at DESC
");
$payments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pembayaran - Admin TripMates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background-color: #212529;
            padding-top: 1rem;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        .payment-proof-img {
            max-width: 100px;
            cursor: pointer;
        }
        .modal-img {
            max-width: 100%;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar text-white">
        <div class="px-3 mb-4">
            <h4><i class="bi bi-airplane-engines"></i> TripMates</h4>
            <small>Admin Dashboard</small>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="bi bi-people me-2"></i> Kelola Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="trips.php">
                    <i class="bi bi-map me-2"></i> Kelola Trips
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="payments.php">
                    <i class="bi bi-cash-stack me-2"></i> Kelola Pembayaran
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-danger" href="../pages/logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h2 class="mb-4">Kelola Pembayaran</h2>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Peserta</th>
                                <th>Destinasi</th>
                                <th>Metode</th>
                                <th>Jumlah</th>
                                <th>Bukti</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($payments as $payment): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($payment['full_name']); ?></div>
                                        <small class="text-muted">@<?php echo htmlspecialchars($payment['username']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($payment['destination']); ?></td>
                                    <td>
                                        <?php if($payment['payment_method'] == 'bank_transfer'): ?>
                                            <span class="badge bg-primary">Bank Transfer</span>
                                            <div class="small text-muted mt-1">
                                                <?php echo htmlspecialchars($payment['bank_name']); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-info">DANA</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?></td>
                                    <td>
                                        <img src="../assets/images/payments/<?php echo $payment['payment_proof']; ?>" 
                                             class="payment-proof-img"
                                             data-bs-toggle="modal"
                                             data-bs-target="#imageModal"
                                             data-img-src="../assets/images/payments/<?php echo $payment['payment_proof']; ?>"
                                             alt="Bukti Pembayaran">
                                    </td>
                                    <td>
                                        <?php
                                        $badge_class = 
                                            $payment['status'] == 'verified' ? 'bg-success' : 
                                            ($payment['status'] == 'rejected' ? 'bg-danger' : 'bg-warning');
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($payment['status'] == 'pending'): ?>
                                            <button type="button" 
                                                    class="btn btn-success btn-sm mb-1"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#verifyModal"
                                                    data-payment-id="<?php echo $payment['id']; ?>"
                                                    data-action="verify">
                                                <i class="bi bi-check-lg"></i> Verifikasi
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#verifyModal"
                                                    data-payment-id="<?php echo $payment['id']; ?>"
                                                    data-action="reject">
                                                <i class="bi bi-x-lg"></i> Tolak
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                Sudah diproses
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Preview Gambar -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bukti Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="" class="modal-img" alt="Bukti Pembayaran">
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Verifikasi -->
    <div class="modal fade" id="verifyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="payment_id" id="payment_id">
                        <input type="hidden" name="action" id="action">
                        
                        <p id="modal-message"></p>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan:</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn" id="confirm-button">Konfirmasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview gambar
        document.querySelectorAll('.payment-proof-img').forEach(img => {
            img.addEventListener('click', function() {
                document.querySelector('#imageModal .modal-img').src = this.dataset.imgSrc;
            });
        });

        // Setup modal verifikasi
        document.getElementById('verifyModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const action = button.dataset.action;
            const paymentId = button.dataset.paymentId;
            
            this.querySelector('#payment_id').value = paymentId;
            this.querySelector('#action').value = action;
            
            const message = action === 'verify' ? 
                'Apakah Anda yakin ingin memverifikasi pembayaran ini?' :
                'Apakah Anda yakin ingin menolak pembayaran ini?';
            
            this.querySelector('#modal-message').textContent = message;
            
            const confirmButton = this.querySelector('#confirm-button');
            confirmButton.className = `btn btn-${action === 'verify' ? 'success' : 'danger'}`;
            confirmButton.textContent = action === 'verify' ? 'Verifikasi' : 'Tolak';
        });
    </script>
</body>
</html> 