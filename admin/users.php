<?php
session_start();
require_once '../config/database.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit();
}

// Handle aksi blokir/aktifkan user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    $is_active = ($action === 'activate') ? 1 : 0;
    
    try {
        $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$is_active, $user_id]);
        
        $_SESSION['success'] = "Status user berhasil diperbarui.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch(PDOException $e) {
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Ambil semua user kecuali admin
$stmt = $db->prepare("
    SELECT 
        u.*,
        (SELECT COUNT(*) FROM trips WHERE user_id = u.id) as total_trips,
        (SELECT COUNT(*) FROM trip_participants WHERE user_id = u.id) as joined_trips
    FROM users u 
    WHERE username != 'admin'
    ORDER BY created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Users - Admin TripMates</title>
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
        .profile-img {
            width: 40px;
            height: 40px;
            object-fit: cover;
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
                <a class="nav-link active" href="users.php">
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
        <h2 class="mb-4">Kelola Users</h2>

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
                                <th>User</th>
                                <th>Email</th>
                                <th>Bergabung</th>
                                <th>Trip Dibuat</th>
                                <th>Trip Diikuti</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="../assets/images/profiles/<?php echo $user['profile_image'] ?: 'default-avatar.jpg'; ?>" 
                                                 class="rounded-circle me-2 profile-img" 
                                                 alt="Profile">
                                            <div>
                                                <div><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    <td><?php echo $user['total_trips']; ?> trip</td>
                                    <td><?php echo $user['joined_trips']; ?> trip</td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $user['is_active'] ? 'Aktif' : 'Diblokir'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($user['is_active']): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="block">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Yakin ingin memblokir user ini?')">
                                                    <i class="bi bi-slash-circle"></i> Blokir
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="btn btn-success btn-sm"
                                                        onclick="return confirm('Yakin ingin mengaktifkan user ini?')">
                                                    <i class="bi bi-check-circle"></i> Aktifkan
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="view_user.php?id=<?php echo $user['id']; ?>" 
                                           class="btn btn-info btn-sm">
                                            <i class="bi bi-eye"></i> Detail
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