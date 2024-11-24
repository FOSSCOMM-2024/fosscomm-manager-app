<?php


// send_email.php

function sendEmail($receiverEmail, $subject, $body)
{
    // Load the configuration file
    $appConfig = include __DIR__ . '/../app/config/app_config.php';

    // Prepare the data to send
    $sendEmailData = [
        'receiverEmail' => $receiverEmail,
        'subject' => $subject,
        'body' => $body,
    ];

    // Init the cURL request
    $sendEmailURL = $appConfig['mail_api'];
    $ch = curl_init($sendEmailURL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);

    // Convert the data to JSON
    $sendEmailDataJSON = json_encode($sendEmailData);

    // Set the request body
    curl_setopt($ch, CURLOPT_POSTFIELDS, $sendEmailDataJSON);

    // Execute the request
    $response = curl_exec($ch);
    $requestError = curl_error($ch);

    // Handle errors
    if ($requestError) {
        return ['success' => false, 'error' => $requestError];
    } else {
        return ['success' => true, 'response' => $response];
    }
}

// addAttendee.php