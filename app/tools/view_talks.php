<?php
// Database connection
require '../services/DatabaseService.php';  // Include your database connection file
use app\services\DatabaseService;

// Load the app configuration
$appConfig = require '../config/app_config.php';

// Fetch the presentation details from the database
$dbService = new DatabaseService();
$query = "SELECT * FROM presentation_uploads ORDER BY submission_date DESC";  // Sort by submission date
$presentations = $dbService->executeQuery($query);
$presentations = $presentations->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FOSSCOMM 2024 Talks</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <link rel="stylesheet" href="../../styles/app_styles.css">
    <link rel="stylesheet" href="../../styles/view_talks.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="../../index.php">
            <h1 class="navbar-brand">
                <img src="<?php echo $appConfig['logo_small'] ?>" alt="logo" width="30" height="30" class="d-inline-block align-top">
                <?php echo str_replace("Admin", "2024", $appConfig['app_name']) ?>
            </h1>
        </a>
    </div>
</nav>

<div class="container">
    <h1 class="talks-header" style="text-align: center">All FOSSCOMM 2024 Talks</h1>

    <?php if (!empty($presentations)) : ?>
        <div class="talks">
            <?php foreach ($presentations as $presentation) : ?>
                <div class="card" style="width: 18rem;">
                    <div class="card-body">
                        <h5 class="card-title">
                            <?= htmlspecialchars($presentation['talk_title']) ?>
<!--                            <span class="badge badge-primary">--><?php //= htmlspecialchars($presentation['organization']) ?><!--</span>-->
                        </h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            Speaker(s): <?= htmlspecialchars($presentation['speaker_name']) ?>
                        </h6>

                        <a href="../../uploads/presentations/<?= htmlspecialchars($presentation['presentation_filename']) ?>" class="card-link" target="_blank">
                            <i class="bi bi-download"></i> Download
                        </a>

                        <?php if (!empty($presentation['speaker_email']) && $presentation['share_email']) : ?>
                            <a href="mailto:<?= htmlspecialchars($presentation['speaker_email']) ?>" class="card-link">
                                <i class="bi bi-envelope"></i> Contact Speaker
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No presentations available yet. Check back later!</p>
    <?php endif; ?>
</div>

<!-- Bootstrap JS and jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
