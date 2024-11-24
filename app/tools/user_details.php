<?php

require '../services/DatabaseService.php';

use app\services\DatabaseService;

$appConfig = include __DIR__ . '/../config/app_config.php';
$databaseService = new DatabaseService();

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}


// Check if ID is set
if (!isset($_GET['id'])) {
    die('User not found');
}

$id = $_GET['id'];
$userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';

// Process form submission to update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $received_lunch = $_POST['received_lunch'] ?? 0;
    $came_to_beer_event = $_POST['came_to_beer_event'] ?? 0;
    $check_in_fosscomm = $_POST['checkin_to_conference'] ?? 0;
    $registered_to_beer_event = $_POST['registered_to_beer_event'] ?? 0;

    // Update the database with the new values
    $updateQuery = "UPDATE conference_attendees SET received_lunch = ?, came_to_beer_event = ? , check_in_fosscomm = ?, registered_to_beer_event = ? WHERE id = ?";
    $stmt = $databaseService->executeQuery($updateQuery, ['iiiii', $received_lunch, $came_to_beer_event, $check_in_fosscomm, $registered_to_beer_event, $id]);

    // Redirect to the same page to avoid form resubmission
    header("Location: user_details.php?id=$id");
}

// Fetch user details
$getByIdQuery = "SELECT * FROM conference_attendees WHERE id = ?";
$stmt = $databaseService->executeQuery($getByIdQuery, ['i', $id]);
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>User Details - <?= htmlspecialchars($user['name']) ?> <?= htmlspecialchars($user['surname']) ?></title>
    <link rel="icon" href="https://2024.fosscomm.gr/wp-content/uploads/2024/04/cropped-fosscommIcon-32x32.png" sizes="32x32">

    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../../styles/user_details.css">
</head>
<body>
<!-- Navigation Bar with Logout Button -->
<!-- Navigation Bar with Logout Button -->
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="../../index.php">
            <h1 class="navbar-brand">
                <img src="<?php echo $appConfig['logo_small'] ?>" alt="logo" width="30" height="30" class="d-inline-block align-top">
                <?php echo $appConfig['app_name']  ?>
            </h1>
        </a>

        <!-- Logout should appear only if user is logged in -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="ml-auto">
                <a href="../logout.php" class="btn btn-danger"><i class="bi bi-power"> </i>Logout</a>
            </div>
        <?php endif; ?>
    </div>
</nav>
<div class="container mt-5">
    <div class="user-details">
        <div class="user-info">
            <div class="qr-code">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= $user['qr_code'] ?>" alt="QR Code">
            </div>

            <div class="user-info-details">
                <h2><?= htmlspecialchars($user['name']) ?> <?= htmlspecialchars($user['surname']) ?></h2>
                <p>
                    <strong>Email:</strong> <?= htmlspecialchars($user['email']) ?> <br>
                    <strong>Organization:</strong> <?= htmlspecialchars($user['company']) ? htmlspecialchars($user['company']) : 'N/A' ?> <br>
                    <strong>Role:</strong> <?= htmlspecialchars($user['title']) ? htmlspecialchars($user['title']) : 'N/A' ?> <br>
                </p>

                <?php if (strtoupper($userRole) === 'ADMIN' || strtoupper($userRole) === 'USER'): ?>
                    <table class="table table-bordered">
                        <tr>
                            <th style="color: var(--color-text)">Registered to Conference</th>
                            <td>
                            <span class="status status-<?= $user['registered_to_conference'] ? 'yes' : 'no' ?>">
                                <?= $user['registered_to_conference'] ? 'Yes' : 'No' ?>
                            </span>
                            </td>
                        </tr>
                        <tr>
                            <th style="color: var(--color-text)">Check-In</th>
                            <td>
                            <span class="status status-<?= $user['check_in_fosscomm'] ? 'yes' : 'no' ?>">
                                <?= $user['check_in_fosscomm'] ? 'Yes' : 'No' ?>
                            </span>
                            </td>
                        </tr>
                        <?php if ($user['registered_to_beer_event']): ?>
                        <tr>
                            <th style="color: var(--color-text)">Registered to Beer Event</th>
                            <td>
                            <span class="status status-<?= $user['registered_to_beer_event'] ? 'yes' : 'no' ?>">
                                <?= $user['registered_to_beer_event'] ? 'Yes' : 'No' ?>
                            </span>
                            </td>
                        </tr>
                        <tr>
                            <th style="color: var(--color-text)">Came to Beer Event</th>
                            <td>
                            <span class="status status-<?= $user['came_to_beer_event'] ? 'yes' : 'no' ?>">
                                <?= $user['came_to_beer_event'] ? 'Yes' : 'No' ?>
                            </span>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th style="color: var(--color-text)">Received Lunch</th>
                            <td>
                            <span class="status status-<?= $user['received_lunch'] ? 'yes' : 'no' ?>">
                                <?= $user['received_lunch'] ? 'Yes' : 'No' ?>
                            </span>
                            </td>
                        </tr>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Form to update Received Lunch Status and Came to Beer Event Status -->
        <?php if (strtoupper($userRole) === 'ADMIN' || strtoupper($userRole) === 'USER'): ?>
            <h4 class="update-attendance-status-header">Update Attendance Status</h4>
            <form method="POST" style="display: flex; flex-direction: column; gap: 1rem">
                <div class="form-group">
                    <label for="registered_to_conference" style="color: var(--color-text)">Check-In:</label>
                    <select name="checkin_to_conference" id="checkin_to_conference" class="form-control">
                        <option value="1" <?= $user['check_in_fosscomm'] === 1 ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= $user['check_in_fosscomm'] === 0 ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="received_lunch" style="color: var(--color-text)">Received Lunch:</label>
                    <select name="received_lunch" id="received_lunch" class="form-control">
                        <option value="1" <?= $user['received_lunch'] ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= !$user['received_lunch'] ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="registered_to_beer_event" style="color: var(--color-text)">Registered to Beer Event:</label>
                    <select name="registered_to_beer_event" id="registered_to_beer_event" class="form-control">
                        <option value="1" <?= $user['registered_to_beer_event'] ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= !$user['registered_to_beer_event'] ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="came_to_beer_event" style="color: var(--color-text)">Came to Beer Event:</label>
                    <select name="came_to_beer_event" id="came_to_beer_event" class="form-control">
                        <option value="1" <?= $user['came_to_beer_event'] ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= !$user['came_to_beer_event'] ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="background-color: var(--color-accent); border: none; color: var(--color-text-dark); font-weight: bold; align-self: center">
                    Update Status
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Bootstrap JS, Popper.js, and jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
