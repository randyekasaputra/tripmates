<?php
session_start();
require_once '../config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Ambil ID trip dari parameter URL
$trip_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$trip_id) {
    header('Location: profil.php');
    exit();
}

// Ambil detail trip
$stmt = $db->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
$stmt->execute([$trip_id, $_SESSION['user_id']]);
$trip = $stmt->fetch();

// Cek apakah trip ditemukan dan milik user yang login
if (!$trip) {
    header('Location: profil.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destination = $_POST['destination'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $max_participants = $_POST['max_participants'];
    $price = $_POST['price'];
    
    // Handle file upload jika ada
    $image = $trip['image']; // Gunakan gambar yang sudah ada sebagai default
    $upload_error = '';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        $filesize = $_FILES['image']['size'];
        
        if (!in_array(strtolower($filetype), $allowed)) {
            $upload_error = 'Format file tidak diizinkan. Gunakan: ' . implode(', ', $allowed);
        } elseif ($filesize > 5242880) {
            $upload_error = 'Ukuran file terlalu besar. Maksimal 5MB.';
        } else {
            $newname = uniqid() . '.' . $filetype;
            $upload_path = '../assets/images/trips/' . $newname;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Hapus gambar lama jika ada
                if ($trip['image'] && file_exists('../assets/images/trips/' . $trip['image'])) {
                    unlink('../assets/images/trips/' . $trip['image']);
                }
                $image = $newname;
            } else {
                $upload_error = 'Gagal mengupload file.';
            }
        }
    }

    if (!$upload_error) {
        try {
            $stmt = $db->prepare("
                UPDATE trips 
                SET destination = ?, description = ?, start_date = ?, 
                    end_date = ?, max_participants = ?, price = ?, image = ?
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([
                $destination,
                $description,
                $start_date,
                $end_date,
                $max_participants,
                $price,
                $image,
                $trip_id,
                $_SESSION['user_id']
            ]);

            $success = "Trip berhasil diperbarui!";
            
            // Refresh data trip
            $stmt = $db->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
            $stmt->execute([$trip_id, $_SESSION['user_id']]);
            $trip = $stmt->fetch();
            
        } catch(PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    } else {
        $error = $upload_error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Trip - TripMates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .preview-container {
            max-width: 300px;
            margin: 10px 0;
        }
        .preview-container img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include '../components/navbar.php'; ?>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Edit Trip</h2>
                        
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

                        <form method="POST" enctype="multipart/form-data" id="tripForm">
                            <div class="mb-3">
                                <label for="destination" class="form-label">Destinasi</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="destination" 
                                       name="destination" 
                                       value="<?php echo htmlspecialchars($trip['destination']); ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Deskripsi</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="4" 
                                          required><?php echo htmlspecialchars($trip['description']); ?></textarea>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="start_date" 
                                           name="start_date" 
                                           value="<?php echo $trip['start_date']; ?>"
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label for="end_date" class="form-label">Tanggal Selesai</label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="end_date" 
                                           name="end_date" 
                                           value="<?php echo $trip['end_date']; ?>"
                                           required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="max_participants" class="form-label">Maksimal Peserta</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="max_participants" 
                                           name="max_participants" 
                                           value="<?php echo $trip['max_participants']; ?>"
                                           min="1" 
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label for="price" class="form-label">Harga per Orang</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" 
                                               class="form-control" 
                                               id="price" 
                                               name="price" 
                                               value="<?php echo $trip['price']; ?>"
                                               min="0" 
                                               required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">Gambar Destinasi</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="form-text">Format yang diizinkan: JPG, JPEG, PNG, GIF. Maksimal 5MB.</div>
                                <div class="preview-container">
                                    <img src="../assets/images/trips/<?php echo $trip['image'] ?: 'default-trip.jpg'; ?>" 
                                         id="imagePreview" 
                                         alt="Preview">
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Perubahan
                                </button>
                                <a href="profil.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali ke Profil
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../components/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview gambar
        document.getElementById('image').onchange = function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validasi ukuran file
                if (file.size > 5242880) {
                    alert('Ukuran file terlalu besar. Maksimal 5MB.');
                    this.value = '';
                    return;
                }
                
                // Validasi tipe file
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak diizinkan. Gunakan: JPG, JPEG, PNG, GIF');
                    this.value = '';
                    return;
                }
                
                // Preview gambar
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        };

        // Validasi tanggal
        document.getElementById('tripForm').onsubmit = function(e) {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (startDate < today) {
                alert('Tanggal mulai tidak boleh kurang dari hari ini');
                e.preventDefault();
                return false;
            }

            if (endDate < startDate) {
                alert('Tanggal selesai tidak boleh kurang dari tanggal mulai');
                e.preventDefault();
                return false;
            }
        };
    </script>
</body>
</html> 