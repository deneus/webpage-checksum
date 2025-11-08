<?php

declare(strict_types=1);

namespace WebpageChecksum;

/**
 * Detects checksum changes.
 * Follows Single Responsibility Principle.
 */
class Detector
{
    /**
     * Check if checksum has changed.
     *
     * @param string|null $previousChecksum Previous checksum.
     * @param string $currentChecksum Current checksum.
     * @return bool True if checksum changed.
     */
    public function hasChanged(?string $previousChecksum, string $currentChecksum): bool
    {
        return $previousChecksum !== null && $previousChecksum !== $currentChecksum;
    }

    /**
     * Check if this is the first check (no previous checksum).
     *
     * @param string|null $previousChecksum Previous checksum.
     * @return bool True if this is the first check.
     */
    public function isFirstCheck(?string $previousChecksum): bool
    {
        return $previousChecksum === null;
    }
}

