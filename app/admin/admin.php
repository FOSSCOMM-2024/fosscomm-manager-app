<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ../home.php');
    exit;
}

$appConfig = include __DIR__ . '/../config/app_config.php';

$success_message = "";
$errorMsg = "";

require '../services/DatabaseService.php';
use app\services\DatabaseService;

$databaseService = new DatabaseService();

// Handle User Creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['user_id']) && isset($_POST['create_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Check if user already exists
    $getUserQuery = "SELECT * FROM users WHERE username = ?";
    $stmt = $databaseService->executeQuery($getUserQuery, ['s', $username]);
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $errorMsg = "User already exists!";
    } else {
        // Insert user into DB
        $insertUserQuery = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $databaseService->executeQuery($insertUserQuery, ['sss', $username, $hashedPassword, $role]);

        if ($stmt->affected_rows > 0) {
            $success_message = "User created successfully!";
        } else {
            $errorMsg = "Failed to create user!";
        }
    }
}

// After user submits the edit form
if (isset($_POST['user_id'])) {
    $userId = $_POST['user_id'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    $newPassword = $_POST['password']; // This will be empty if not changed

    if (!empty($newPassword)) {
        // If a new password is provided, hash it
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateUserQuery = "UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?";
        $stmt = $databaseService->executeQuery($updateUserQuery, ['sssi', $username, $hashedPassword, $role, $userId]);
    } else {
        // If no new password, only update username and role
        $updateUserQuery = "UPDATE users SET username = ?, role = ? WHERE id = ?";
        $stmt = $databaseService->executeQuery($updateUserQuery, ['ssi', $username, $role, $userId]);
    }

    // Check for success and display message
    if ($stmt->affected_rows > 0) {
        $success_message = "User updated successfully!";
    } else {
        $errorMsg = "Failed to update user!";
    }
}

// Handle User Deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user']) && isset($_POST['delete_user_id'])) {
    $userIdToDelete = $_POST['delete_user_id'];
    $deleteUserQuery = "DELETE FROM users WHERE id = ?";
    $stmt = $databaseService->executeQuery($deleteUserQuery, ['i', $userIdToDelete]);

    if ($stmt->affected_rows > 0) {
        $success_message = "User deleted successfully!";
    } else {
        $errorMsg = "Failed to delete user!";
    }
}


// Get all users
$getAllUsersQuery = "SELECT * FROM users";
$stmt = $databaseService->executeQuery($getAllUsersQuery);
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin Panel</title>
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
                <?php echo $appConfig['app_name'] ?>
            </h1>
        </a>
        <div class="ml-auto">
            <a href="../logout.php" class="btn btn-danger"><i class="bi bi-power"> </i>Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="post" id="editUserForm">
                        <div class="form-group">
                            <label for="edit-username">Username</label>
                            <input type="text" class="form-control" id="edit-username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-password">Password (leave blank to keep current password)</label>
                            <input type="password" class="form-control" id="edit-password" name="password">
                        </div>
                        <div class="form-group">
                            <label for="edit-role">Role</label>
                            <select class="form-control" id="edit-role" name="role" required>
                                <option value="">Select Role</option>
                                <?php foreach ($appConfig['user_roles'] as $role): ?>
                                    <option value="<?php echo $role ?>"><?php echo $role ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input type="hidden" id="edit-user-id" name="user_id">
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this user?</p>
                    <form method="post" id="deleteUserForm">
                        <input type="hidden" id="delete-user-id" name="delete_user_id">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="delete_user" value="true">Delete User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" role="dialog" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">Create User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="post" id="createUserForm">
                        <div class="form-group">
                            <label for="create-username">Username</label>
                            <input type="text" class="form-control" id="create-username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="create-password">Password</label>
                            <input type="password" class="form-control" id="create-password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="create-role">Role</label>
                            <select class="form-control" id="create-role" name="role" required>
                                <option value="">Select Role</option>
                                <?php foreach ($appConfig['user_roles'] as $role): ?>
                                    <option value="<?php echo $role ?>"><?php echo $role ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" name="create_user" value="true">Create User</button>
                    </form>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="text-center mt-5" style="color: var(--color-text)">
                Manage Users
            </h2>

            <!-- Create User Button -->
            <div style="display: flex; flex-direction: row; justify-content: space-between; align-items: center; margin-bottom: 1em">
                <h5 style="color: var(--color-text); margin: 0; display: flex; align-items: center; justify-content: center">
                    Users List
                </h5>

                <button style="height: 5vh" type="button" class="btn btn-primary" data-toggle="modal" data-target="#createUserModal" title="Create User">
                    <i class="bi bi-person-plus"> </i>
                </button>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <table class="table table-dark mt-3">
                <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['username']; ?></td>
                        <td><?php echo $user['role']; ?></td>
                        <td>
                            <button class="btn btn-warning" title="Edit User" data-toggle="modal" data-target="#editUserModal"
                                    data-id="<?php echo $user['id']; ?>" data-username="<?php echo $user['username']; ?>"
                                    data-role="<?php echo $user['role']; ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-danger" title="Delete User" data-toggle="modal"
                                    data-target="#deleteUserModal" data-id="<?php echo $user['id']; ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    $(document).ready(function() {
        // Populate edit user modal
        $('#editUserModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var username = button.data('username'); // Extract info from data-* attributes
            var role = button.data('role');
            var userId = button.data('id');

            var modal = $(this);
            modal.find('#edit-username').val(username);
            modal.find('#edit-role').val(role);
            modal.find('#edit-user-id').val(userId);
            modal.find('#edit-password').val(''); // Reset password field
        });

        // Populate delete user modal
        $('#deleteUserModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var userId = button.data('id'); // Extract info from data-* attributes

            var modal = $(this);
            modal.find('#delete-user-id').val(userId);
        });
    });
</script>

</body>
</html>
