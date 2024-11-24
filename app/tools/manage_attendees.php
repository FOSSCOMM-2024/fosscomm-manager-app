<?php

use app\services\DatabaseService;

session_start();
include '../services/DatabaseService.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$success_message = "";
$error_message = ""; // To hold error messages

$attendeesDB = new DatabaseService();

$appConfig = include __DIR__ . '/../config/app_config.php';

$userUpdateFields = [
    'name' => 'first_name',
    'surname' => 'last_name',
    'email' => 'email',
    'company' => 'company',
    'title' => 'title',
];

// Handle user update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $fieldsToUpdate = [];

    // Get the user ID
    $user_id = $_POST['user_id'];

    // Get the fields to update (Only the ones that are set)
    foreach ($userUpdateFields as $field => $postKey) {
        $receivedValue = $_POST[$postKey];
        if (empty($receivedValue)) { continue; }

        $fieldsToUpdate[$field] = $_POST[$postKey];

        // if the field is not the id, enclose it in quotes to avoid SQL syntax errors
        if ($field != 'id') { $fieldsToUpdate[$field] = "'$receivedValue'"; }
    }

    try {
        $query = "UPDATE conference_attendees SET name = ?, surname = ?, email = ?, company = ?, title = ? WHERE id = ?";
        $params = ['sssssi', $fieldsToUpdate['name'], $fieldsToUpdate['surname'], $fieldsToUpdate['email'], $fieldsToUpdate['company'], $fieldsToUpdate['title'], $user_id];
        $stmt = $attendeesDB->executeQuery($query, $params);
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }

    // If the checkin status is updated, update the checkin status in the database
    if (isset($_POST['checkin'])) {
        $checkin = $_POST['checkin'];
        $query = "UPDATE conference_attendees SET check_in_fosscomm = ? WHERE id = ?";
        $attendeesDB->executeQuery($query, ['ii', $checkin, $user_id]);
    }

    $success_message = "User updated successfully!";
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    try {
        $query = "DELETE FROM conference_attendees WHERE id = ?";
        $attendeesDB->executeQuery($query, ['i', $user_id]);

        $success_message = "User deleted successfully!";
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Handling the CSV upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');

    // Skip the first row (header)
    fgetcsv($handle);

    while (($data = fgetcsv($handle)) !== false) {
        $first_name = $data[0];
        $last_name = $data[1];
        $email = $data[2];
        $company = isset($data[3]) ? $data[3] : '';
        $title = isset($data[4]) ? $data[4] : '';

        try {
            $query = "INSERT INTO conference_attendees (name, surname, email, company, title, registered_to_conference) VALUES (?, ?, ?, ?, ?, ?)";
            $params = ['sssssi', $first_name, $last_name, $email, $company, $title, true];
            $stmt = $attendeesDB->executeQuery($query, $params);

            $success_message = "Users uploaded successfully!";
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }

    fclose($handle);
}

// Handle manual user addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    require_once '../../api/send_email.php';

    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $company = $_POST['company'];
    $title = $_POST['title'];
    $registered = true;

    try {
        $addQuery = "INSERT INTO conference_attendees (name, surname, email, company, title, registered_to_conference) VALUES (?, ?, ?, ?, ?, ?)";
        $addParams = ['sssssi', $first_name, $last_name, $email, $company, $title, true];
        $addStatement = $attendeesDB->executeQuery($addQuery, $addParams);

        // Get the inserted attendee Details
        $getAttendeeQuery = "SELECT * FROM conference_attendees WHERE email = ?";
        $getAttendeeParams = ['s', $email];
        $stmt = $attendeesDB->executeQuery($getAttendeeQuery, $getAttendeeParams);

        $attendee = $stmt->get_result()->fetch_assoc();

        // Also send an email to the user (1 with the confirmation and 1 with the QR code)
        $receiverEmail = $email;
        $subject = "FOSSCOMM 2024 | Your Access QR Code";
        $qrCodeTemplate = file_get_contents('../../email-templates/email_fosscomm_event_access_qr_code.html');

        // Update the placeholders in the template
        $ebadgeURL = "https://fosscomm.archontis.gr/ebadge.php" . "?id=" . $attendee['id'] . "&token=" . $attendee['ebadge_token'];
        $qr_code = $attendee['qr_code'];
        $qrCodeTemplate = str_replace('{{E_BADGE_URL}}', $ebadgeURL, $qrCodeTemplate);
        $qrCodeTemplate = str_replace('{{QR_CODE_URL}}', $qr_code, $qrCodeTemplate);

        $emailResult = sendEmail($receiverEmail, $subject, $qrCodeTemplate);

        // Check for email sending success
        if ($emailResult['success']) {
            $success_message = "User added successfully and email sent!";
        } else {
            $error_message = "Error sending email: " . $emailResult['error'];
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Check if CSV export is requested
if (isset($_GET['export_csv']) && $_GET['export_csv'] == 'true') {
    // Prepare query to get all attendees
    $query = "SELECT * FROM conference_attendees";
    $result = $attendeesDB->executeQuery($query);

    // Create a filename (the table name and current date/time)
    $filename = 'fosscomm24_attendees_' . date('Y-m-d_H-i-s') . '.csv';

    // Set headers to prompt CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Add CSV header
    fputcsv($output, ['First Name', 'Last Name', 'Email', 'Company', 'Title', 'Registered', 'Checked In', 'Welcome Event', 'Came to Welcome Event', 'QR Code', 'E-badge Token']);

    // Fetch and write each row to the CSV
    $queryResult = $row = $result->get_result();
    $attendees = $queryResult->fetch_all(MYSQLI_ASSOC);

    // Show Attendees (for debugging)
    for ($i = 0; $i < count($attendees); $i++) {
        $name = $attendees[$i]['name'];
        $surname = $attendees[$i]['surname'];
        $email = $attendees[$i]['email'];
        $company = $attendees[$i]['company'];
        $title = $attendees[$i]['title'];
        $registered = $attendees[$i]['registered_to_conference'];
        $checked_in = $attendees[$i]['check_in_fosscomm'];
        $welcome_event = $attendees[$i]['registered_to_beer_event'];
        $came_to_welcome_event = $attendees[$i]['came_to_beer_event'];
        $qr_code = $attendees[$i]['qr_code'];
        $ebadge_token = $attendees[$i]['ebadge_token'];

        fputcsv($output, [$name, $surname, $email, $company, $title, $registered, $checked_in, $welcome_event, $came_to_welcome_event, $qr_code, $ebadge_token]);
    }

    // Close the output stream
    fclose($output);
    exit; // Stop further processing as we are sending a file
}

// Define the number of records per page
$records_per_page = 50;

// Get the current page number from the URL, default to 1 if not set
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start_from = ($current_page - 1) * $records_per_page;

// Initialize search variable
$search_email = '';
$query = '';
$params = [];
$stmt = null;

// Check if a search email is submitted
if (isset($_POST['search'])) {
    $search_email = $_POST['search_email'];
    // Search both the email and name-surame columns
    $query = "SELECT * FROM conference_attendees WHERE email LIKE ? OR CONCAT(name, ' ', surname) LIKE ? LIMIT ?, ?";
    $params = ['ssii', "%$search_email%", "%$search_email%", $start_from, $records_per_page];

    $stmt = $attendeesDB->executeQuery($query, $params);

} else {
    // If no search, select all attendees with pagination
    $query = "SELECT * FROM conference_attendees LIMIT ?, ?";
    $params = ['ii', $start_from, $records_per_page];

    $stmt = $attendeesDB->executeQuery($query, $params);
}

$result = $stmt->get_result();

// Get the total number of records
$total_query = "SELECT COUNT(*) FROM conference_attendees";
$total_stmt = $attendeesDB->executeQuery($total_query);
$total_records = $total_stmt->get_result()->fetch_row()[0];

// Calculate the total number of pages
$total_pages = ceil($total_records / $records_per_page);

// Get the total of the Checked In attendees
$checked_in_query = "SELECT COUNT(*) FROM conference_attendees WHERE check_in_fosscomm = 1";
$checked_in_stmt = $attendeesDB->executeQuery($checked_in_query);
$checked_in = $checked_in_stmt->get_result()->fetch_row()[0];

$attendeesDB->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Upload Users</title>
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

    <!-- Modal for CSV Upload -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload Conference Attendees</h5>
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
                        <button type="submit" class="btn btn-primary">Upload CSV</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Adding User -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add User Manually</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Form to add user manually -->
                    <form method="POST">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="company">Company</label>
                            <input type="text" class="form-control" id="company" name="company">
                        </div>
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" class="form-control" id="title" name="title">
                        </div>
                        <button type="submit" class="btn btn-primary" name="add_user">Add User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Editing User -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Form to edit user details -->
                    <form method="POST" id="editUserForm">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        <div class="form-group">
                            <label for="edit_first_name">First Name</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_last_name">Last Name</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_company">Company</label>
                            <input type="text" class="form-control" id="edit_company" name="company">
                        </div>
                        <div class="form-group">
                            <label for="edit_title">Title</label>
                            <input type="text" class="form-control" id="edit_title" name="title">
                        </div>
                        <div class="form-group">
                            <label for="edit_checkin">Checked In</label>
                            <select class="form-control" id="edit_checkin" name="checkin">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <button class="btn btn-danger" name="delete_user" type="button" onclick="submitDelete()">
                            <i class="bi bi-trash3"></i>
                            Delete
                        </button>

                        <button type="submit" class="btn btn-success" name="update_user">
                            <i class="bi bi-send"></i>
                            Update
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <h1 class="text-center mb-4" style="color: var(--color-accent)">Manage Attendees</h1>

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
            <input type="text" name="search_email" class="form-control" placeholder="Search by email or Full Name" value="<?= htmlspecialchars($search_email) ?>">
            <div class="input-group-append">
                <button class="btn btn-primary" type="submit" name="search" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>

        <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#uploadModal" title="Upload Users from CSV" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);">
            <i class="bi bi-cloud-upload"></i>
        </a>

        <a href="?export_csv=true" class="btn btn-primary" title="Export Users to CSV" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);">
            <i class="bi bi-file-earmark-spreadsheet"></i>
        </a>

        <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#addUserModal" title="Add New Attendee" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);">
            <i class="bi bi-person-plus"></i>
        </a>
    </div>

    <!-- Users Table -->

    <div style="display: flex; flex-direction: row; justify-content: space-between">
        <p style="color: var(--color-accent)">
            <strong>Total Attendees:</strong> <?= $total_records ?>
        </p>

        <p style="color: var(--color-accent)">
            <strong>Checked In:</strong> <?= $checked_in ?> / <?= $total_records ?>
        </p>
    </div>

    <table class="table table-bordered" style="color: var(--color-accent);">
        <thead class="thead-dark">
        <tr>
            <th>Full Name</th>
            <th>Email</th>
            <th>Company</th>
            <th>Role</th>
            <th>Register</th>
            <th>Checkin</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td class="first-name"><?= htmlspecialchars($row['name']) ?> <?= htmlspecialchars($row['surname']) ?></td>
                <td class="email"><?= htmlspecialchars($row['email']) ?></td>
                <td class="company"><?= htmlspecialchars($row['company']) ?></td>
                <td class="title"><?= htmlspecialchars($row['title']) ?></td>
                <td class="<?= $row['registered_to_conference'] ? 'text-success' : 'text-danger' ?>" style="text-align: center">
                    <?= $row['registered_to_conference'] ? '<i class="bi bi-check-circle text-success" style="font-size: 1.5em"></i>' : '<i class="bi bi-x-circle text-danger" style="font-size: 1.5em"></i>' ?>
                </td>

                <td class="<?= $row['check_in_fosscomm'] ? 'text-success' : 'text-danger' ?>" style="text-align: center">
                    <?= $row['check_in_fosscomm'] ? '<i class="bi bi-check-circle text-success" style="font-size: 1.5em"></i>' : '<i class="bi bi-x-circle text-danger" style="font-size: 1.5em"></i>' ?>
                </td>

                <td>
                    <div style="display: flex; gap: 0.5em;">
                        <button class="btn btn-primary btn-sm btn-edit" title="Edit" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);"
                                onclick="populateEditModal(<?= htmlspecialchars(json_encode($row)) ?>)">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <a href="user_details.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm" title="View" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);" rel="noreferrer" target="_blank">
                            <i class="bi bi-eye"></i>
                        </a>

                        <a href="ebadge.php?id=<?= $row['id'] ?>&token=<?= $row['ebadge_token']?>" class="btn btn-primary btn-sm" title="View" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);" rel="noreferrer" target="_blank">
                            <i class="bi bi-person-badge"></i>
                        </a>
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
    function populateEditModal(user) {
        document.getElementById('edit_user_id').value = user.id || '';
        document.getElementById('edit_first_name').value = user.name || 'Unknown Name';
        document.getElementById('edit_last_name').value = user.surname || 'Unknown Surname';
        document.getElementById('edit_email').value = user.email || '';
        document.getElementById('edit_company').value = user.company || 'N/A';
        document.getElementById('edit_title').value = user.title || 'N/A';
        document.getElementById('edit_checkin').value = user.check_in_fosscomm ? 1 : 0;

        // Show the modal
        $('#editUserModal').modal('show');
    }

    function submitDelete() {
        // Set a hidden input to indicate delete action
        let deleteInput = document.createElement("input");
        deleteInput.type = "hidden";
        deleteInput.name = "delete_user";
        deleteInput.value = "1";

        // Add it to the form and submit
        document.getElementById("editUserForm").appendChild(deleteInput);
        document.getElementById("editUserForm").submit();
    }
</script>

</body>
</html>
