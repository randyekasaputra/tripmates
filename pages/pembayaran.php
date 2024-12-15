<?php
session_start();
require_once '../config/database.php';

// Ambil parameter dari URL
$trip_id = isset($_GET['trip_id']) ? $_GET['trip_id'] : null;
$price = isset($_GET['price']) ? $_GET['price'] : null;

// Cek apakah trip_id dan price ada
if (!$trip_id || !$price) {
    header('Location: ../index.php');
    exit();
}

// Ambil detail trip untuk konfirmasi
$stmt = $db->prepare("SELECT destination FROM trips WHERE id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch();

if (!$trip) {
    header('Location: ../index.php');
    exit();
}

// Array rekening yang tersedia
$bank_accounts = [
    'bca' => [
        'name' => 'Bank BCA',
        'number' => '8465472589',
        'holder' => 'TripMates Indonesia'
    ],
    'mandiri' => [
        'name' => 'Bank Mandiri',
        'number' => '1440056789123',
        'holder' => 'TripMates Indonesia'
    ]
];

$dana_account = [
    'number' => '087731932494',
    'holder' => 'TripMates'
];

// Proses unggah bukti pembayaran
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = $_POST['payment_method'];
    $bank_name = '';
    $account_number = '';
    $account_name = '';
    
    // Set detail bank jika metode pembayaran bank transfer
    if ($payment_method == 'bank_transfer') {
        $bank_name = $_POST['bank_name'];
        $account_number = $_POST['account_number'];
        $account_name = $_POST['account_name'];
    }

    $upload_error = '';
    $payment_proof = '';

    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $filename = $_FILES['payment_proof']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        $filesize = $_FILES['payment_proof']['size'];

        if (!in_array(strtolower($filetype), $allowed)) {
            $upload_error = 'Format file tidak diizinkan. Gunakan: ' . implode(', ', $allowed);
        } elseif ($filesize > 5242880) {
            $upload_error = 'Ukuran file terlalu besar. Maksimal 5MB.';
        } else {
            $newname = uniqid() . '.' . $filetype;
            $upload_path = '../assets/images/payments/' . $newname;

            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_path)) {
                try {
                    // Insert ke collection_payments
                    $stmt = $db->prepare("
                        INSERT INTO collection_payments (
                            trip_id,
                            user_id,
                            amount,
                            payment_method,
                            bank_name,
                            account_number,
                            account_name,
                            payment_proof,
                            payment_date,
                            status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')
                    ");
                    
                    $stmt->execute([
                        $trip_id,
                        $_SESSION['user_id'],
                        $price,
                        $payment_method,
                        $bank_name,
                        $account_number,
                        $account_name,
                        $payment_proof
                    ]);

                    $_SESSION['success'] = "Bukti pembayaran berhasil diunggah dan sedang menunggu verifikasi.";
                    header("Location: destinasi.php?id=" . $trip_id);
                    exit();
                } catch(PDOException $e) {
                    $upload_error = "Terjadi kesalahan: " . $e->getMessage();
                    // Hapus file jika gagal menyimpan ke database
                    if (file_exists($upload_path)) {
                        unlink($upload_path);
                    }
                }
            } else {
                $upload_error = 'Gagal mengupload file.';
            }
        }
    } else {
        $upload_error = 'Silakan unggah bukti pembayaran.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Trip - TripMates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .payment-option {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-option:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .payment-option.selected {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .payment-details {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .payment-details.active {
            display: block;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../components/navbar.php'; ?>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">
                            <i class="bi bi-credit-card text-primary"></i> Pembayaran Trip
                        </h2>

                        <!-- Detail Pembayaran -->
                        <div class="alert alert-info mb-4">
                            <h5 class="alert-heading">Detail Pembayaran:</h5>
                            <p class="mb-1">Destinasi: <?php echo htmlspecialchars($trip['destination']); ?></p>
                            <p class="mb-0">Total Pembayaran: Rp <?php echo number_format($price, 0, ',', '.'); ?></p>
                        </div>

                        <?php if (isset($upload_error)): ?>
                            <div class="alert alert-danger"><?php echo $upload_error; ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <!-- Pilihan Metode Pembayaran -->
                            <h5 class="mb-3">Pilih Metode Pembayaran:</h5>

                            <!-- Transfer Bank -->
                            <div class="payment-option" onclick="selectPayment('bank')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="bank" id="bank" class="me-3">
                                    <div>
                                        <label for="bank" class="fw-bold mb-0">Transfer Bank</label>
                                        <p class="text-muted mb-0">Transfer melalui rekening bank</p>
                                    </div>
                                </div>
                                <div class="payment-details" id="bank-details">
                                    <h6 class="mb-3">Rekening Tujuan:</h6>
                                    <div class="list-group mb-3">
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0">Bank BCA</h6>
                                                    <p class="mb-0">0123456789</p>
                                                    <small class="text-muted">a.n. TripMates Indonesia</small>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('0123456789')">
                                                    <i class="bi bi-clipboard"></i> Salin
                                                </button>
                                            </div>
                                        </div>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0">Bank Mandiri</h6>
                                                    <p class="mb-0">987654321</p>
                                                    <small class="text-muted">a.n. TripMates Indonesia</small>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('987654321')">
                                                    <i class="bi bi-clipboard"></i> Salin
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- DANA -->
                            <div class="payment-option mt-3" onclick="selectPayment('dana')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="dana" id="dana" class="me-3">
                                    <div>
                                        <label for="dana" class="fw-bold mb-0">DANA</label>
                                        <p class="text-muted mb-0">Pembayaran melalui DANA</p>
                                    </div>
                                </div>
                                <div class="payment-details" id="dana-details">
                                    <h6 class="mb-3">Nomor DANA:</h6>
                                    <div class="list-group">
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <p class="mb-0">087731932494</p>
                                                    <small class="text-muted">a.n. TripMates</small>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('087731932494')">
                                                    <i class="bi bi-clipboard"></i> Salin
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Bukti Pembayaran -->
                            <div class="mt-4">
                                <h5 class="mb-3">Upload Bukti Pembayaran:</h5>
                                <input type="file" class="form-control" name="payment_proof" accept="image/*,.pdf" required>
                                <div class="form-text">Format yang diizinkan: JPG, JPEG, PNG, PDF. Maksimal 5MB.</div>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload"></i> Upload Bukti Pembayaran
                                </button>
                                <a href="destinasi.php?id=<?php echo $trip_id; ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../components/footer.php'; ?>

    <script>
        function selectPayment(method) {
            // Reset semua payment option
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            document.querySelectorAll('.payment-details').forEach(details => {
                details.classList.remove('active');
            });

            // Pilih radio button
            document.getElementById(method).checked = true;

            // Tampilkan detail yang dipilih
            document.getElementById(method + '-details').classList.add('active');
            document.getElementById(method).closest('.payment-option').classList.add('selected');
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Nomor rekening berhasil disalin!');
            }).catch(err => {
                console.error('Gagal menyalin teks: ', err);
            });
        }
    </script>
</body>
</html> 