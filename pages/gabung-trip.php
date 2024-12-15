<?php
session_start();
require_once '../config/database.php';

// Redirect jika belum login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit();
}

// Ambil ID trip dari parameter URL
$trip_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$trip_id) {
    header('Location: ../index.php');
    exit();
}

try {
    // Cek apakah trip ada dan masih tersedia
    $stmt = $db->prepare("
        SELECT t.*, 
               (SELECT COUNT(*) FROM trip_participants WHERE trip_id = t.id AND status = 'approved') as current_participants
        FROM trips t 
        WHERE t.id = ?
    ");
    $stmt->execute([$trip_id]);
    $trip = $stmt->fetch();

    if (!$trip) {
        $_SESSION['error'] = "Trip tidak ditemukan.";
        header('Location: ../index.php');
        exit();
    }

    // Cek apakah user sudah pernah bergabung
    $stmt = $db->prepare("
        SELECT status FROM trip_participants 
        WHERE trip_id = ? AND user_id = ?
    ");
    $stmt->execute([$trip_id, $_SESSION['user_id']]);
    $existing_request = $stmt->fetch();

    if ($existing_request) {
        $_SESSION['error'] = "Anda sudah mengajukan permintaan untuk trip ini.";
        header('Location: destinasi.php?id=' . $trip_id);
        exit();
    }

    // Cek apakah masih ada slot tersedia
    if ($trip['current_participants'] >= $trip['max_participants']) {
        $_SESSION['error'] = "Maaf, trip ini sudah penuh.";
        header('Location: destinasi.php?id=' . $trip_id);
        exit();
    }

    // Cek apakah user adalah pembuat trip
    if ($trip['user_id'] == $_SESSION['user_id']) {
        $_SESSION['error'] = "Anda tidak bisa bergabung dengan trip yang Anda buat sendiri.";
        header('Location: destinasi.php?id=' . $trip_id);
        exit();
    }

    // Proses permintaan bergabung
    $stmt = $db->prepare("
        INSERT INTO trip_participants (trip_id, user_id, status) 
        VALUES (?, ?, 'pending')
    ");
    if ($stmt->execute([$trip_id, $_SESSION['user_id']])) {
        $_SESSION['success'] = "Permintaan bergabung berhasil dikirim. Menunggu persetujuan pembuat trip.";
        header('Location: profil.php#joined');
        exit();
    }

} catch(PDOException $e) {
    $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    header('Location: destinasi.php?id=' . $trip_id);
    exit();
}
?>