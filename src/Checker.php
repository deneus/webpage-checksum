<?php

declare(strict_types=1);

namespace WebpageChecksum;

use WebpageChecksum\Notification\NotificationInterface;
use WebpageChecksum\Output\OutputInterface;

/**
 * Main checksum checker orchestrator.
 * Follows Single Responsibility Principle and Dependency Inversion.
 */
class Checker
{
    public function __construct(
        private readonly Fetcher $fetcher,
        private readonly Calculator $calculator,
        private readonly Storage $storage,
        private readonly Detector $detector,
        private readonly MessageFormatter $formatter,
        private readonly OutputInterface $output,
        private readonly ?NotificationInterface $notifier = null
    ) {
    }

    /**
     * Check webpage for checksum changes.
     *
     * @param string $url URL to check.
     * @return int Exit code (0 = no change, 1 = change detected).
     */
    public function check(string $url): int
    {
        $this->output->write("Checksum file path: {$this->storage->getStoragePath()}");
        $this->output->write(
            "File exists: " . (file_exists($this->storage->getStoragePath()) ? 'yes' : 'no')
        );

        // Fetch webpage content.
        $html = $this->fetcher->fetch($url);

        // Calculate checksum.
        $currentChecksum = $this->calculator->calculate($html);
        $timestamp = date('Y-m-d H:i:s');

        // Read previous checksum.
        $previousChecksum = $this->storage->read();

        // Detect changes.
        if ($this->detector->hasChanged($previousChecksum, $currentChecksum)) {
            return $this->handleChange($url, $timestamp, $previousChecksum, $currentChecksum);
        }

        if ($this->detector->isFirstCheck($previousChecksum)) {
            return $this->handleFirstCheck($currentChecksum);
        }

        return $this->handleNoChange($currentChecksum);
    }

    /**
     * Handle checksum change.
     *
     * @param string $url Webpage URL.
     * @param string $timestamp Timestamp.
     * @param string $previousChecksum Previous checksum.
     * @param string $currentChecksum Current checksum.
     * @return int Exit code.
     */
    private function handleChange(
        string $url,
        string $timestamp,
        string $previousChecksum,
        string $currentChecksum
    ): int {
        $this->output->write("Checksum changed!");
        $this->output->write("Previous: {$previousChecksum}");
        $this->output->write("Current:  {$currentChecksum}");
        $this->output->write("Timestamp: {$timestamp}");

        // Store new checksum.
        $this->storage->write($currentChecksum);

        // Send notification if configured.
        if ($this->notifier !== null) {
            $formatted = $this->formatter->formatChecksumChange(
                $url,
                $timestamp,
                $previousChecksum,
                $currentChecksum
            );

            if ($this->notifier->send($formatted['subject'], $formatted['message'])) {
                $this->output->write("Email notification sent successfully.");
            } else {
                $this->output->write("Failed to send email notification.");
            }
        } else {
            $this->output->write("Email recipient not configured. Skipping notification.");
        }

        // Exit with code 1 to indicate change (can be used in CI/CD).
        return 1;
    }

    /**
     * Handle first check (no previous checksum).
     *
     * @param string $checksum Current checksum.
     * @return int Exit code.
     */
    private function handleFirstCheck(string $checksum): int
    {
        $this->output->write("First check - storing checksum: {$checksum}");
        $this->storage->write($checksum);
        return 0;
    }

    /**
     * Handle no change detected.
     *
     * @param string $checksum Current checksum.
     * @return int Exit code.
     */
    private function handleNoChange(string $checksum): int
    {
        $this->output->write("Checksum unchanged: {$checksum}");
        return 0;
    }
}

