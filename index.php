<?php
session_start();
require_once 'config/database.php';

// Ambil daftar trip terbaru
$stmt = $db->query("
    SELECT t.*, u.username, 
           (SELECT COUNT(*) FROM trip_participants WHERE trip_id = t.id AND status = 'approved') as participant_count 
    FROM trips t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC 
    LIMIT 6
");
$latest_trips = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMates - Temukan Teman Traveling</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'components/navbar.php'; ?>

    <!-- Hero Section -->
    <div class="bg-primary text-white py-4 hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4 fw-bold">Temukan Teman Traveling</h1>
                    <p class="lead">Jelajahi destinasi impian bersama teman baru. Bergabung dengan TripMates sekarang!</p>
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <a href="pages/register.php" class="btn btn-light btn-lg me-2">Daftar Sekarang</a>
                        <a href="pages/login.php" class="btn btn-outline-light btn-lg">Login</a>
                    <?php else: ?>
                        <a href="pages/tambah-destinasi.php" class="btn btn-light btn-lg">Buat Trip Baru</a>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 text-center">
                    <img src="assets/images/travel-hero.png" class="img-fluid rounded shadow" alt="Travel Hero" style="max-height: 250px; object-fit: cover;">
                </div>
            </div>
        </div>
    </div>

    <!-- Trip Terbaru Section -->
    <div class="container my-5">
        <h2 class="text-center mb-4">Trip Terbaru</h2>
        <div class="row">
            <?php if(empty($latest_trips)): ?>
                <div class="col-12 text-center">
                    <p>Belum ada trip yang tersedia.</p>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="pages/tambah-destinasi.php" class="btn btn-primary">Buat Trip Pertama</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach($latest_trips as $trip): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card trip-card h-100 shadow-sm">
                            <img src="assets/images/trips/<?php echo htmlspecialchars($trip['image'] ?: 'default-trip.jpg'); ?>" 
                                 class="card-img-top trip-image" 
                                 alt="<?php echo htmlspecialchars($trip['destination']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($trip['destination']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars(substr($trip['description'], 0, 100)) . '...'; ?></p>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> 
                                            <?php echo date('d M Y', strtotime($trip['start_date'])); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <small class="text-muted me-3">
                                            <i class="bi bi-people"></i> 
                                            <?php echo $trip['participant_count']; ?>/<?php echo $trip['max_participants']; ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="bi bi-cash"></i> 
                                            Rp <?php echo number_format($trip['price'], 0, ',', '.'); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        By @<?php echo htmlspecialchars($trip['username']); ?>
                                    </small>
                                    <a href="pages/destinasi.php?id=<?php echo $trip['id']; ?>" 
                                       class="btn btn-primary btn-sm">Lihat Detail</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Fitur Section -->
    <div class="bg-light py-5">
        <div class="container">
            <h2 class="text-center mb-5">Mengapa TripMates?</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="bi bi-people-fill display-4 text-primary mb-3"></i>
                        <h4>Teman Baru</h4>
                        <p>Temukan teman traveling yang sesuai dengan minat dan tujuan perjalananmu.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="bi bi-shield-check display-4 text-primary mb-3"></i>
                        <h4>Aman & Terpercaya</h4>
                        <p>Semua pengguna telah terverifikasi dan trip dikelola dengan profesional.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="bi bi-cash-coin display-4 text-primary mb-3"></i>
                        <h4>Harga Terjangkau</h4>
                        <p>Berbagi biaya perjalanan dengan peserta lain untuk menghemat pengeluaran.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Destinasi Populer Section -->
    <div class="container my-5">
        <h2 class="text-center mb-4">Destinasi Populer</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card destination-card">
                    <div class="destination-img-wrapper">
                        <img src="./assets/images/destinations/bali.jpg" class="card-img destination-img" alt="Bali">
                        <div class="destination-overlay">
                            <div class="destination-content">
                                <h5 class="card-title">Bali</h5>
                                <p class="card-text">Pulau Dewata dengan keindahan alam dan budaya yang memukau</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card destination-card">
                    <div class="destination-img-wrapper">
                        <img src="assets/images/destinations/lombok.jpg" class="card-img destination-img" alt="Lombok">
                        <div class="destination-overlay">
                            <div class="destination-content">
                                <h5 class="card-title">Lombok</h5>
                                <p class="card-text">Surga tersembunyi dengan pantai-pantai yang menakjubkan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card destination-card">
                    <div class="destination-img-wrapper">
                        <img src="assets/images/destinations/raja-ampat.jpeg" class="card-img destination-img" alt="Raja Ampat">
                        <div class="destination-overlay">
                            <div class="destination-content">
                                <h5 class="card-title">Raja Ampat</h5>
                                <p class="card-text">Surga bawah laut dengan keindahan terumbu karang</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .destination-card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }

    .destination-card:hover {
        transform: translateY(-5px);
    }

    .destination-img-wrapper {
        position: relative;
        padding-top: 75%; /* Aspect ratio 4:3 */
        overflow: hidden;
    }

    .destination-img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .destination-card:hover .destination-img {
        transform: scale(1.1);
    }

    .destination-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
        padding: 20px;
        color: white;
    }

    .destination-content {
        transform: translateY(0);
        transition: transform 0.3s ease;
    }

    .destination-card:hover .destination-content {
        transform: translateY(-5px);
    }

    .destination-content h5 {
        margin-bottom: 8px;
        font-weight: 600;
    }

    .destination-content p {
        margin-bottom: 0;
        font-size: 0.9rem;
        opacity: 0.9;
    }

    .hero-section {
        position: relative;
    }

    .hero-section img {
        transition: transform 0.3s ease;
    }

    .hero-section img:hover {
        transform: scale(1.05);
    }
    </style>

    <?php include 'components/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>