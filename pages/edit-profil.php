<?php
session_start();
require_once '../config/database.php';

// Redirect jika belum login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Ambil data user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $bio = trim($_POST['bio']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // Update profil tanpa password
        if (empty($current_password)) {
            // Validasi email
            if ($email !== $user['email']) {
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    throw new Exception("Email sudah digunakan!");
                }
            }

            // Handle upload foto profil
            $profile_image = $user['profile_image'];
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['profile_image']['name'];
                $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                $filesize = $_FILES['profile_image']['size'];

                if (!in_array(strtolower($filetype), $allowed)) {
                    throw new Exception('Format file tidak diizinkan. Gunakan: ' . implode(', ', $allowed));
                }
                if ($filesize > 5242880) {
                    throw new Exception('Ukuran file terlalu besar. Maksimal 5MB.');
                }

                $newname = uniqid() . '.' . $filetype;
                $upload_path = '../assets/images/profiles/' . $newname;

                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    // Hapus foto lama jika ada
                    if ($profile_image && $profile_image != 'default-avatar.jpg' && file_exists('../assets/images/profiles/' . $profile_image)) {
                        unlink('../assets/images/profiles/' . $profile_image);
                    }
                    $profile_image = $newname;
                }
            }

            // Update data profil
            $stmt = $db->prepare("
                UPDATE users 
                SET full_name = ?, email = ?, bio = ?, profile_image = ?
                WHERE id = ?
            ");
            $stmt->execute([$full_name, $email, $bio, $profile_image, $_SESSION['user_id']]);
            $success = "Profil berhasil diperbarui!";

        } else {
            // Update profil dengan password baru
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("Password saat ini tidak sesuai!");
            }
            if (strlen($new_password) < 6) {
                throw new Exception("Password baru minimal 6 karakter!");
            }
            if ($new_password !== $confirm_password) {
                throw new Exception("Konfirmasi password baru tidak sesuai!");
            }

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                UPDATE users 
                SET full_name = ?, email = ?, bio = ?, password = ?
                WHERE id = ?
            ");
            $stmt->execute([$full_name, $email, $bio, $hashed_password, $_SESSION['user_id']]);
            $success = "Profil dan password berhasil diperbarui!";
        }

        // Refresh data user
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - TripMates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">
                            <i class="bi bi-person-gear text-primary"></i> Edit Profil
                        </h2>

                        <?php if($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-circle-fill"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="text-center mb-4">
                                <img src="../assets/images/profiles/<?php echo $user['profile_image'] ?: 'default-avatar.jpg'; ?>" 
                                     class="rounded-circle mb-3" 
                                     width="150" 
                                     height="150"
                                     alt="Profile Image"
                                     style="object-fit: cover;">
                                <div>
                                    <label for="profile_image" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-camera"></i> Ganti Foto
                                    </label>
                                    <input type="file" 
                                           class="d-none" 
                                           id="profile_image" 
                                           name="profile_image" 
                                           accept="image/*">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" 
                                       disabled>
                                <div class="form-text">Username tidak dapat diubah</div>
                            </div>

                            <div class="mb-3">
                                <label for="full_name" class="form-label">Nama Lengkap</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="full_name" 
                                       name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" 
                                          id="bio" 
                                          name="bio" 
                                          rows="3"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                            </div>

                            <hr class="my-4">

                            <h5 class="mb-3">Ubah Password</h5>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Password Saat Ini</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="current_password" 
                                       name="current_password">
                                <div class="form-text">Kosongkan jika tidak ingin mengubah password</div>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">Password Baru</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="new_password" 
                                       name="new_password">
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview foto profil sebelum upload
        document.getElementById('profile_image').onchange = function(e) {
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
                    document.querySelector('img.rounded-circle').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        };
    </script>
</body>
</html> 