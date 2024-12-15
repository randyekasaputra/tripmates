<?php
session_start();
require_once '../config/database.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit();
}

// Ambil statistik
$stats = [
    'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_trips' => $db->query("SELECT COUNT(*) FROM trips")->fetchColumn(),
    'pending_payments' => $db->query("SELECT COUNT(*) FROM collection_payments WHERE status = 'pending'")->fetchColumn(),
    'total_earnings' => $db->query("SELECT SUM(amount) FROM collection_payments WHERE status = 'verified'")->fetchColumn() ?: 0
];

// Ambil pembayaran terbaru
$stmt = $db->query("
    SELECT cp.*, t.destination, u.username, u.full_name
    FROM collection_payments cp
    JOIN trips t ON cp.trip_id = t.id
    JOIN users u ON cp.user_id = u.id
    ORDER BY cp.created_at DESC
    LIMIT 5
");
$recent_payments = $stmt->fetchAll();

// Ambil trip terbaru
$stmt = $db->query("
    SELECT t.*, u.username, 
           (SELECT COUNT(*) FROM trip_participants WHERE trip_id = t.id AND status = 'approved') as participant_count
    FROM trips t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 5
");
$recent_trips = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TripMates</title>
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
        .stat-card {
            border-radius: 10px;
            border: none;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .nav-link {
            color: rgba(255,255,255,.8);
            padding: 0.8rem 1rem;
            margin: 0.2rem 1rem;
            border-radius: 5px;
        }
        .nav-link:hover, .nav-link.active {
            background-color: rgba(255,255,255,.1);
            color: white;
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
                <a class="nav-link active" href="index.php">
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
                <a class="nav-link" href="payments.php">
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
        <h2 class="mb-4">Dashboard</h2>

        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p class="mb-0">Total Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <h3><?php echo number_format($stats['total_trips']); ?></h3>
                        <p class="mb-0">Total Trips</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body">
                        <h3><?php echo number_format($stats['pending_payments']); ?></h3>
                        <p class="mb-0">Pending Payments</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <h3>Rp <?php echo number_format($stats['total_earnings'], 0, ',', '.'); ?></h3>
                        <p class="mb-0">Total Earnings</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Pembayaran Terbaru -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Pembayaran Terbaru</h5>
                        <a href="payments.php" class="btn btn-sm btn-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Trip</th>
                                        <th>Jumlah</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['username']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['destination']); ?></td>
                                            <td>Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $payment['status'] == 'verified' ? 'success' : 
                                                        ($payment['status'] == 'rejected' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trip Terbaru -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Trip Terbaru</h5>
                        <a href="trips.php" class="btn btn-sm btn-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Destinasi</th>
                                        <th>Dibuat oleh</th>
                                        <th>Peserta</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_trips as $trip): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($trip['destination']); ?></td>
                                            <td><?php echo htmlspecialchars($trip['username']); ?></td>
                                            <td>
                                                <?php echo $trip['participant_count']; ?>/<?php echo $trip['max_participants']; ?>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($trip['start_date'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 