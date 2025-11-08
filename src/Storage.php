<?php

declare(strict_types=1);

namespace WebpageChecksum;

use WebpageChecksum\Output\OutputInterface;

/**
 * Handles checksum storage and retrieval.
 * Follows Single Responsibility Principle.
 */
class Storage
{
    public function __construct(
        private readonly string $storagePath,
        private readonly OutputInterface $output
    ) {
    }

    /**
     * Read previous checksum from storage.
     *
     * @return string|null Previous checksum or null if not found.
     */
    public function read(): ?string
    {
        if (!file_exists($this->storagePath)) {
            $this->output->write("No previous checksum file found.");
            return null;
        }

        $checksum = trim(file_get_contents($this->storagePath));
        $this->output->write("Previous checksum read from file: {$checksum}");
        return $checksum;
    }

    /**
     * Store checksum to storage.
     *
     * @param string $checksum Checksum to store.
     * @return bool True if stored successfully.
     */
    public function write(string $checksum): bool
    {
        $bytesWritten = file_put_contents($this->storagePath, $checksum);

        if ($bytesWritten === false) {
            $this->output->writeError("Failed to write checksum to file!");
            return false;
        }

        $this->output->write(
            "Checksum written to file: {$checksum} ({$bytesWritten} bytes)"
        );
        $this->output->write(
            "File exists after write: " . (file_exists($this->storagePath) ? 'yes' : 'no')
        );

        if (file_exists($this->storagePath)) {
            $this->output->write(
                "File contents after write: " . trim(file_get_contents($this->storagePath))
            );
        }

        return true;
    }

    /**
     * Get storage path.
     *
     * @return string Storage file path.
     */
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }
}

