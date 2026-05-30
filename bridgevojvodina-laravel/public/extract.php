/**
 * Automated extraction script for GitHub Actions deployment.
 */

$token = 'Bv2026_Deploy_Secret_99'; 

if (!isset($_GET['token']) || $_GET['token'] !== $token) {
    header('HTTP/1.0 403 Forbidden');
    die('Unauthorized');
}

if (!class_exists('ZipArchive')) {
    header('HTTP/1.0 500 Internal Server Error');
    die('Error: ZipArchive class not found.');
}

// Smart path detection
$currentDir = __DIR__;
$parentDir = dirname($currentDir);
$zipName = 'deploy.zip';

// Look for ZIP in current dir or parent dir
if (file_exists($currentDir . '/' . $zipName)) {
    $zipPath = $currentDir . '/' . $zipName;
    $extractPath = (basename($currentDir) === 'public_html') ? $parentDir : $currentDir;
} elseif (file_exists($parentDir . '/' . $zipName)) {
    $zipPath = $parentDir . '/' . $zipName;
    $extractPath = $parentDir;
} else {
    header('HTTP/1.0 404 Not Found');
    die("Error: $zipName not found in " . $currentDir . " or " . $parentDir);
}

$zip = new ZipArchive;
if ($zip->open($zipPath) === TRUE) {
    $zip->extractTo($extractPath);
    $zip->close();
    unlink($zipPath);
    echo "Extraction successful to: " . realpath($extractPath);
    unlink(__FILE__);
} else {
    header('HTTP/1.0 500 Internal Server Error');
    echo "Failed to open ZIP file.";
}
