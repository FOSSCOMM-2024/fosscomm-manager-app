<?php

require '../services/DatabaseService.php';

use app\services\DatabaseService;

$appConfig = include __DIR__ . '/../config/app_config.php';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $successMessage = "";
    $errorMessage = "";

    // Capture the form data
    $speakerName = $_POST['speaker-name'];
    $organization = isset($_POST['organization']) ? $_POST['organization'] : 'N/A';
    $talkTitle = $_POST['talk-title'];
    $speakerEmail = isset($_POST['speaker-email']) ? $_POST['speaker-email'] : null;
    $shareEmail = isset($_POST['share-email']) ? 1 : 0; // Checkbox for email sharing

    // Directory to save uploaded presentations
    $uploadDir = __DIR__ . '../../../uploads/presentations/';

    // Ensure the uploads directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Handle file upload
    $presentationFile = $_FILES['presentation'];
    $newName = $speakerName . '_' . $talkTitle . '_' . time() . "." . (pathinfo($presentationFile['name'], PATHINFO_EXTENSION));
    $presentationFileName = basename($newName);
    $presentationFilePath = $uploadDir . $presentationFileName;
    $uploadSuccess = false;

    if (move_uploaded_file($presentationFile['tmp_name'], $presentationFilePath)) {
        $uploadSuccess = true;
    }

    // Display the form data after submission
    if ($uploadSuccess) {
        // Now that the upload is successful update the DB entry
        $dbService = new DatabaseService();
        $insertQuery = "INSERT INTO presentation_uploads (speaker_name, organization, talk_title, speaker_email, share_email, presentation_filename, submission_date) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $dbService->executeQuery($insertQuery, ['ssssss', $speakerName, $organization, $talkTitle, $speakerEmail, $shareEmail, $presentationFileName]);
        $stmt->close();

        $successMessage = "Presentation uploaded successfully!";
    } else {
        $errorMessage = "Failed to upload presentation.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Upload Presentation</title>
    <link rel="icon" href="https://2024.fosscomm.gr/wp-content/uploads/2024/04/cropped-fosscommIcon-32x32.png" sizes="32x32">

    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../../styles/upload_cv.css">
    <link rel="stylesheet" href="../../styles/app_styles.css">
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
                    Presentation Upload Disclaimer
                </h5>
            </div>
            <div class="modal-body">
                <h5 style="text-align: left">1. Purpose</h5>
                <p>
                    Your presentation will be archived and made publicly available on the FOSSCOMM platform. This helps us preserve and share the valuable
                    knowledge shared during the event with attendees, as well as with anyone interested in the topics you presented.

                    <br><br>

                    In alignment with the principles of the open-source community, your presentation will be accessible to all, fostering collaboration, learning, and the widespread sharing of knowledge.
                </p>

                <h5 style="text-align: left">2. Information Collected</h5>
                <div>
                    <p>
                        Additionally with your presentation, we will share your name, organization, and email address (if provided). This information will be displayed alongside your presentation on the FOSSCOMM platform.
                    </p>
                </div>

                <h5 style="text-align: left">3. Purpose of Data Collection</h5>
                <div>
                    <p>
                        Your data will be shared alongside your presentation to provide context and contact information for attendees interested in your work.
                    </p>
                </div>

                <h5 style="text-align: left">4. Data Retention and Deletion</h5>
                <p>
                    You have the right to deny the sharing of your data and presentation. If you wish to remove your presentation from the FOSSCOMM platform, please contact us at
                    <a href="mailto:info@fosscomm.gr">
                        info@fosscomm.gr
                    </a>
                </p>

                <h5 style="text-align: left">5. Data Sharing and Consent</h5>
                <div>
                    <p>By submitting this form to share your Presentation, you consent to:</p>
                    <ul>
                        <li>
                            Share your presentation and personal information with FOSSCOMM 2024 attendees.
                        </li>
                        <li>
                            Archive your presentation on the FOSSCOMM platform & Website for public access.
                        </li>
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
                </div>

                <h5 style="text-align: left">7. Access & Distribution</h5>
                <p>
                    Once uploaded, your presentation may be distributed on our website and other platforms to ensure it reaches as many people as possible,
                    even those who couldn’t attend the event.
                </p>

                <h5 style="text-align: left">8. Attribution</h5>
                <p>
                    We will always provide clear attribution to you as the presenter. However, by uploading your presentation, you grant us permission to use, share, and distribute your content within the context of FOSSCOMM and open-source-related activities.
                </p>

                <h5 style="text-align: left">9. No Commercial Use</h5>
                <p>
                    Your content will only be shared for educational and informational purposes and will not be used for any commercial activities.
                </p>

                <h5 style="text-align: left">10. Contact Information</h5>
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
    <h2>Upload Your Presentation</h2>

    <!--  Submission Info Alerts  -->
    <?php if (!empty($successMessage)) { ?>
        <div class="alert alert-success" role="alert">
            <strong>Success!</strong> <?= $successMessage ?>
        </div>
    <?php } ?>

    <form action="" method="POST" enctype="multipart/form-data" style="margin: 1em!important;">
        <div class="form-group">
            <label for="speaker-name">Speaker(s) Name(s):</label>
            <input type="text" id="speaker-name" name="speaker-name" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="organization">Speaker(s) Organization (Optional):</label>
            <input type="text" id="organization" name="organization" class="form-control">
        </div>

        <div class="form-group">
            <label for="talk-title">Talk Title:</label>
            <input type="text" id="talk-title" name="talk-title" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="speaker-email">Speaker Email (Optional):</label>
            <input type="email" id="speaker-email" name="speaker-email" class="form-control" placeholder="example@example.com">
        </div>

        <div class="form-group">
            <input type="checkbox" id="share-email" name="share-email">
            <label for="share-email">I consent to share my email along with the presentation details.</label>
        </div>

        <div class="form-group">
            <label for="presentation"><i class="bi bi-file-earmark-pdf"></i> Upload Presentation (PDF, PPT, etc.):</label>
            <input type="file" id="presentation" name="presentation" class="form-control" accept=".pdf, .ppt, .pptx, .odp" required>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-cloud-upload"></i> Upload Presentation
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
