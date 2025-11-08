<?php

declare(strict_types=1);

namespace WebpageChecksum;

/**
 * Formats notification messages.
 * Follows Single Responsibility Principle.
 */
class MessageFormatter
{
    /**
     * Format checksum change notification message.
     *
     * @param string $url Webpage URL.
     * @param string $timestamp Timestamp of change.
     * @param string $previousChecksum Previous checksum.
     * @param string $currentChecksum Current checksum.
     * @return array{subject: string, message: string} Formatted subject and message.
     */
    public function formatChecksumChange(
        string $url,
        string $timestamp,
        string $previousChecksum,
        string $currentChecksum
    ): array {
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
            $currentChecksum
        );

        return [
            'subject' => $subject,
            'message' => $message
        ];
    }
}

