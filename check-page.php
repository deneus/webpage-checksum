#!/usr/bin/env php
<?php
/**
 * Webpage Checksum Checker
 * 
 * Fetches HTML from a webpage and calculates its checksum.
 * Compares with previous checksum to detect changes.
 */

// Get the URL from environment variable or command line argument.
$url = getenv('WEBPAGE_URL') ?: ($argv[1] ?? null);

if (empty($url)) {
    error_log("Error: URL not provided. Set WEBPAGE_URL env var or pass as argument.");
    exit(1);
}

// Validate URL.
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    error_log("Error: Invalid URL format: {$url}");
    exit(1);
}

// File to store previous checksum.
$checksumFile = __DIR__ . '/.checksum';

// Fetch the webpage HTML.
$html = @file_get_contents($url);

if ($html === false) {
    error_log("Error: Failed to fetch webpage from {$url}");
    exit(1);
}

// Calculate checksum.
$checksum = md5($html);
$timestamp = date('Y-m-d H:i:s');

// Read previous checksum if it exists.
$previousChecksum = null;
if (file_exists($checksumFile)) {
    $previousChecksum = trim(file_get_contents($checksumFile));
}

// Compare checksums.
if ($previousChecksum !== null && $previousChecksum !== $checksum) {
    echo "Checksum changed!\n";
    echo "Previous: {$previousChecksum}\n";
    echo "Current:  {$checksum}\n";
    echo "Timestamp: {$timestamp}\n";
    
    // Store the new checksum.
    file_put_contents($checksumFile, $checksum);
    
    // Exit with code 1 to indicate change (can be used in CI/CD).
    exit(1);
} elseif ($previousChecksum === null) {
    echo "First check - storing checksum: {$checksum}\n";
    file_put_contents($checksumFile, $checksum);
    exit(0);
} else {
    echo "Checksum unchanged: {$checksum}\n";
    exit(0);
}

