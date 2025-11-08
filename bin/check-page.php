#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use WebpageChecksum\Calculator;
use WebpageChecksum\Checker;
use WebpageChecksum\Detector;
use WebpageChecksum\Fetcher;
use WebpageChecksum\MessageFormatter;
use WebpageChecksum\Notification\EmailNotifier;
use WebpageChecksum\Output\ConsoleOutput;
use WebpageChecksum\Storage;

/**
 * Entry point for webpage checksum checker.
 */

// Get URL from environment variable or command line argument.
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

// Initialize dependencies.
$output = new ConsoleOutput();
$fetcher = new Fetcher($output);
$calculator = new Calculator();
$storagePath = __DIR__ . '/../.checksum';
$storage = new Storage($storagePath, $output);
$detector = new Detector();
$formatter = new MessageFormatter();

// Setup email notifier if configured.
$notifier = null;
$emailTo = getenv('EMAIL_TO') ?: 'deneus18@hotmail.com';
$emailFrom = getenv('EMAIL_FROM') ?: 'deneus18@hotmail.com';
$emailReplyToEnv = getenv('EMAIL_REPLY_TO');
$emailReplyTo = ($emailReplyToEnv !== false && !empty($emailReplyToEnv)) ? $emailReplyToEnv : null;

// SMTP configuration (optional - if not set, uses PHP mail()).
$smtpHost = getenv('SMTP_HOST');
$smtpPort = getenv('SMTP_PORT') ? (int)getenv('SMTP_PORT') : null;
$smtpUsername = getenv('SMTP_USERNAME');
$smtpPassword = getenv('SMTP_PASSWORD');
$smtpEncryption = getenv('SMTP_ENCRYPTION'); // 'tls' or 'ssl'

if (!empty($emailTo)) {
    $notifier = new EmailNotifier(
        $emailTo,
        $emailFrom,
        $emailReplyTo,
        $output,
        $smtpHost !== false ? $smtpHost : null,
        $smtpPort,
        $smtpUsername !== false ? $smtpUsername : null,
        $smtpPassword !== false ? $smtpPassword : null,
        $smtpEncryption !== false ? $smtpEncryption : null
    );
    
    // Send test email only once per week.
    $testEmailFile = __DIR__ . '/../.test-email-timestamp';
    $shouldSendTest = false;
    
    if (file_exists($testEmailFile)) {
        $lastTestTimestamp = (int)trim(file_get_contents($testEmailFile));
        $oneWeekAgo = time() - (7 * 24 * 60 * 60); // 7 days in seconds.
        
        if ($lastTestTimestamp < $oneWeekAgo) {
            $shouldSendTest = true;
        }
    } else {
        // First time, send test email.
        $shouldSendTest = true;
    }
    
    if ($shouldSendTest) {
        // Get ISO week number.
        $weekNumber = (int)date('W');
        $subject = "Ensure webpage-checksum is still running W{$weekNumber}";
        $notifier->send($subject, "Test");
        // Update timestamp.
        file_put_contents($testEmailFile, (string)time());
    }
}

// Create checker and run.
$checker = new Checker(
    $fetcher,
    $calculator,
    $storage,
    $detector,
    $formatter,
    $output,
    $notifier
);

$exitCode = $checker->check($url);
exit($exitCode);

