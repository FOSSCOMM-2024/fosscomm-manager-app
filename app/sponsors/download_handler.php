<?php

require '../services/DatabaseService.php';

$databaseService = new \app\services\DatabaseService();

if (isset($_POST['download_csv'])) {
    // Prepare CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="cv_submissions.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['First Name', 'Last Name', 'Email', 'Position', 'Role', 'Phone', 'LinkedIn', 'GitHub', 'Bio']);

    $query = "SELECT first_name, last_name, email, position, role, phone, linkedin, github, bio FROM cv_uploads";
    $result = $databaseService->executeQuery($query)->get_result();

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

if (isset($_POST['download_pdfs'])) {
    // Zip CV PDFs and download as a single file
    $query = "SELECT cv_filename FROM cv_uploads";
    $result = $databaseService->executeQuery($query)->get_result();

    $zip = new ZipArchive();
    $zipFilename = "cv_pdfs.zip";

    if ($zip->open($zipFilename, ZipArchive::CREATE) !== TRUE) {
        die("Could not open archive");
    }

    while ($row = $result->fetch_assoc()) {
        $filePath = '../../uploads/cvs/' . $row['cv_filename'];
        if (file_exists($filePath)) {
            $zip->addFile($filePath, $row['cv_filename']);
        }
    }

    $zip->close();

    // Set headers for download
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
    header('Content-Length: ' . filesize($zipFilename));
    readfile($zipFilename);

    // Remove the temporary zip file after download
    unlink($zipFilename);
    exit;
}
