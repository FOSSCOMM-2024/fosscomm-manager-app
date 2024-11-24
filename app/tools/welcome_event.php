<?php

require '../services/DatabaseService.php';

use app\services\DatabaseService;

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$appConfig = include __DIR__ . '/../config/app_config.php';

$success_message = "";
$databaseService = new DatabaseService();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');


    // Get all the csv data
    $data = [];

    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $email = $row[0];
        $phone = $row[1];
        $specialDiet = $row[2];
        $guidelines = $row[3];

        // if special diet is not set, set it to NO. If it is SPECIAL, set it to YES
        if ($specialDiet == "") { $specialDiet = "NO"; }
        else { $specialDiet = "YES"; }

        // Check if the email exists in the attendees table
        $query = "SELECT * FROM conference_attendees WHERE email = ?";
        $stmt = $databaseService->executeQuery($query, ['s', $email]);

        $result = $databaseService->fetchAll($stmt);

        $registeredToFosscomm = true;

        // if the email exists, update the registered_to_beer_event column
        $query = "UPDATE conference_attendees SET registered_to_beer_event = ? WHERE email = ?";
        $databaseService->executeQuery($query, ['is', true, $email]);

        // If the email does not exist create an entry to the attendees table but make the Registered to fosscomm to false
        if (count($result) == 0) {
            $title = "welcome_event_unregistered_" . date("Y-m-d") . ".log";
            $log_file = fopen($title, "a");
            fwrite($log_file, $email . "\n");
            fclose($log_file);
        }

        $success_message = "Beer event registrations updated successfully!";
    }

    fclose($handle);
}

$search_email = "";
if (isset($_POST['search'])) {
    $search_email = $_POST['search_email'];

    $query = "SELECT * FROM conference_attendees WHERE registered_to_beer_event = ? AND email LIKE ?";
    $stmt = $databaseService->executeQuery($query, ['is', 1, "%$search_email%"]);
    $attendees = $databaseService->fetchAll($stmt);
    $total_records = count($attendees);

    // Close the statement
    $stmt->close();
}
else {
    // Get all the attendees that are registered to beer event
    $query = "SELECT * FROM conference_attendees WHERE registered_to_beer_event = ?";
    $stmt = $databaseService->executeQuery($query, ['i', 1]);
    $attendees = $databaseService->fetchAll($stmt);
    $total_records = count($attendees);
}
// Get how many attendees are checked in
$query = "SELECT COUNT(*) FROM conference_attendees WHERE came_to_beer_event = ?";
$stmt = $databaseService->executeQuery($query, ['i', 1]);
$checked_in = $stmt->get_result()->fetch_row()[0];

// Close the statement
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Update Beer Event Registration</title>
    <link rel="icon" href="https://2024.fosscomm.gr/wp-content/uploads/2024/04/cropped-fosscommIcon-32x32.png" sizes="32x32">
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../../styles/app_styles.css">
</head>
<body>
<!-- Navigation Bar with Logout Button -->
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="../../index.php">
            <h1 class="navbar-brand">
                <img src="<?php echo $appConfig['logo_small'] ?>" alt="logo" width="30" height="30" class="d-inline-block align-top">
                <?php echo $appConfig['app_name']  ?>
            </h1>
        </a>
        <div class="ml-auto">
            <a href="../logout.php" class="btn btn-danger"><i class="bi bi-power"> </i>Logout</a>
        </div>
    </div>
</nav>

<!-- Modal for CSV Upload -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">
                    Update Beer Event Registrations
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Form to upload CSV -->
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="csv_file">Upload CSV File</label>
                        <input type="file" class="form-control-file" id="csv_file" name="csv_file" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);">Upload CSV</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <!-- Display all attendees that are registered to the beer event -->

        </div>

        <h1 class="text-center mb-4 mt-4" style="color: var(--color-accent); width: 100%">Welcome Event Attendees</h1>

        <div style="display: flex; justify-content: space-between; align-items: center; gap: 1em; margin-bottom: 1em; width: 100%">
            <form method="POST" class="input-group">
                <input type="text" name="search_email" class="form-control" placeholder="Search by email" value="<?= htmlspecialchars($search_email) ?>">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit" name="search" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#uploadModal" title="Upload Users from CSV" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);">
                <i class="bi bi-cloud-upload"></i>
            </a>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success mt-3" role="alert">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div style="width: 100%; display: flex; flex-direction: row; justify-content: space-between">
            <p style="color: var(--color-accent)">
                <strong>Total Attendees:</strong> <?= $total_records ?>
            </p>

            <p style="color: var(--color-accent)">
                <strong>Checked In:</strong> <?= $checked_in ?> / <?= $total_records ?>
            </p>
        </div>

        <table class="table table-bordered" style="color: var(--color-accent)">
            <thead class="thead-dark">
            <tr>
                <th>Full Name</th>
                <th>Email</th>
                <th>Details</th>
                <th>Checked In</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($attendees as $attendee): ?>
                <tr>
                    <td class="first-name">
                        <i class="bi bi-eye-fill"> </i>
                        <a href="user_details.php?id=<?= $attendee['id'] ?>" rel="noreferrer" target="_blank" style="color: var(--color-accent); text-decoration: underline">
                            <?= htmlspecialchars($attendee['name']) ?> <?= htmlspecialchars($attendee['surname']) ?>
                        </a>
                    </td>
                    <td class="email"><?= htmlspecialchars($attendee['email']) ?></td>
                    <td class="org">
                        <?= htmlspecialchars($attendee['company']) ?? 'N/A' ?>
                        <?= htmlspecialchars($attendee['title']) ?? 'N/A' ?>
                    </td>

                    <td class="checked-in" style="text-align: center">
                        <?php if ($attendee['came_to_beer_event']): ?>
                            <i class="bi bi-check-circle text-success" style="font-size: 1.5em"></i>
                        <?php else: ?>
                            <i class="bi bi-x-circle text-danger" style="font-size: 1.5em"></i>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bootstrap JS, Popper.js, and jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
