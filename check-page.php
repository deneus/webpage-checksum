#!/usr/bin/env php
<?php
/**
 * Webpage Checksum Checker
 * 
 * Fetches HTML from a webpage and calculates its checksum.
 * Compares with previous checksum to detect changes.
 * Sends email notification when checksum changes.
 */

/**
 * Send email notification.
 * 
 * @param string $to Recipient email address.
 * @param string $subject Email subject.
 * @param string $message Email message body.
 * @return bool True if email sent successfully, false otherwise.
 */
function sendEmailNotification($to, $subject, $message) {
    if (empty($to)) {
        error_log("Error: Email recipient not provided.");
        return false;
    }
    
    // Validate email address.
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("Error: Invalid email address: {$to}");
        return false;
    }
    
    // Set email headers.
    $headers = [
        'From: ' . 'deneus18@hotmail.com',
        'Reply-To: ' . (getenv('EMAIL_REPLY_TO') ?: 'noreply@localhost'),
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8'
    ];
    
    // Send the email.
    $result = @mail($to, $subject, $message, implode("\r\n", $headers));
    
    if (!$result) {
        error_log("Error: Failed to send email notification.");
        return false;
    }
    
    return true;
}

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
echo "Checksum file path: {$checksumFile}\n";
echo "File exists: " . (file_exists($checksumFile) ? 'yes' : 'no') . "\n";

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
    echo "Previous checksum read from file: {$previousChecksum}\n";
} else {
    echo "No previous checksum file found.\n";
}

// Compare checksums.
if ($previousChecksum !== null && $previousChecksum !== $checksum) {
    echo "Checksum changed!\n";
    echo "Previous: {$previousChecksum}\n";
    echo "Current:  {$checksum}\n";
    echo "Timestamp: {$timestamp}\n";
    
    // Store the new checksum.
    $writeResult = file_put_contents($checksumFile, $checksum);
    if ($writeResult !== false) {
        echo "Checksum written to file: {$checksum} ({$writeResult} bytes)\n";
        echo "File exists after write: " . (file_exists($checksumFile) ? 'yes' : 'no') . "\n";
        echo "File contents after write: " . trim(file_get_contents($checksumFile)) . "\n";
    } else {
        echo "ERROR: Failed to write checksum to file!\n";
    }
    
    // Send email notification.
    $emailTo = getenv('EMAIL_TO');
    
    if (!empty($emailTo)) {
        $subject = "Webpage Checksum Changed - {$url}";
        $message = sprintf(
            "Webpage Checksum Changed!\n\n" .
            "URL: %s\n" .
            "Date: %s\n" .
            "Previous Checksum: %s\n" .
            "Current Checksum: %s\n",
            $url,
            $timestamp,
            $previousChecksum,
            $checksum
        );
        
        if (sendEmailNotification($emailTo, $subject, $message)) {
            echo "Email notification sent successfully.\n";
        } else {
            echo "Failed to send email notification.\n";
        }
    } else {
        echo "Email recipient not configured. Skipping notification.\n";
    }
    
    // Exit with code 1 to indicate change (can be used in CI/CD).
    exit(1);
} elseif ($previousChecksum === null) {
    echo "First check - storing checksum: {$checksum}\n";
    $writeResult = file_put_contents($checksumFile, $checksum);
    if ($writeResult !== false) {
        echo "Checksum written to file: {$checksum} ({$writeResult} bytes)\n";
        echo "File exists after write: " . (file_exists($checksumFile) ? 'yes' : 'no') . "\n";
        echo "File contents after write: " . trim(file_get_contents($checksumFile)) . "\n";
    } else {
        echo "ERROR: Failed to write checksum to file!\n";
    }
    exit(0);
} else {
    echo "Checksum unchanged: {$checksum}\n";
    exit(0);
}

