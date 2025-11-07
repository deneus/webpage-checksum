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
 * Send WhatsApp notification using Meta WhatsApp API.
 * 
 * @param string $toPhoneNumber Recipient phone number (with country code, no +).
 * @param string $message Message to send.
 * @param string $phoneNumberId Meta WhatsApp phone number ID.
 * @param string $accessToken Meta WhatsApp access token.
 * @return bool True if message sent successfully, false otherwise.
 */
function sendWhatsAppNotification($toPhoneNumber, $message, $phoneNumberId, $accessToken) {
    if (empty($toPhoneNumber) || empty($phoneNumberId) || empty($accessToken)) {
        error_log("Error: WhatsApp phone number, phone number ID, or access token not provided.");
        return false;
    }
    
    // Remove + and any spaces from phone number.
    $toPhoneNumber = preg_replace('/[^0-9]/', '', $toPhoneNumber);
    
    // Build the API URL.
    $apiUrl = "https://graph.facebook.com/v22.0/{$phoneNumberId}/messages";
    
    // Prepare the request payload.
    // For custom text messages, we use the 'text' type instead of 'template'.
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $toPhoneNumber,
        'type' => 'text',
        'text' => [
            'body' => $message
        ]
    ];
    
    // Initialize cURL.
    $ch = curl_init($apiUrl);
    
    // Set cURL options.
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    // Execute the request.
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    curl_close($ch);
    
    // Check for cURL errors.
    if ($response === false || !empty($curlError)) {
        error_log("Error: Failed to send WhatsApp notification. cURL error: {$curlError}");
        return false;
    }
    
    // Check HTTP response code.
    if ($httpCode < 200 || $httpCode >= 300) {
        error_log("Error: WhatsApp API returned HTTP {$httpCode}. Response: {$response}");
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
    $whatsappPhone = getenv('WHATSAPP_PHONE');
    $whatsappPhoneNumberId = getenv('WHATSAPP_PHONE_NUMBER_ID');
    $whatsappAccessToken = getenv('WHATSAPP_ACCESS_TOKEN');
    
    if (!empty($whatsappPhone) && !empty($whatsappPhoneNumberId) && !empty($whatsappAccessToken)) {
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
        
        if (sendWhatsAppNotification($whatsappPhone, $message, $whatsappPhoneNumberId, $whatsappAccessToken)) {
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
    $whatsappPhone = getenv('WHATSAPP_PHONE');
    $whatsappPhoneNumberId = getenv('WHATSAPP_PHONE_NUMBER_ID');
    $whatsappAccessToken = getenv('WHATSAPP_ACCESS_TOKEN');
    if (!empty($whatsappPhone) && !empty($whatsappPhoneNumberId) && !empty($whatsappAccessToken)) {
        sendWhatsAppNotification($whatsappPhone, "Checksum unchanged", $whatsappPhoneNumberId, $whatsappAccessToken);
    }
    else {
        echo "WhatsApp credentials not configured. Skipping notification.\n";
    }
    
    exit(0);
}

