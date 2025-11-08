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
$emailReplyTo = getenv('EMAIL_REPLY_TO');

if (!empty($emailTo)) {
    $notifier = new EmailNotifier($emailTo, $emailFrom, $emailReplyTo, $output);
    $notifier->send("Test", "Test");
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

