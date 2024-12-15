<?php
session_start();
require_once '../config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Ambil data user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Ambil trip yang dibuat user
$stmt = $db->prepare("
    SELECT t.*, 
           (SELECT COUNT(*) FROM trip_participants WHERE trip_id = t.id AND status = 'approved') as participant_count
    FROM trips t 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$created_trips = $stmt->fetchAll();

// Ambil trip yang diikuti user
$stmt = $db->prepare("
    SELECT t.*, tp.status, u.username as organizer_name,
           (SELECT COUNT(*) FROM trip_participants WHERE trip_id = t.id AND status = 'approved') as participant_count
    FROM trips t 
    JOIN trip_participants tp ON t.id = tp.trip_id 
    JOIN users u ON t.user_id = u.id
    WHERE tp.user_id = ? 
    ORDER BY tp.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$joined_trips = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - TripMates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .trip-image {
            height: 200px;
            object-fit: cover;
        }
        .card {
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>

    <div class="container my-5">
        <!-- Profile Header -->
        <div class="row mb-5">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <img src="../assets/images/profiles/<?php echo $user['profile_image'] ?: 'default-avatar.jpg'; ?>" 
                             class="rounded-circle mb-3" 
                             width="150" 
                             height="150"
                             alt="Profile Image"
                             style="object-fit: cover;">
                        <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                        <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                        <p class="mb-3"><?php echo htmlspecialchars($user['bio'] ?: 'Belum ada bio'); ?></p>
                        <a href="edit-profil.php" class="btn btn-primary">
                            <i class="bi bi-pencil-square"></i> Edit Profil
                        </a>
                    </div>
                    <div class="card-footer bg-light">
                        <div class="row text-center">
                            <div class="col-6">
                                <h5><?php echo count($created_trips); ?></h5>
                                <small class="text-muted">Trip Dibuat</small>
                            </div>
                            <div class="col-6">
                                <h5><?php echo count($joined_trips); ?></h5>
                                <small class="text-muted">Trip Diikuti</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="trips-tab" data-bs-toggle="tab" href="#trips" role="tab">
                            <i class="bi bi-compass"></i> Trip Saya
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="joined-tab" data-bs-toggle="tab" href="#joined" role="tab">
                            <i class="bi bi-people"></i> Trip yang Diikuti
                        </a>
                    </li>
                </ul>

                <!-- Tab Contents -->
                <div class="tab-content" id="profileTabsContent">
                    <!-- Trip Saya -->
                    <div class="tab-pane fade show active" id="trips" role="tabpanel">
                        <?php if (empty($created_trips)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-compass display-1 text-muted"></i>
                                <p class="mt-3">Anda belum membuat trip apapun.</p>
                                <a href="tambah-destinasi.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Buat Trip Baru
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach($created_trips as $trip): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 shadow-sm">
                                            <img src="../assets/images/trips/<?php echo $trip['image'] ?: 'default-trip.jpg'; ?>" 
                                                 class="card-img-top trip-image" 
                                                 alt="<?php echo htmlspecialchars($trip['destination']); ?>">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($trip['destination']); ?></h5>
                                                <p class="card-text"><?php echo htmlspecialchars(substr($trip['description'], 0, 100)) . '...'; ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="bi bi-calendar"></i> 
                                                        <?php echo date('d M Y', strtotime($trip['start_date'])); ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="bi bi-people"></i> 
                                                        <?php echo $trip['participant_count']; ?>/<?php echo $trip['max_participants']; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-white">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <a href="collection-payment.php?trip_id=<?php echo $trip['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary me-2">
                                                            <i class="bi bi-cash-stack"></i> Kelola Pembayaran
                                                        </a>
                                                        <a href="kelola-permintaan.php?trip_id=<?php echo $trip['id']; ?>" 
                                                           class="btn btn-sm btn-primary">
                                                            <i class="bi bi-people"></i> Kelola Peserta
                                                        </a>
                                                    </div>
                                                    <a href="destinasi.php?id=<?php echo $trip['id']; ?>" 
                                                       class="btn btn-sm btn-info text-white">
                                                        <i class="bi bi-eye"></i> Lihat
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Trip yang Diikuti -->
                    <div class="tab-pane fade" id="joined" role="tabpanel">
                        <?php if (empty($joined_trips)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-people display-1 text-muted"></i>
                                <p class="mt-3">Anda belum mengikuti trip apapun.</p>
                                <a href="../index.php" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Cari Trip
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach($joined_trips as $trip): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 shadow-sm">
                                            <div class="position-absolute top-0 end-0 m-2">
                                                <?php
                                                $badge_class = 
                                                    $trip['status'] == 'approved' ? 'bg-success' : 
                                                    ($trip['status'] == 'rejected' ? 'bg-danger' : 'bg-warning');
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo ucfirst($trip['status']); ?>
                                                </span>
                                            </div>
                                            <img src="../assets/images/trips/<?php echo $trip['image'] ?: 'default-trip.jpg'; ?>" 
                                                 class="card-img-top trip-image" 
                                                 alt="<?php echo htmlspecialchars($trip['destination']); ?>">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($trip['destination']); ?></h5>
                                                <p class="card-text"><?php echo htmlspecialchars(substr($trip['description'], 0, 100)) . '...'; ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="bi bi-calendar"></i> 
                                                        <?php echo date('d M Y', strtotime($trip['start_date'])); ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="bi bi-people"></i> 
                                                        <?php echo $trip['participant_count']; ?>/<?php echo $trip['max_participants']; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-white">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        By @<?php echo htmlspecialchars($trip['organizer_name']); ?>
                                                    </small>
                                                    <?php if($trip['status'] == 'approved'): ?>
                                                        <a href="gabung-trip.php?id=<?php echo $trip['id']; ?>" 
                                                           class="btn btn-sm btn-success">
                                                            <i class="bi bi-check-circle"></i> Gabung
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="destinasi.php?id=<?php echo $trip['id']; ?>" 
                                                       class="btn btn-sm btn-info text-white">
                                                        <i class="bi bi-eye"></i> Lihat Detail
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- <?php include '../components/footer.php'; ?> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>