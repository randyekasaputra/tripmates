<?php
session_start();
require_once '../config/database.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit();
}

// Handle aksi hapus/nonaktifkan trip
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['trip_id'])) {
    $trip_id = $_POST['trip_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM trips WHERE id = ?");
            $stmt->execute([$trip_id]);
            $_SESSION['success'] = "Trip berhasil dihapus.";
        } else if ($action === 'toggle') {
            $stmt = $db->prepare("UPDATE trips SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$trip_id]);
            $_SESSION['success'] = "Status trip berhasil diperbarui.";
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch(PDOException $e) {
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Ambil semua trip
$stmt = $db->prepare("
    SELECT 
        t.*,
        u.username,
        u.full_name,
        (SELECT COUNT(*) FROM trip_participants WHERE trip_id = t.id AND status = 'approved') as participant_count,
        (SELECT COUNT(*) FROM collection_payments cp 
         JOIN trip_participants tp ON cp.trip_participant_id = tp.id 
         WHERE tp.trip_id = t.id AND cp.status = 'verified') as paid_count
    FROM trips t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
");
$stmt->execute();
$trips = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Trips - Admin TripMates</title>
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
        .trip-img {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
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
                <a class="nav-link active" href="trips.php">
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
        <h2 class="mb-4">Kelola Trips</h2>

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
                                <th>Trip</th>
                                <th>Dibuat oleh</th>
                                <th>Tanggal</th>
                                <th>Peserta</th>
                                <th>Harga</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($trips as $trip): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="../assets/images/trips/<?php echo $trip['image'] ?: 'default-trip.jpg'; ?>" 
                                                 class="trip-img me-3" 
                                                 alt="Trip">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($trip['destination']); ?></div>
                                                <small class="text-muted">
                                                    <?php echo date('d M Y', strtotime($trip['start_date'])); ?> - 
                                                    <?php echo date('d M Y', strtotime($trip['end_date'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($trip['full_name']); ?></div>
                                        <small class="text-muted">@<?php echo htmlspecialchars($trip['username']); ?></small>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($trip['created_at'])); ?></td>
                                    <td>
                                        <div><?php echo $trip['participant_count']; ?>/<?php echo $trip['max_participants']; ?></div>
                                        <small class="text-muted"><?php echo $trip['paid_count']; ?> sudah bayar</small>
                                    </td>
                                    <td>Rp <?php echo number_format($trip['price'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $trip['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $trip['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                                            <input type="hidden" name="action" value="toggle">
                                            <button type="submit" class="btn btn-warning btn-sm" 
                                                    onclick="return confirm('Yakin ingin mengubah status trip ini?')">
                                                <i class="bi bi-toggle-on"></i> Toggle Status
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Yakin ingin menghapus trip ini?')">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        </form>
                                        <a href="../pages/destinasi.php?id=<?php echo $trip['id']; ?>" 
                                           class="btn btn-info btn-sm" target="_blank">
                                            <i class="bi bi-eye"></i> Lihat
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 