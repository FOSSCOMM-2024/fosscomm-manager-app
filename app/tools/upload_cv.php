<?php

require '../services/DatabaseService.php';

use app\services\DatabaseService;

$appConfig = include __DIR__ . '/../config/app_config.php';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $successMessage = "";
    $errorMessage = "";

    // Capture the form data
    $email = $_POST['email'];
    $firstName = $_POST['first-name'];
    $lastName = $_POST['last-name'];
    $position = $_POST['position'];
    $role = $_POST['role'];
    $customRole = isset($_POST['custom-role']) ? $_POST['custom-role'] : null;
    $phone = isset($_POST['phone']) ? $_POST['phone'] : 'N/A';
    $linkedin = isset($_POST['linkedin']) ? $_POST['linkedin'] : 'N/A';
    $github = isset($_POST['github']) ? $_POST['github'] : 'N/A';
    $bio = isset($_POST['bio']) ? $_POST['bio'] : 'N/A';

    // Directory to save uploaded CVs
    $uploadDir = __DIR__ . '../../../uploads/cvs/';

    // Ensure the uploads directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Handle file upload
    $cvFile = $_FILES['cv'];
    $newName = $firstName . '_' . $lastName . '_' . time() . "." . (pathinfo($cvFile['name'], PATHINFO_EXTENSION));
    $cvFileName = basename($newName);
    $cvFilePath = $uploadDir . $cvFileName;
    $uploadSuccess = false;

    if (move_uploaded_file($cvFile['tmp_name'], $cvFilePath)) {
        $uploadSuccess = true;
    }

    // Capture the rest of the form data
    $email = $_POST['email'];
    $firstName = $_POST['first-name'];
    $lastName = $_POST['last-name'];
    $position = $_POST['position'];
    $role = $_POST['role'];
    $customRole = isset($_POST['custom-role']) ? $_POST['custom-role'] : null;
    $phone = isset($_POST['phone']) ? $_POST['phone'] : 'N/A';
    $linkedin = isset($_POST['linkedin']) ? $_POST['linkedin'] : 'N/A';
    $github = isset($_POST['github']) ? $_POST['github'] : 'N/A';
    $bio = isset($_POST['bio']) ? $_POST['bio'] : 'N/A';

    // Concatenate the custom role if "Other" is selected
    if ($role === 'Other' && !empty($customRole)) {
        $role = $customRole;
    }

    // Display the form data after submission
    if ($uploadSuccess) {
        // Now that the upload is successful update the DB entry
        $dbService = new DatabaseService();
        $insertQuery = "INSERT INTO cv_uploads (email, first_name, last_name, position, role, phone, linkedin, github, bio, cv_filename, submission_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $dbService->executeQuery($insertQuery, ['ssssssssss', $email, $firstName, $lastName, $position, $role, $phone, $linkedin, $github, $bio, $cvFileName]);
        $stmt->close();

        $successMessage = "CV uploaded successfully!";
    } else {
        $errorMessage = "Failed to upload CV.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Upload CV</title>
    <link rel="icon" href="https://2024.fosscomm.gr/wp-content/uploads/2024/04/cropped-fosscommIcon-32x32.png" sizes="32x32">

    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../../styles/upload_cv.css">
    <link rel="stylesheet" href="../../styles/app_styles.css">

    <script>
        function handleRoleChange() {
            var roleSelect = document.getElementById('role');
            var customRoleGroup = document.getElementById('custom-role-group');
            var selectedValue = roleSelect.value;
            // Show the custom role input only if "Other" is selected
            if (selectedValue === 'Other') {
                customRoleGroup.style.display = 'block';
            } else {
                customRoleGroup.style.display = 'none';
            }
        }

        // Ensure the "Other" role input is hidden by default
        window.onload = function() {
            handleRoleChange();
        };
    </script>
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

<!-- Modal for Terms & Conditions -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">
                    CV Upload Terms & Conditions
                </h5>
            </div>
            <div class="modal-body">
                <p style="font-style: italic">
                    Last updated: 29th October 2024
                </p>

                <h5 style="text-align: left">1. Acceptance of Terms</h5>
                <p>
                    By submitting your CV and related personal data through this application form, you agree to these terms and conditions and provide consent for the specified uses of your information.
                </p>

                <h5 style="text-align: left">2. Information Collected</h5>
                <div>
                    <p>We may collect and process the following personal information as part of the application and CV submission:</p>
                    <ul>
                        <li>Full name, email address, phone number, LinkedIn profile, GitHub profile, and other social links.</li>
                        <li>Professional information, including your position, area of expertise, and biography.</li>
                        <li>Uploaded documents, including your CV, which should be in PDF format.</li>
                    </ul>
                </div>

                <h5 style="text-align: left">3. Purpose of Data Collection</h5>
                <div>
                    <p>The data you submit will be used to provide information to sponsors, upon your explicit consent, who may retain a copy for recruitment purposes, in accordance with GDPR compliance.</p>
                </div>

                <h5 style="text-align: left">4. Data Retention and Deletion</h5>
                <p>
                    Your data will be retained on FOSSCOMM 2024 systems only until the conclusion of the FOSSCOMM 2024 event.
                    After the event ends, all personal data and CVs stored on our systems will be deleted.

                    However, sponsors with whom your CV has been shared may retain their copy of your data on their systems in accordance with GDPR.
                </p>

                <h5 style="text-align: left">5. Data Sharing and Consent</h5>
                <div>
                    <p>By submitting this form to share your CV, you consent to:</p>
                    <ul>
                        <li>The sharing of your CV and relevant information with event sponsors for recruitment or networking opportunities.</li>
                        <li>Sponsors retaining your data on their systems as they manage it according to GDPR.</li>
                    </ul>
                </div>

                <h5 style="text-align: left">6. Your Rights</h5>
                <div>
                    <p>Under GDPR, you have the right to:</p>
                    <ul>
                        <li>Access, rectify, or delete your personal information.</li>
                        <li>Restrict or object to the processing of your data.</li>
                        <li>Withdraw consent at any time.</li>
                    </ul>
                    <p>
                        After FOSSCOMM 2024 concludes, responsibility for handling these rights transfers to the sponsors retaining your CV.
                        Should you wish to act on your rights post-event, you may contact us to obtain a list of the companies with whom your CV was shared.
                    </p>
                </div>

                <h5 style="text-align: left">7. Disclaimer</h5>
                <p>
                    We reserve the right to reject any CV that does not comply with the submission requirements or if the file type is incorrect.
                    FOSSCOMM 2024 is not responsible for any third-party actions taken based on the information provided to our sponsors or any misuse
                    of information by third parties.
                </p>

                <h5 style="text-align: left">8. Contact Information</h5>
                <p>
                    For questions or further assistance, please contact us at the <a href="mailto:info@fosscomm.gr">info@fosscomm.gr</a>.
                </p>

                <!-- Button to Disagree and go to the homepage -->
                <a href="http://2024.fosscomm.gr" class="btn btn-danger">
                    <i class="bi bi-x-circle"></i> I Disagree
                </a>

                <!-- button to agree and close the modal -->
                <button type="button" class="btn btn-success" data-dismiss="modal">
                    <i class="bi bi-check-circle"></i> I Agree
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container" style="min-width: 70vw">
    <h2>Upload your CV</h2>

    <!--  Submission Info Alerts  -->
    <?php if (!empty($successMessage)) { ?>
        <div class="alert alert-success" role="alert">
            <strong>Success!</strong> <?= $successMessage ?>
        </div>
    <?php } ?>

    <form action="" method="POST" enctype="multipart/form-data" style="margin: 1em!important;">
        <div class="form-group">
            <label for="email">Registration Email:</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="first-name">First Name:</label>
            <input type="text" id="first-name" name="first-name" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="last-name">Last Name:</label>
            <input type="text" id="last-name" name="last-name" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="position">Position (Student, Professional, etc.):</label>
            <input type="text" id="position" name="position" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="role">General Role/Area of Expertise:</label>
            <select class="form-control" id="role" name="role" onchange="handleRoleChange()" required>
                <option value="" disabled selected>Select your role</option>
                <option value="Frontend Developer">Frontend Developer</option>
                <option value="Backend Developer">Backend Developer</option>
                <option value="Fullstack Developer">Fullstack Developer</option>
                <option value="DevOps Engineer">DevOps Engineer</option>
                <option value="Data Scientist">Data Scientist</option>
                <option value="Mobile Developer">Mobile Developer</option>
                <option value="UI/UX Designer">UI/UX Designer</option>
                <option value="Product Manager">Product Manager</option>
                <option value="QA Engineer">QA Engineer</option>
                <option value="System Administrator">System Administrator</option>
                <option value="Other">Other (Please Specify)</option>
            </select>
        </div>

        <!-- Custom role input field, shown only if 'Other' is selected -->
        <div class="form-group" id="custom-role-group" style="display:none;">
            <label for="custom-role">Please Specify Your Role:</label>
            <input type="text" id="custom-role" name="custom-role" class="form-control" placeholder="Enter your role">
        </div>

        <div class="form-group">
            <label for="phone">Phone Number (Optional):</label>
            <input type="tel" id="phone" name="phone" class="form-control">
        </div>

        <div class="form-group">
            <label for="linkedin"><i class="bi bi-linkedin"></i> LinkedIn Profile (Optional):</label>
            <input type="text" id="linkedin" name="linkedin" class="form-control" placeholder="https://linkedin.com/in/your-profile">
        </div>

        <div class="form-group">
            <label for="github"><i class="bi bi-github"></i> GitHub Profile (Optional):</label>
            <input type="text" id="github" name="github" class="form-control" placeholder="https://github.com/your-profile">
        </div>

        <div class="form-group">
            <label for="bio">Short Bio (Optional):</label>
            <textarea id="bio" name="bio" class="form-control" placeholder="Tell us a little about yourself..."></textarea>
        </div>

        <br>

        <div class="form-group">
            <label for="cv"><i class="bi bi-filetype-pdf"></i> Upload CV (PDF):</label>
            <input type="file" id="cv" name="cv" class="form-control" accept=".pdf" required>
        </div>

        <!-- Checkbox to agree with sharing the CV with sponsors -->
        <div class="form-group">
            <input type="checkbox" id="share-cv" name="share-cv" required>
            <label for="share-cv">
                I have read and agree to the <a href="#" data-toggle="modal" data-target="#termsModal">Terms & Conditions</a> for CV uploads.
            </label>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-cloud-upload"></i> Upload CV
            </button>
        </div>
    </form>
</div>

<!-- Bootstrap JS, Popper.js, and jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    // Show the modal when the page loads
    $(document).ready(function() {
        $('#termsModal').modal('show');
    });
</script>
</body>
</html>
