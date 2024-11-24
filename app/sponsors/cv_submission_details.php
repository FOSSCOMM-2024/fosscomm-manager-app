<?php
require '../services/DatabaseService.php';

session_start();
// Make sure that the user is connected
// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$appConfig = include __DIR__ . '/../config/app_config.php';

// Fetch the submission ID from the URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id === 0) {
    // Redirect if no valid ID is provided
    header("Location: index.php");
    exit();
}

// Initialize the database service
$databaseService = new \app\services\DatabaseService();

// Query to fetch submission details
$getSubmissionQuery = "SELECT * FROM cv_uploads WHERE id = ?";
$params = ['i', $id];
$stmt = $databaseService->executeQuery($getSubmissionQuery, $params);

// Fetch the result
$row = $databaseService->fetchAll($stmt)[0];

if (!$row) {
    // If no submission is found, show a 404 or error message
    echo "No submission found!";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo 'CV ' . htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']); ?></title>
    <link rel="icon" href="https://2024.fosscomm.gr/wp-content/uploads/2024/04/cropped-fosscommIcon-32x32.png" sizes="32x32">

    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../styles/app_styles.css">

    <link rel="stylesheet" href="../../styles/cv_submission_details.css">
</head>
<body>

<!-- Navigation Bar with Back Button -->
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="../../index.php">
            <h1 class="navbar-brand">
                <img src="<?php echo $appConfig['logo_small'] ?>" alt="logo" width="30" height="30" class="d-inline-block align-top">
                <?php echo $appConfig['app_name']  ?>
            </h1>
        </a>
        <div class="ml-auto">
            <a href="javascript:history.back()" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="row">
        <!-- Left Column with Contact Info -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h4>Contact Info</h4>
                    <p><i class="bi bi-envelope"></i> <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>"><?php echo htmlspecialchars($row['email']); ?></a></p>
                    <p><i class="bi bi-phone"></i> <a href="tel:<?php echo htmlspecialchars($row['phone']); ?>"><?php echo htmlspecialchars($row['phone']); ?></a></p>
                    <p><i class="bi bi-linkedin"></i> <a href="<?php echo htmlspecialchars($row['linkedin']); ?>" target="_blank">LinkedIn</a></p>
                    <p><i class="bi bi-github"></i> <a id="githubUrl" href="<?php echo htmlspecialchars($row['github']); ?>" target="_blank">GitHub</a></p>
                </div>
            </div>

            <!-- Placeholder for the GitHub Profile Card -->
            <div id="github-profile-card" class="card mt-4">
                <div class="card-header">
                    <h5><i class="bi bi-github"></i> GitHub Profile</h5>
                </div>
                <div class="card-body">
                    <div class="github-card">
                        <img id="githubIMG" src="">
                        <div class="git-prof-details" id="github-profile-body">
                            <!-- GitHub profile details will be added here -->
                            <p>
                                Loading Github Profile...
                            </p>
                        </div>
                    </div>
                    
                </div>
            </div>

            <!-- Error Message (if any) -->
            <div id="github-error" class="alert alert-warning mt-4" role="alert" style="display: none;">
                GitHub profile not available or user not found.
            </div>
        </div>

        <!-- Right Column with Profile Details -->
        <div class="col-md-8" style="color: var(--color-text)">
            <h2 style="color: var(--color-accent); font-weight: bold;"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></h2>
            <p><strong>Current Position:</strong> <?php echo htmlspecialchars($row['position']); ?></p>
            <p><strong>General Role:</strong> <?php echo htmlspecialchars($row['role']); ?></p>

            <!-- Custom Role (if available) -->
            <?php if (!empty($row['custom_role'])): ?>
                <p><strong>Custom Role:</strong> <?php echo htmlspecialchars($row['custom_role']); ?></p>
            <?php endif; ?>

            <!-- Bio Section -->
            <h4>Bio</h4>
            <p><?php echo htmlspecialchars($row['bio']); ?></p>

            <!-- CV View -->
            <div class="mt-4">
                <a href="<?php echo '../../uploads/cvs/' . htmlspecialchars($row['cv_filename']); ?>" class="btn" target="_blank" style="background-color: var(--color-accent); color: var(--color-text-dark)">
                    <i class="bi bi-file-earmark-pdf"></i> View CV
                </a>

                <a href="<?php echo '../../uploads/cvs/' . htmlspecialchars($row['cv_filename']); ?>" class="btn" download style="background-color: var(--color-accent); color: var(--color-text-dark)">
                    <i class="bi bi-download"></i> Download CV
                </a>
            </div>

            <!-- CV Download -->
            <div class="mt-2">

            </div>

            <!-- Submission Date -->
            <p class="mt-3"><strong>Submission Date:</strong> <?php echo htmlspecialchars($row['submission_date']); ?></p>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    // Function to fetch and display GitHub data
    function fetchGithubProfile(username) {
        // GitHub API endpoint
        const apiUrl = `https://api.github.com/users/${username}`;

        // Fetch GitHub data using fetch API
        fetch(apiUrl, {
            headers: {
                'User-Agent': 'PHP-Client'  // GitHub requires a User-Agent header
            }
        })
            .then(response => {
                // Check if the response is successful
                if (!response.ok) {
                    throw new Error(`GitHub profile not found: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                // Add the users image to the card
                const githubProfileImage = document.getElementById('githubIMG');
                githubProfileImage.src = data.avatar_url;


                // Populate the GitHub card with the user's data
                const githubProfileBody = document.getElementById('github-profile-body');
                githubProfileBody.innerHTML = `
                <h3><a href="${data.html_url}">${data.login}</a></h3>
                <p><strong><i class="bi bi-geo-alt"> </i>Location:</strong> ${data.location}</p>
                <p><strong><i class="bi bi-file-earmark-code"> </i>Repos:</strong> ${data.public_repos}</p>
                <p><strong><i class="bi bi-people"> </i>Followers:</strong> ${data.followers}</p>
                <p><strong><i class="bi bi-people"> </i>Following:</strong> ${data.following}</p>
            `;

                // Show the card and hide the error message
                document.getElementById('github-profile-card').style.display = 'block';
                document.getElementById('github-error').style.display = 'none';
            })
            .catch(error => {
                // Show error message if something goes wrong
                console.error('Error fetching GitHub data:', error);
                document.getElementById('github-error').style.display = 'block';
                document.getElementById('github-profile-card').style.display = 'none';
            });
    }

    // Get the GitHub username
    const githubProfileURL = '<?php echo htmlspecialchars($row['github']); ?>';
    const githubUsername = githubProfileURL.split('/').at(3)

    // Call the function to fetch and display the GitHub profile if username is not empty
    if (githubUsername) {
        fetchGithubProfile(githubUsername);
    }
</script>

</body>
</html>
