<?php
session_start();
require_once '../config/database.php';

// Data user untuk testing
$test_users = [
    [
        'username' => 'admin',
        'password' => 'password',
        'expected' => 'Login berhasil'
    ],
    [
        'username' => 'admin',
        'password' => 'wrong_password',
        'expected' => 'Login gagal - Password salah'
    ],
    [
        'username' => 'nonexistent',
        'password' => 'password',
        'expected' => 'Login gagal - User tidak ditemukan'
    ],
    [
        'username' => 'admin@tripmates.com',
        'password' => 'password',
        'expected' => 'Login berhasil menggunakan email'
    ]
];

$results = [];

// Fungsi untuk test login
function test_login($db, $username, $password) {
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            return [
                'status' => 'success',
                'message' => 'Login berhasil',
                'user' => $user['username']
            ];
        } else if ($user) {
            return [
                'status' => 'error',
                'message' => 'Login gagal - Password salah'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Login gagal - User tidak ditemukan'
            ];
        }
    } catch(PDOException $e) {
        return [
            'status' => 'error',
            'message' => 'Error database: ' . $e->getMessage()
        ];
    }
}

// Jalankan test jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($test_users as $test_user) {
        $result = test_login($db, $test_user['username'], $test_user['password']);
        $results[] = [
            'test_case' => "Login dengan {$test_user['username']}",
            'expected' => $test_user['expected'],
            'result' => $result['message'],
            'status' => $result['message'] == $test_user['expected'] ? 'passed' : 'failed'
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Login - TripMates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-result {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .test-passed {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .test-failed {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h2 class="mb-4">Test Login TripMates</h2>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Informasi Test</h5>
                <p class="card-text">
                    Halaman ini digunakan untuk menguji fungsi login dengan berbagai skenario:
                </p>
                <ul>
                    <li>Login dengan kredensial valid</li>
                    <li>Login dengan password salah</li>
                    <li>Login dengan username tidak terdaftar</li>
                    <li>Login menggunakan email</li>
                </ul>
            </div>
        </div>

        <form method="POST" class="mb-4">
            <button type="submit" class="btn btn-primary">Jalankan Test</button>
        </form>

        <?php if (!empty($results)): ?>
            <h4>Hasil Test:</h4>
            <?php foreach ($results as $result): ?>
                <div class="test-result <?php echo $result['status'] == 'passed' ? 'test-passed' : 'test-failed'; ?>">
                    <strong>Test Case:</strong> <?php echo htmlspecialchars($result['test_case']); ?><br>
                    <strong>Expected:</strong> <?php echo htmlspecialchars($result['expected']); ?><br>
                    <strong>Result:</strong> <?php echo htmlspecialchars($result['result']); ?><br>
                    <strong>Status:</strong> <?php echo strtoupper($result['status']); ?>
                </div>
            <?php endforeach; ?>

            <div class="mt-4">
                <h5>Ringkasan:</h5>
                <?php
                $passed = count(array_filter($results, function($r) { return $r['status'] == 'passed'; }));
                $total = count($results);
                ?>
                <p>
                    Test berhasil: <?php echo $passed; ?>/<?php echo $total; ?><br>
                    Persentase: <?php echo round(($passed/$total) * 100); ?>%
                </p>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="../pages/login.php" class="btn btn-secondary">Kembali ke Halaman Login</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 