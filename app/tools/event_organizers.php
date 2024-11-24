<?php

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Get the app configuration
$appConfig = require '../config/app_config.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>
        Event Organizers - <?php echo $appConfig['app_name'] ?>
    </title>
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

<div class="container mt-5">
    <h1 class="text-center mb-4" style="color: var(--color-accent)">
        Event Organizers
    </h1>

    <table class="table table-bordered" style="color: var(--color-accent);">
        <thead class="thead-dark">
        <tr>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Chat Username</th>
            <th>Role</th>
        </tr>
        </thead>
        <tbody>
        <?php
        // The evnt organizers are stored in the app configuration
        // We can loop through them and display them in a table
        //
        // The app configuration is stored in the $appConfig variable

        foreach ($appConfig['organizers'] as $organizer) {
            echo "<tr>";
            echo "<td>{$organizer['full_name']}</td>";
            echo "<td><i class='bi bi-envelope-at-fill'> </i><a href='mailto:{$organizer['email']}'>{$organizer['email']}</a></td>";
            echo "<td><i class='bi bi-telephone-outbound-fill'> </i><a href='tel:{$organizer['telephone']}'>{$organizer['telephone']}</a></td>";
            echo "<td><i class='bi bi-chat-dots-fill'> </i><a href='http://185.25.22.148:3000/direct/{$organizer['social_profile']}' target='_blank' rel='noreferrer'>{$organizer['social_profile']}</a></td>";
            echo "<td>{$organizer['role']}</td>";
            echo "</tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<!-- Bootstrap JS, Popper.js, and jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
