<?php
/**
 * OPcache Preload Script for Webtool 4.2
 *
 * This script preloads frequently used files into OPcache memory
 * on PHP-FPM startup, significantly improving performance.
 *
 * PHP 8.0+ feature - can improve performance by 30-50%
 */

// Only run in production with OPcache enabled
if (php_sapi_name() !== 'cli' || !function_exists('opcache_compile_file')) {
    return;
}

// Define base path
$baseDir = '/var/www/html';

// Change to application directory
if (!is_dir($baseDir)) {
    error_log("OPcache Preload: Base directory not found: {$baseDir}");
    return;
}

chdir($baseDir);

// Require Composer autoloader
$autoloader = $baseDir . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    error_log("OPcache Preload: Composer autoloader not found");
    return;
}

require_once $autoloader;

/**
 * Recursively preload PHP files from a directory
 */
function preloadDirectory(string $directory, array $excludePatterns = []): int
{
    $count = 0;

    if (!is_dir($directory)) {
        return $count;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $filePath = $file->getRealPath();

        // Skip excluded patterns
        foreach ($excludePatterns as $pattern) {
            if (strpos($filePath, $pattern) !== false) {
                continue 2;
            }
        }

        try {
            opcache_compile_file($filePath);
            $count++;
        } catch (Throwable $e) {
            error_log("OPcache Preload: Failed to compile {$filePath}: " . $e->getMessage());
        }
    }

    return $count;
}

$startTime = microtime(true);
$totalFiles = 0;

// Directories to exclude from preloading
$excludePatterns = [
    '/Tests/',
    '/tests/',
    '/Test/',
    '/testing/',
    '/Fixtures/',
    '/fixtures/',
    '/stubs/',
    '/Stubs/',
];

echo "Starting OPcache preload...\n";

// 1. Preload Laravel Framework core files
echo "Preloading Laravel framework...\n";
$totalFiles += preloadDirectory($baseDir . '/vendor/laravel/framework/src', $excludePatterns);

// 2. Preload Symfony core components (used by Laravel)
echo "Preloading Symfony components...\n";
$symfonyDirs = [
    '/vendor/symfony/console',
    '/vendor/symfony/http-foundation',
    '/vendor/symfony/http-kernel',
    '/vendor/symfony/routing',
    '/vendor/symfony/finder',
];

foreach ($symfonyDirs as $dir) {
    if (is_dir($baseDir . $dir)) {
        $totalFiles += preloadDirectory($baseDir . $dir, $excludePatterns);
    }
}

// 3. Preload application code
echo "Preloading application code...\n";
$appDirs = [
    '/app/Http/Controllers',
    '/app/Services',
    '/app/Data',
    '/app/Repositories',
    '/app/Models',
];

foreach ($appDirs as $dir) {
    if (is_dir($baseDir . $dir)) {
        $totalFiles += preloadDirectory($baseDir . $dir, $excludePatterns);
    }
}

// 4. Preload commonly used packages
echo "Preloading common packages...\n";
$commonPackages = [
    '/vendor/spatie/laravel-data',
    '/vendor/livewire/livewire',
];

foreach ($commonPackages as $package) {
    if (is_dir($baseDir . $package)) {
        $totalFiles += preloadDirectory($baseDir . $package, $excludePatterns);
    }
}

$duration = round(microtime(true) - $startTime, 2);
echo "OPcache preload completed: {$totalFiles} files in {$duration}s\n";

// Log to Laravel log file
$logMessage = sprintf(
    "[%s] OPcache Preload: Compiled %d files in %.2fs",
    date('Y-m-d H:i:s'),
    $totalFiles,
    $duration
);

@file_put_contents('/var/log/laravel/laravel.log', $logMessage . PHP_EOL, FILE_APPEND);
