<?php

use app\services\DatabaseService;

session_start();
include '../services/DatabaseService.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$appConfig = include __DIR__ . '/../config/app_config.php';

$success_message = "";
$error_message = "";

$dataService = new DatabaseService();

// Handle user update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email']; // Added email for update
    $phone = $_POST['phone']; // Added phone for update

    $updateQuery = 'UPDATE conference_volunteers SET first_name=?, last_name=?, email=?, phone=? WHERE id=?';
    $stmt = $dataService->executeQuery($updateQuery, ['ssssi', $first_name, $last_name, $email, $phone, $user_id]);

    // If set, update the available status
    if (isset($_POST['available'])) {
        $available = $_POST['available'];
        $updateAvailableQuery = 'UPDATE conference_volunteers SET available=? WHERE id=?';
        $stmt = $dataService->executeQuery($updateAvailableQuery, ['ii', $available, $user_id]);
    }

    // If set, update the role
    if (isset($_POST['role'])) {
        $role = $_POST['role'];
        $updateRoleQuery = 'UPDATE conference_volunteers SET role=? WHERE id=?';
        $stmt = $dataService->executeQuery($updateRoleQuery, ['si', $role, $user_id]);
    }

    $stmt->close();
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    $deleteQuery = 'DELETE FROM conference_volunteers WHERE id=?';
    $stmt = $dataService->executeQuery($deleteQuery, ['i', $user_id]);
    $stmt->close();

    if ($stmt) {
        $success_message = "User deleted successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
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
        $phone = $data[3];

        // Check for duplicate email
        $selectQuery = 'SELECT id FROM conference_volunteers WHERE email = ?';
        $check_stmt = $dataService->executeQuery($selectQuery, ['s', $email]);
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $error_message = "Email '$email' already exists. Skipping this entry.";
            continue; // Skip this entry
        }

        // Insert into conference_volunteers
        $insertQuery = 'INSERT INTO conference_volunteers (first_name, last_name, email, phone) VALUES (?, ?, ?, ?)';
        $stmt = $dataService->executeQuery($insertQuery, ['ssss', $first_name, $last_name, $email, $phone]);

        if (!$stmt) {
            $error_message = "Error: " . $stmt->error;
            break;
        }
    }
    fclose($handle);
    $success_message = "Users uploaded successfully!";
}

// Handle manual user addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    // Check for duplicate email
    $addQuery = 'SELECT id FROM conference_volunteers WHERE email = ?';
    $stmt = $dataService->executeQuery($addQuery, ['s', $email]);
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error_message = "Email '$email' already exists. Please use a different email.";
    } else {
        // Insert into conference_volunteers
        $addUserQuery = 'INSERT INTO conference_volunteers (first_name, last_name, email, phone) VALUES (?, ?, ?, ?)';
        $stmt = $dataService->executeQuery($addUserQuery, ['ssss', $first_name, $last_name, $email, $phone]);

        if ($stmt) { $success_message = "User added successfully!"; }
        else { $error_message = "Error: " . $stmt->error; }
    }
}

// Check if CSV export is requested
if (isset($_GET['export_csv']) && $_GET['export_csv'] == 'true') {
    // Prepare query to get all attendees
    $query = "SELECT * FROM conference_volunteers";
    $result = $dataService->executeQuery($query);

    // Create a filename (the table name and current date/time)
    $filename = 'fosscomm24_volunteers_' . date('Y-m-d_H-i-s') . '.csv';

    // Set headers to prompt CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Add CSV header
    fputcsv($output, ['First Name', 'Last Name', 'Email', 'Phone', 'Available', 'Role']);

    // Fetch and write each row to the CSV
    $queryResult = $row = $result->get_result();
    $volunteers = $queryResult->fetch_all(MYSQLI_ASSOC);

    // Show Attendees (for debugging)
    for ($i = 0; $i < count($volunteers); $i++) {
        $name = $volunteers[$i]['first_name'];
        $surname = $volunteers[$i]['last_name'];
        $email = $volunteers[$i]['email'];
        $phone = $volunteers[$i]['phone'];
        $available = $volunteers[$i]['available'];
        $role = $volunteers[$i]['role'];

        fputcsv($output, [$name, $surname, $email, $phone, $available, $role]);
    }

    // Close the output stream
    fclose($output);
    exit; // Stop further processing as we are sending a file
}

// Define the number of records per page
$records_per_page = 25;

// Get the current page number from the URL, default to 1 if not set
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start_from = ($current_page - 1) * $records_per_page;

// Initialize search variable
$search_email = '';

// Check if a search email is submitted
if (isset($_POST['search'])) {
    $search_email = $_POST['search_email'];
    $selectQuery = 'SELECT * FROM conference_volunteers WHERE email LIKE ? LIMIT ?, ?';
    $search_param = "%" . $search_email . "%";
    $stmt = $dataService->executeQuery($selectQuery, ['sii', $search_param, $start_from, $records_per_page]);

    // Results
    $result = $stmt->get_result();
} else {
    // If no search, select all attendees with pagination
    $selectQuery = 'SELECT * FROM conference_volunteers LIMIT ?, ?';
    $stmt = $dataService->executeQuery($selectQuery, ['ii', $start_from, $records_per_page]);

    // Results
    $result = $stmt->get_result();
}

// Get the total number of records for pagination
$query = 'SELECT COUNT(*) FROM conference_volunteers' . (isset($_POST['search_email']) ? ' WHERE email LIKE ?' : '');
$stmt = $dataService->executeQuery($query, isset($_POST['search_email']) ? ['s', $search_param] : []);
$total_records = $stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_records / $records_per_page);

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Manage Volunteers</title>
    <link rel="icon" href="https://2024.fosscomm.gr/wp-content/uploads/2024/04/cropped-fosscommIcon-32x32.png" sizes="32x32">

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
    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload Volunteers from CSV</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
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

    <!-- Edit User Modal -->
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
                            <label for="edit_phone">Phone</label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone">
                        </div>

                        <div class="form-group">
                            <label for="edit_available">Available</label>
                            <select class="form-control" id="edit_available" name="available">
                                <option value="1">
                                    Yes
                                </option>
                                <option value="0">
                                    No
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_role">Role</label>
                            <select class="form-control" id="edit_role" name="role">
                                <option value="none">
                                    None
                                </option>
                                <option value="info">
                                    Registration/Info Desk
                                </option>
                                <option value="runner">
                                    Runner
                                </option>
                                <option value="tech">
                                    Tech Assistant
                                </option>
                                <option value="lunch">
                                    Lunch Assistant
                                </option>
                                <option value="speakerSupport">
                                    Speaker Support
                                </option>
                                <option value="talk">
                                    Talk Announcer
                                </option>
                                <option value="chat">
                                    Chat Moderator
                                </option>
                            </select>
                        </div>

                        <button type="button" class="btn btn-danger" name="delete_user" onclick="submitDelete()">
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

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add User Manually</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
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
                            <label for="phone">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <button type="submit" class="btn btn-primary" name="add_user">Add User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <h1 class="text-center mb-4" style="color: var(--color-accent)">Manage Volunteers</h1>

    <!-- Search and General Actions -->
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1em; margin-bottom: 1em">
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

        <a href="?export_csv=true" class="btn btn-primary" title="Export Volunteers to CSV" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);">
            <i class="bi bi-file-earmark-spreadsheet"></i>
        </a>

        <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#addUserModal" title="Add New Attendee" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);">
            <i class="bi bi-person-plus"></i>
        </a>
    </div>

    <!-- Show Available Only Checkbox -->
    <div>
        <label style="color: #ea95ff">
            <input type="checkbox" id="showAvailableOnly"> Show Available Only
        </label>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible mt-3" role="alert">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible mt-3" role="alert">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <table class="table table-bordered" style="color: var(--color-accent)">
        <thead class="thead-dark">
        <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone Number</th>
            <th>Available</th>
            <th>Role(s)</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr class="<?= $row['available'] == 1 ? 'available' : 'not-available' ?>">
                <td class="registration-id"><?= $row['id'] ?></td>
                <td class="first-name"><?= htmlspecialchars($row['first_name']) ?> <?= htmlspecialchars($row['last_name']) ?></td>
                <td class="email"><?= htmlspecialchars($row['email']) ?></td>
                <td class="phone">
                    <a href="tel:<?= htmlspecialchars($row['phone']) ?>">
                        <?= htmlspecialchars($row['phone']) ?>
                    </a>
                </td>

                <td style="text-align: center">
                    <?php if ($row['available'] == 1): ?>
                        <i class="bi bi-check-circle text-success" style="font-size: 1.5em"></i>
                    <?php else: ?>
                        <i class="bi bi-x-circle text-danger" style="font-size: 1.5em"></i>
                    <?php endif; ?>
                </td>

                <td>
                    <?=$row['role'] ?>
                </td>

                <td>
                    <div style="display: flex; gap: 0.5em;">
                        <button class="btn btn-primary btn-sm btn-edit" title="Edit" style="background-color: var(--color-accent-dark); border: var(--color-accent-dark);"
                                onclick="populateEditModal(<?= htmlspecialchars(json_encode($row)) ?>)">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    </div>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

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

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    // Populate the edit modal with user details
    function populateEditModal(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_first_name').value = user.first_name;
        document.getElementById('edit_last_name').value = user.last_name;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_phone').value = user.phone;
        document.getElementById('edit_available').value = user.available;
        document.getElementById('edit_role').value = user.role.toLowerCase();

        $('#editUserModal').modal('show');
    }

    // Delete Action Submission
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

    // Show Available Only Checkbox
    document.getElementById('showAvailableOnly').addEventListener('change', function() {
        let rows = document.querySelectorAll('table tbody tr');

        rows.forEach(row => {
            if (this.checked) {
                // If "Show Available Only" is checked, hide the rows that are not available
                if (row.classList.contains('not-available')) {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            } else {
                // If unchecked, show all rows
                row.style.display = '';
            }
        });
    });
</script>

</body>
</html>
