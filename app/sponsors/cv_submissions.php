<?php

require '../services/DatabaseService.php';

session_start();
// Make sure that the user is connected
// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Get the user role
$userRole = $_SESSION['user_role'];

$appConfig = include __DIR__ . '/../config/app_config.php';
$success_message = "";
$error_message = "";

// Handle user deletion
if (isset($_POST['delete_user_id'])) {
    $delete_user_id = $_POST['delete_user_id'];

    // Use the DB Service to delete the user
    $databaseService = new \app\services\DatabaseService();
    $query = "DELETE FROM cv_uploads WHERE id = ?";
    $params = ['i', $delete_user_id];

    if ($databaseService->executeQuery($query, $params)) {
        $success_message = "User deleted successfully.";
    } else {
        $error_message = "Failed to delete user.";
    }
}

// Use the DB Service to get the submitted cvs
$databaseService = new \app\services\DatabaseService();

// Initialize search variable
$search_role = '';
$query = '';
$params = [];
$stmt = null;

$records_per_page = 25;
$current_page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($current_page - 1) * $records_per_page;

// Check if search role is submitted
if (isset($_POST['search'])) {
    $search_role = $_POST['search_role'];

    $query = "SELECT * FROM cv_uploads WHERE role LIKE ? LIMIT $start, $records_per_page";
    $params = ['s', '%' . $search_role . '%'];
    $stmt = $databaseService->executeQuery($query, $params);
} else {
    $getAllQuery = "SELECT * FROM cv_uploads LIMIT $start, $records_per_page";
    $stmt = $databaseService->executeQuery($getAllQuery);
}

$result = $stmt->get_result();
$total_records = $result->num_rows;

$total_pages = ceil($total_records / $records_per_page);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>
        CV Submissions
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
    <!-- Modal to show CV PDF -->
    <div class="modal fade" id="cvModal" tabindex="-1" role="dialog" aria-labelledby="cvModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document"> <!-- Larger modal size -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cvModalLabel">
                        CV Viewer
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- PDF embedded in object tag -->
                    <object id="pdf-object" type="application/pdf" data="" width="100%" height="600px">
                        <p>Your browser does not support PDFs. Please download the PDF to view it: <a href="#" id="pdf-download-link">Download PDF</a>.</p>
                    </object>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="pdf-download-link" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);">
                        <i class="bi bi-cloud-arrow-down"> </i>
                        Download
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Download Options -->
    <div class="modal fade" id="downloadOptionsModal" tabindex="-1" role="dialog" aria-labelledby="downloadOptionsModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="downloadOptionsModalLabel">Download Options</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p style="text-align: center">Select your preferred download format:</p>
                    <form method="POST" action="download_handler.php">
                        <div class="form-group" style="display: flex; justify-content: center;">
                            <button type="submit" name="download_pdfs" class="btn btn-primary">
                                <i class="bi bi-file-earmark-pdf"></i> Download All CVs (PDF)
                            </button>
                        </div>
                        <div class="form-group" style="display: flex; justify-content: center;">
                            <button type="submit" name="download_csv" class="btn btn-success">
                                <i class="bi bi-file-earmark-spreadsheet"></i> Download Entries (CSV)
                            </button>
                        </div>

                        <!-- Alert that the CSV download will contain the Location of the CV PDFs on the current server and will not download them -->
                        <div class="alert alert-warning mt-3" role="alert">
                            The CSV download will contain the Location of the CV PDFs on the current server and will not download them.
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Deletion of Submission (only shows on admins) -->
    <!-- Modal for Deletion of Submission (only shows on admins) -->
    <?php if ($userRole === 'admin'): ?>
        <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="downloadOptionsModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete User</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="text-align: center">
                        Are you sure that you want to delete the user?
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" id="deleteUserId" name="delete_user_id" value="">
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>


    <h1 class="text-center mb-4" style="color: var(--color-accent)">
        CV Submissions
    </h1>

    <!-- Display success message if set -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible mt-3" role="alert">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <!-- Display error message if set -->
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible mt-3" role="alert">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1em; margin-bottom: 1em">
        <form method="POST" class="input-group">
            <input type="text" name="search_role" class="form-control" placeholder="Search by General Role (e.g. Fullstack Developer)" value="<?= htmlspecialchars($search_role) ?>">
            <div class="input-group-append">
                <button class="btn btn-primary" type="submit" name="search" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);">
                    <i class="bi bi-search"></i>
                </button>
            </div>

            <!-- Download Options Button -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#downloadOptionsModal" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark); margin-left: 0.5em">
                <i class="bi bi-cloud-arrow-down"></i> Download All
            </button>
        </form>
    </div>

    <!-- Users Table -->
    <p style="color: var(--color-accent)">
        <strong>Total Submissions:</strong> <?= $total_records ?>
    </p>

    <table class="table table-bordered" style="color: var(--color-accent);">
        <thead class="thead-dark">
        <tr>
            <th>Full Name</th>
            <th>Email</th>
            <th>Position</th>
            <th>General Role</th>
            <th>Phone Number</th>
            <th>LinkedIn</th>
            <th>GitHub</th>
            <th>Bio</th>
            <th>CV</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td class="first-name"><?= htmlspecialchars($row['first_name']) ?> <?= htmlspecialchars($row['last_name']) ?></td>
                <td class="email"><?= htmlspecialchars($row['email']) ?></td>
                <td class="position"><?= htmlspecialchars($row['position']) ?></td>
                <td class="title"><?= htmlspecialchars($row['role']) ?></td>
                <td class="phone" style="text-align: center">
                    <a href=<?php echo "tel:" . $row['phone'] ?> >
                        <?php echo htmlspecialchars($row['phone']) ?>
                    </a>
                </td>

                <td class="linked">
                    <a href=<?php echo $row['linkedin'] ?> >
                        LinkedIn Profile
                    </a>
                </td>

                <td class="linked">
                    <a href=<?php echo $row['github'] ?> >
                        Github Profile
                    </a>
                </td>

                <td class="bio">
                    <!-- If the bio is more than X words cut it and add "..." -->
                    <?php
                    $bio = htmlspecialchars($row['bio']);
                    if (str_word_count($bio) > 5) { $bio = substr($bio, 0, 50) . '...'; }
                    echo $bio;
                    ?>
                </td>

                <td class="cv">
                    <button
                            type="button"
                            class="btn btn-primary btn-sm"
                            data-toggle="modal"
                            data-target="#cvModal"
                            data-pdf="<?php echo '../../uploads/cvs/' . $row['cv_filename']; ?>"
                            data-firstname="<?= htmlspecialchars($row['first_name']); ?>"
                            data-lastname="<?= htmlspecialchars($row['last_name']); ?>"
                            title="View"
                            style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);"
                    >
                        View
                    </button>
                </td>

                <td class="cv">
                    <div style="display: flex; gap: 0.5em">
                        <a href="cv_submission_details.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm" title="View Candidate Profile" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);">
                            <i class="bi bi-eye"></i>
                        </a>

                        <!-- Delete Submission Modal Button -->
                        <?php if ($userRole === 'admin'): ?>
                            <button
                                    type="button"
                                    class="btn btn-danger btn-sm"
                                    data-toggle="modal"
                                    data-target="#deleteModal"
                                    data-id="<?= $row['id'] ?>"
                                    title="Delete"
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </td>

            </tr>
        <?php } ?>
        </tbody>
    </table>

    <!-- Pagination Links -->
    <nav aria-label="Page navigation" style="background: none!important;">
        <ul class="pagination justify-content-center">
            <?php if ($current_page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $current_page - 1 . '&search_email=' . urlencode($search_email) ?>">Previous</a>
                </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $current_page === $i ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i . '&search_email=' . urlencode($search_email) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $current_page + 1 . '&search_email=' . urlencode($search_email) ?>">Next</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<!-- Bootstrap JS, Popper.js, and jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
        $('#cvModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var pdfUrl = button.data('pdf');
        var firstName = button.data('firstname');
        var lastName = button.data('lastname');

        // Update modal title
        var modalTitle = 'CV ' + firstName + ' ' + lastName;
        $('#cvModalLabel').text(modalTitle);

        // Set the PDF URL in the object tag and download link
        $('#pdf-object').attr('data', pdfUrl);
        $('#pdf-download-link').attr('href', pdfUrl);
        });

            // Clear the data attribute when modal is closed
            $('#cvModal').on('hidden.bs.modal', function() {
            $('#pdf-object').attr('data', '');
        });

        $('#deleteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var userId = button.data('id'); // Get user ID from button data
            var modal = $(this);
            modal.find('#deleteUserId').val(userId); // Set the value of the hidden input
        });
</script>

</body>
</html>
