#!/usr/bin/env php
<?php
/**
 * Webpage Checksum Checker
 * 
 * Fetches HTML from a webpage and calculates its checksum.
 * Compares with previous checksum to detect changes.
 * Sends WhatsApp notification when checksum changes.
 */

/**
 * Send WhatsApp notification using CallMeBot API.
 * 
 * @param string $phoneNumber Phone number with country code.
 * @param string $message Message to send.
 * @param string $apiKey CallMeBot API key.
 * @return bool True if message sent successfully, false otherwise.
 */
function sendWhatsAppNotification($phoneNumber, $message, $apiKey) {
    if (empty($phoneNumber) || empty($apiKey)) {
        error_log("Error: WhatsApp phone number or API key not provided.");
        return false;
    }
    
    // Build the API URL.
    $apiUrl = sprintf(
        'https://api.callmebot.com/whatsapp.php?phone=%s&text=%s&apikey=%s',
        urlencode($phoneNumber),
        urlencode($message),
        urlencode($apiKey)
    );
    
    // Send the request.
    $response = @file_get_contents($apiUrl);
    
    if ($response === false) {
        error_log("Error: Failed to send WhatsApp notification.");
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
    
    // Send WhatsApp notification.
    $whatsappPhone = getenv('CALLMEBOT_PHONE');
    $whatsappApiKey = getenv('CALLMEBOT_API_KEY');
    
    if (!empty($whatsappPhone) && !empty($whatsappApiKey)) {
        $message = sprintf(
            "🔔 Webpage Checksum Changed!\n\n" .
            "URL: %s\n" .
            "Date: %s\n" .
            "Previous: %s\n" .
            "Current: %s",
            $url,
            $timestamp,
            $previousChecksum,
            $checksum
        );
        
        if (sendWhatsAppNotification($whatsappPhone, $message, $whatsappApiKey)) {
            echo "WhatsApp notification sent successfully.\n";
        } else {
            echo "Failed to send WhatsApp notification.\n";
        }
    } else {
        echo "WhatsApp credentials not configured. Skipping notification.\n";
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
    
    // Send test WhatsApp notification.
    $whatsappPhone = getenv('CALLMEBOT_PHONE');
    $whatsappApiKey = getenv('CALLMEBOT_API_KEY');
    if (!empty($whatsappPhone) && !empty($whatsappApiKey)) {
        sendWhatsAppNotification($whatsappPhone, "Checksum unchanged", $whatsappApiKey);
    }
    
    exit(0);
}

