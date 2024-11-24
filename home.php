<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$appConfig = include __DIR__ . '/app/config/app_config.php';

$userRole = $_SESSION['user_role'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Home</title>

    <link rel="icon" href="https://2024.fosscomm.gr/wp-content/uploads/2024/04/cropped-fosscommIcon-32x32.png" sizes="32x32">

    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="styles/app_styles.css">
</head>
<body style="min-height: 100vh">

<!-- Navigation Bar with Logout Button -->
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <h1 class="navbar-brand">
                <img src="<?php echo $appConfig['logo_small'] ?>" alt="logo" width="30" height="30" class="d-inline-block align-top">
                <?php echo $appConfig['app_name']  ?>
            </h1>
        </a>
        <div class="ml-auto">
            <a href="./app/logout.php" class="btn btn-danger"><i class="bi bi-power"> </i>Logout</a>
        </div>
    </div>
</nav>

<!-- Main Content Section -->
<div class="container mt-5" style="min-height: 75vh">
    <div class="row">

        <!-- ADMIN AND SIMPLE USERS FUNTIONALITY -->
        <?php if ($userRole === 'admin' || $userRole === 'user') : ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 tool-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">
                            <i class="bi bi-people"></i>
                            <strong>Event Attendees</strong>
                        </h5>
                        <p class="card-text">Manage and upload attendee details for the conference.</p>
                        <a href="app/tools/manage_attendees.php" class="btn btn-primary" style="background-color: var(--color-primary-light); border: none">Go to Tool</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card h-100 tool-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">
                            <i class="bi bi-emoji-smile"></i>
                            <strong>Event Volunteers</strong>
                        </h5>
                        <p class="card-text">Manage and upload volunteer details for the conference.</p>
                        <a href="app/tools/manage_volunteers.php" class="btn btn-primary" style="background-color: var(--color-primary-light); border: none">Go to Tool</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card h-100 tool-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">
                            <i class="bi bi-person-video2"></i>
                            <strong>Event Organizers</strong>
                        </h5>
                        <p class="card-text">
                            View the organizers of the conference and their contact details.
                        </p>
                        <a href="app/tools/event_organizers.php" class="btn btn-primary" style="background-color: var(--color-primary-light); border: none">Go to Tool</a>
                    </div>
                </div>
            </div>

            <!-- Update Beer Event Registration Card -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 tool-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">
                            <i class="bi bi-music-note-beamed"></i>
                            <strong>Beer Event Registrations</strong>
                        </h5>
                        <p class="card-text">Update and manage registration for the Beer Event.</p>
                        <a href="app/tools/welcome_event.php" class="btn btn-primary" style="background-color: var(--color-primary-light); border: none">Go to Tool</a>
                    </div>
                </div>
            </div>

            <!-- Generate QR Codes Card -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 tool-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">
                            <i class="bi bi-qr-code"></i>
                            <strong>QR Code Reader</strong>
                        </h5>
                        <p class="card-text">
                            Read an attendee's QR code to view their details and validate their attendance.
                        </p>
                        <a href="app/tools/qr_code_reader.php" class="btn btn-primary" style="background-color: var(--color-primary-light); border: none">Go to Tool</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card h-100 tool-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">
                            <i class="bi bi-image"></i>
                            <strong>Post Image Creator</strong>
                        </h5>
                        <p class="card-text">
                            Create images for the conference's social media posts (External Tool).
                        </p>
                        <a href="https://post-img-maker.vercel.app/" class="btn btn-primary" rel="noreferrer" target="_blank" style="background-color: var(--color-primary-light); border: none">
                            Go to Tool
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card h-100 tool-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">
                            <i class="bi bi-file-earmark-person"></i>   
                            <strong>
                                All FOSSCOMM 2024 Talks
                            </strong>
                        </h5>
                        <p class="card-text">
                            View all the talks that will be presented at FOSSCOMM 2024.
                        </p>
                        <a href="app/tools/view_talks.php" class="btn btn-primary" rel="noreferrer" target="_blank" style="background-color: var(--color-primary-light); border: none">
                            Go to Tool
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ADMIN ONLY FUNCTIONALITY -->
        <?php if ($userRole === 'admin'): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 tool-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">
                            <i class="bi bi-person-lock"></i>
                            <strong>
                                Manage App Users
                            </strong>
                        </h5>
                        <p class="card-text">
                            Manage and update the users that can access the conference app.
                        </p>
                        <a href="./app/admin/admin.php" class="btn btn-primary" rel="noreferrer" target="_blank" style="background-color: var(--color-primary-light); border: none">
                            Go to Tool
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- SPONSORS FUNCTIONALITY -->
        <?php if ($userRole === 'sponsor' || $userRole === 'admin'): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 tool-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">
                            <i class="bi bi-file-earmark-person"> </i>
                            <strong>
                                View CV Submissions
                            </strong>
                        </h5>
                        <p class="card-text">
                            View the CVs submitted by FOSSCOMM 2024 Attendees.
                        </p>
                        <a href="./app/sponsors/cv_submissions.php" class="btn btn-primary" style="background-color: var(--color-primary-light); border: none">
                            Go to Tool
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!--Beautiful footer (to bottom)-->
<footer class="text-center text-lg-start">
    <div class="text-center p-1">
        <p>
            <?php echo $appConfig['app_name'] ?> - 2024
        </p>
        <p>
            Made by <a href="https://opensource.uom.gr" class="text-light" target="_blank" rel="noreferrer">OpenSource UoM</a>
        </p>
    </div>
</footer>

<!-- Bootstrap JS, Popper.js, and jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
