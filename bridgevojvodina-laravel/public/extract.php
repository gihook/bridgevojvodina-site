<?php
/**
 * Automated extraction script for GitHub Actions deployment.
 */

$token = 'DEPLOY_TOKEN_PLACEHOLDER'; 

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
    echo "Extraction successful to: " . realpath($extractPath) . "\n";
    
    // Run migrations and optimize
    $artisanPath = $extractPath . '/artisan';
    if (file_exists($artisanPath)) {
        try {
            // Bootstrap Laravel
            require $extractPath . '/vendor/autoload.php';
            $app = require_once $extractPath . '/bootstrap/app.php';
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            
            echo "\n--- Running migrations ---\n";
            $kernel->call('migrate', ['--force' => true]);
            echo \Illuminate\Support\Facades\Artisan::output() . "\n";
            
            echo "\n--- Optimizing ---\n";
            $kernel->call('optimize');
            echo \Illuminate\Support\Facades\Artisan::output() . "\n";
        } catch (\Exception $e) {
            echo "\nError during Laravel tasks: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Artisan not found at $artisanPath\n";
    }

    unlink(__FILE__);
} else {
    header('HTTP/1.0 500 Internal Server Error');
    echo "Failed to open ZIP file.";
}
