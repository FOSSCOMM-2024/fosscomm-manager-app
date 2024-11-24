<?php

require 'app/services/DatabaseService.php';

use app\services\DatabaseService;

session_start();
//include 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

$appConfig = include __DIR__ . '/app/config/app_config.php';

$databaseService = new DatabaseService();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Query DB for user
    $getUserQuery = "SELECT * FROM users WHERE username = ?";
    $stmt = $databaseService->executeQuery($getUserQuery, ['s', $username]);
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: home.php');
            exit;
        } else {
            $error = "Invalid credentials!";
        }
    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login</title>
    <link rel="icon" href="https://2024.fosscomm.gr/wp-content/uploads/2024/04/cropped-fosscommIcon-32x32.png" sizes="32x32">
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="styles/app_styles.css">

    <!-- MANIFEST FOR PWA -->
    <link rel="manifest" href="manifest.json">
</head>
<body style="height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card" style="border: none">
                <div class="card-header text-center" style="background-color: var(--color-border)">
                    <h3>
                        <img src="<?php echo $appConfig['logo_large'] ?>" alt="logo" class="d-inline-block align-top" style="object-fit: contain; height: 10vh;">
                    </h3>
                </div>
                <div class="card-body" style="background-color: #383443">
                    <form method="POST">
                        <div class="form-group">
                            <label for="username" style="color: var(--color-accent)">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password" style="color: var(--color-accent)">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);">Login</button>
                    </form>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger mt-3" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Click here to go to the download page -->
    <div class="row justify-content-center mt-3">
        <a href='app/download.php'" class="text-center" style="color: var(--color-accent)">Click here to download the Application</a>
    </div>
</div>

<!-- Bootstrap JS, Popper.js, and jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
