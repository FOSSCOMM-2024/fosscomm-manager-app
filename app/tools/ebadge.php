<?php

require '../services/DatabaseService.php';

use app\services\DatabaseService;

$appConfig = include __DIR__ . '/../config/app_config.php';
$databaseService = new DatabaseService();

// Check if ID is set
if (!isset($_GET['id'])) {
    die('User not found');
}

// Check if the user token is set
if (!isset($_GET['token'])) {
    http_response_code(401);
    die('Unauthorized');
}

$id = $_GET['id'];
$userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';

// First get the token and check if the attendee ID matches for the token
$token = $_GET['token'];
$attendeeID = $_GET['id'];

$checkTokenQuery = "SELECT * FROM conference_attendees WHERE id = $attendeeID AND ebadge_token = '$token'";
$stmt = $databaseService->executeQuery($checkTokenQuery, []);

// Get the result
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(401);
    die('Unauthorized');
}

// Fetch user details
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>E-Badge - FOSSCOMM 2024</title>
    <link rel="icon" href="https://2024.fosscomm.gr/wp-content/uploads/2024/04/cropped-fosscommIcon-32x32.png" sizes="32x32">

    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../../styles/ebadge.css">
</head>
<body style="padding: 0">
<div class="page-container">

    <div class="e-badge">
        <div class="badge-header">
            <h1>
                Thessaloniki, Greece 2024
            </h1>
            <img src="https://2024.fosscomm.gr/wp-content/uploads/2024/11/logo_a2.png" alt="FOSSCOMM 2024">
        </div>

        <div class="badge-info-container">
            <img class="qr" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= $user['qr_code'] ?>">

            <h2 style="text-align: center">
                <?= htmlspecialchars($user['name']) ?> <?= htmlspecialchars($user['surname']) ?>
            </h2>

            <p style="text-align: center">
                <?= htmlspecialchars($user['title']) ? htmlspecialchars($user['title']) : 'N/A' ?> @ <?= htmlspecialchars($user['company']) ? htmlspecialchars($user['company']) : 'N/A' ?>
            </p>
            <p class="email" style="text-align: center">
                <?= htmlspecialchars($user['email']) ?>
            </p>
        </div>
    </div>
</div>

<!-- Bootstrap JS, Popper.js, and jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
