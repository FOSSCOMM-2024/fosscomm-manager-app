<?php

// This file is not used in the app itself, but it is a "point of communication" between the admin app
// and the Wordpress Form Submissions. The form submissions on wordpress are handled by the "MetForm" plugin, that
// provides the ability to send HTTP request to an API. This file is the API endpoint that the MetForm plugin will
// use when new attendees register to FOSSCOMM through the form.

$ALLOWED_CALLER = 'https://2024.fosscomm.gr';

// Get what ever is calling this API
$caller = $_SERVER['HTTP_USER_AGENT'];

// Check if the caller string includes any of the allowed caller
if (strpos($caller, $ALLOWED_CALLER) === false) {
    http_response_code(403);
    die('Forbidden');
}

$postData = $_POST;
$formEntries = $postData['entries'];
$formID = $postData['form_id'];
$entryID = $postData['entry_id'];
$referrer = $postData['referrer_url'];

// Get the data
$decodedEntries = json_decode($formEntries, true);
$firstName = $decodedEntries['mf-first-name'];
$lastName = $decodedEntries['mf-last-name'];
$email = $decodedEntries['mf-email'];
$company = $decodedEntries['mf-org-name'];
$title = $decodedEntries['mf-job-title'];

// Include the database service
require '../app/services/DatabaseService.php';
require '../app/services/QrCodeService.php';
use \app\services\DatabaseService;
use \app\services\QrCodeService;

$databaseService = new DatabaseService();
// Make sure that the attendee is not already registered (by email)
$checkAttendeeQuery = "SELECT * FROM conference_attendees WHERE email = ?";
$checkAttendeeParams = ['s', $email];
$stmt = $databaseService->executeQuery($checkAttendeeQuery, $checkAttendeeParams);
$result = $stmt->get_result();
$attendeeExists = $result->num_rows > 0;

if ($attendeeExists) {
    http_response_code(400);
    die('Attendee already exists');
}

$createAttendeeQuery = "INSERT INTO conference_attendees (name, surname, email, company, title, registered_to_conference) VALUES (?, ?, ?, ?, ?, ?)";
$createAttendeeParams = ['sssssi', $firstName, $lastName, $email, $company, $title, 1];
$stmt = $databaseService->executeQuery($createAttendeeQuery, $createAttendeeParams);

// Get the ID of the newly inserted attendee
$attendeeID = $stmt->insert_id;

// Generate the QR code for the attendee
$qrCodeService = new QrCodeService();
$qrURL = $qrCodeService->makeUserDetailsQr($attendeeID);

// Make a random string token of 255 characters (make sure it is unique)
$token = bin2hex(random_bytes(40));

// Update the attendee with the QR code URL
$updateQRCodeQuery = "UPDATE conference_attendees SET qr_code = ? WHERE id = ?";
$updateQRCodeParams = ['si', $qrURL, $attendeeID];
$databaseService->executeQuery($updateQRCodeQuery, $updateQRCodeParams);

// Add the token to the attendee
$updateTokenQuery = "UPDATE conference_attendees SET ebadge_token = ? WHERE id = ?";
$updateTokenParams = ['si', $token, $attendeeID];
$databaseService->executeQuery($updateTokenQuery, $updateTokenParams);

// Close the statement
$stmt->close();

// Now that we added the attendee to the database, we can send an email to the attendee
// With the registration confirmation. For some reason the EmailSendingService is not working
// and cant import the PHPMailer class. Also we cant connect to teh gmail SMTP server from this server
// So we will use the following workaround:
// - We put the mail sending logic as an API on another webserver (and it works [!?])
// - We will send a POST request to that API with the following data:
// - receiverEmail: The email of the attendee
// - subject: The subject of the email
// - body: The body of the email
// And pray to the dark spirits of computer mana that it works
$appConfig = include __DIR__ . '/../app/config/app_config.php';

$sendEmailURL = $appConfig['mail_api'];

$subject = "🎉Registration Successful!🎉";
$body = file_get_contents('../email-templates/fosscomm_2024_registration_confirmed_email.html');
$receiverEmail = $email;

require '../api/send_email.php';

$confirmationEmailResult = sendEmail($receiverEmail, $subject, $body);

// Get the created attendee
$getAttendeeQuery = "SELECT * FROM conference_attendees WHERE id = ?";
$getAttendeeParams = ['i', $attendeeID];

$stmt = $databaseService->executeQuery($getAttendeeQuery, $getAttendeeParams);
$attendee = $stmt->get_result()->fetch_assoc();

// Get the QR code URL and the token
$qrURL = $attendee['qr_code'];
$token = $attendee['ebadge_token'];

$subject = "FOSSCOMM 2024 | Your QR Code and E-Badge";
$body = file_get_contents('../email-templates/email_fosscomm_event_access_qr_code.html');
$receiverEmail = $email;

// Replace the placeholders with the actual data
$body = str_replace('{{QR_CODE_URL}}', 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . $qrURL, $body);

$ebadgeURL = "https://fosscomm.archontis.gr/ebadge.php?id=" . $attendeeID . "&token=" . $token;
$body = str_replace('{{E_BADGE_URL}}', $ebadgeURL, $body);

$qrCodeEmailResult = sendEmail($receiverEmail, $subject, $body);

$requestError = $confirmationEmailResult['error'] ?? null;

// Log the request
$timestamp = time();
$filename = "add_attendee_api_log.log";
$myfile = fopen($filename, "a") or die("Unable to open file!");

fwrite($myfile, "=====================================================\n");
fwrite($myfile, "Received Request from: " . $caller . "\n");
fwrite($myfile, "Time: " . $timestamp . "\n");
fwrite($myfile, "Form ID: " . $formID . "\n");
fwrite($myfile, "Entry ID: " . $entryID . "\n");
fwrite($myfile, "Referrer: " . $referrer . "\n");
fwrite($myfile, "Entries:\n");

$decodedEntries = json_decode($formEntries, true);
$firstNameLine = "First Name: " . $firstName . "\n";
$lastNameLine = "Last Name: " . $lastName . "\n";
$emailLine = "Email: " . $email . "\n";
$companyLine = "Company: " . $company . "\n";
$titleLine = "Title: " . $title . "\n";

$line = $firstName . $lastName . $email . $company . $title;
fwrite($myfile, $line);
fwrite($myfile, "Received Registration Request Added The Attendee to the Database\n");
fwrite($myfile, "Attendee ID: " . $attendeeID . "\n");
fwrite($myfile, "QR Code URL: " . $qrURL . "\n");

if (!$requestError) { fwrite($myfile, "Email Sent Successfully\n"); }
else {
    fwrite($myfile, "Email Sending Failed\n");
    fwrite($myfile, "Error: " . $requestError . "\n");
}

fwrite($myfile, "=====================================================\n");

fclose($myfile);