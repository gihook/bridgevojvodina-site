<?php
/**
 * Automated extraction script for GitHub Actions deployment.
 */

// Basic security: Check for a token to prevent unauthorized extraction
// You should add DEPLOY_TOKEN to your GitHub Repository Secrets.
$token = 'Bv2026_Deploy_Secret_99'; 

if (!isset($_GET['token']) || $_GET['token'] !== $token) {
    header('HTTP/1.0 403 Forbidden');
    die('Unauthorized');
}

if (!class_exists('ZipArchive')) {
    header('HTTP/1.0 500 Internal Server Error');
    die('Error: ZipArchive class not found. Please enable the zip extension on your server.');
}

$zipFile = '../deploy.zip'; // ZIP is uploaded one level above public_html/

if (file_exists($zipFile)) {
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        $zip->extractTo('../');
        $zip->close();
        unlink($zipFile); // Delete ZIP after extraction
        echo "Extraction successful!";
        
        // Optionally delete this script itself
        unlink(__FILE__);
    } else {
        header('HTTP/1.0 500 Internal Server Error');
        echo "Failed to open ZIP file.";
    }
} else {
    header('HTTP/1.0 404 Not Found');
    echo "ZIP file not found.";
}
