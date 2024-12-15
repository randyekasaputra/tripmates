<?php
session_start();
require_once '../config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destination = $_POST['destination'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $max_participants = $_POST['max_participants'];
    $price = $_POST['price'];
    
    // Handle file upload
    $image = '';
    $upload_error = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        $filesize = $_FILES['image']['size'];
        
        // Validasi tipe file
        if (!in_array(strtolower($filetype), $allowed)) {
            $upload_error = 'Format file tidak diizinkan. Gunakan: ' . implode(', ', $allowed);
        }
        // Validasi ukuran file (max 5MB)
        elseif ($filesize > 5242880) {
            $upload_error = 'Ukuran file terlalu besar. Maksimal 5MB.';
        }
        else {
            $newname = uniqid() . '.' . $filetype;
            $upload_path = '../assets/images/trips/' . $newname;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image = $newname;
            } else {
                $upload_error = 'Gagal mengupload file.';
            }
        }
    }

    if (!$upload_error) {
        try {
            // Pastikan user_id ada
            $user_id = $_SESSION['user_id'];
            
            // Cek apakah user ada
            $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            if (!$stmt->fetch()) {
                throw new Exception("User tidak valid");
            }

            // Insert trip baru
            $stmt = $db->prepare("
                INSERT INTO trips (user_id, destination, description, start_date, end_date, 
                                 max_participants, price, image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id,
                $destination,
                $description,
                $start_date,
                $end_date,
                $max_participants,
                $price,
                $image
            ]);

            header('Location: ../index.php');
            exit();
        } catch(Exception $e) {
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
    <title>Tambah Destinasi - TripMates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .preview-container {
            max-width: 300px;
            margin: 10px 0;
            display: none;
        }
        .preview-container img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        .preview-container .remove-preview {
            position: absolute;
            top: 10px;
            right: 25px;
            background: rgba(0,0,0,0.5);
            color: white;
            padding: 5px 10px;
            border-radius: 50%;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Tambah Destinasi Baru</h2>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" id="tripForm">
                            <div class="mb-3">
                                <label for="destination" class="form-label">Destinasi</label>
                                <input type="text" class="form-control" id="destination" name="destination" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="end_date" class="form-label">Tanggal Selesai</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="max_participants" class="form-label">Maksimal Peserta</label>
                                    <input type="number" class="form-control" id="max_participants" name="max_participants" min="1" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="price" class="form-label">Harga per Orang</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="price" name="price" min="0" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">Gambar Destinasi</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="form-text">Format yang diizinkan: JPG, JPEG, PNG, GIF. Maksimal 5MB.</div>
                                <div class="preview-container position-relative">
                                    <img id="imagePreview" src="#" alt="Preview">
                                    <span class="remove-preview"><i class="bi bi-x"></i></span>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Tambah Destinasi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview gambar
        document.getElementById('image').onchange = function(e) {
            const preview = document.getElementById('imagePreview');
            const previewContainer = document.querySelector('.preview-container');
            const file = e.target.files[0];
            
            if (file) {
                // Validasi ukuran file
                if (file.size > 5242880) {
                    alert('Ukuran file terlalu besar. Maksimal 5MB.');
                    this.value = '';
                    previewContainer.style.display = 'none';
                    return;
                }
                
                // Validasi tipe file
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak diizinkan. Gunakan: JPG, JPEG, PNG, GIF');
                    this.value = '';
                    previewContainer.style.display = 'none';
                    return;
                }
                
                preview.src = URL.createObjectURL(file);
                previewContainer.style.display = 'block';
            }
        };

        // Hapus preview
        document.querySelector('.remove-preview').onclick = function() {
            document.getElementById('image').value = '';
            document.querySelector('.preview-container').style.display = 'none';
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