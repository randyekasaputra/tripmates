<?php
session_start();
require_once '../config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle persetujuan/penolakan permintaan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['participant_id'])) {
    $participant_id = $_POST['participant_id'];
    $action = $_POST['action'];
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    try {
        // Update status permintaan
        $stmt = $db->prepare("
            UPDATE trip_participants 
            SET status = ? 
            WHERE id = ? AND 
                  trip_id IN (SELECT id FROM trips WHERE user_id = ?)
        ");
        $stmt->execute([$status, $participant_id, $_SESSION['user_id']]);
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch(PDOException $e) {
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Ambil daftar permintaan untuk trip yang dibuat oleh user
$stmt = $db->prepare("
    SELECT 
        tp.id as participant_id,
        tp.status,
        tp.created_at as request_date,
        t.destination,
        t.start_date,
        t.end_date,
        u.username,
        u.full_name,
        u.profile_image
    FROM trip_participants tp
    JOIN trips t ON tp.trip_id = t.id
    JOIN users u ON tp.user_id = u.id
    WHERE t.user_id = ?
    ORDER BY tp.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Permintaan - TripMates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <div class="container my-5">
        <h2 class="mb-4">Kelola Permintaan Trip</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($requests)): ?>
            <div class="alert alert-info">
                Belum ada permintaan bergabung dengan trip Anda.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Peserta</th>
                            <th>Destinasi</th>
                            <th>Tanggal Trip</th>
                            <th>Tanggal Request</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($requests as $request): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="../assets/images/profiles/<?php echo $request['profile_image'] ?: 'default-avatar.jpg'; ?>" 
                                             class="rounded-circle me-2" 
                                             width="40" 
                                             height="40" 
                                             alt="Profile">
                                        <div>
                                            <div><?php echo htmlspecialchars($request['full_name']); ?></div>
                                            <small class="text-muted">@<?php echo htmlspecialchars($request['username']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($request['destination']); ?></td>
                                <td>
                                    <?php echo date('d M Y', strtotime($request['start_date'])); ?> - 
                                    <?php echo date('d M Y', strtotime($request['end_date'])); ?>
                                </td>
                                <td><?php echo date('d M Y H:i', strtotime($request['request_date'])); ?></td>
                                <td>
                                    <?php
                                    $badge_class = 
                                        $request['status'] == 'approved' ? 'bg-success' : 
                                        ($request['status'] == 'rejected' ? 'bg-danger' : 'bg-warning');
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($request['status'] == 'pending'): ?>
                                        <div class="btn-group" role="group">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="participant_id" value="<?php echo $request['participant_id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-success btn-sm me-1" 
                                                        onclick="return confirm('Setujui permintaan ini?')">
                                                    <i class="bi bi-check-lg"></i> Setujui
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="participant_id" value="<?php echo $request['participant_id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Tolak permintaan ini?')">
                                                    <i class="bi bi-x-lg"></i> Tolak
                                                </button>
                                            </form>
                                        </div>
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
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>