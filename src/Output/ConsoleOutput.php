<?php

declare(strict_types=1);

namespace WebpageChecksum\Output;

/**
 * Console output implementation.
 * Follows Single Responsibility Principle.
 */
class ConsoleOutput implements OutputInterface
{
    /**
     * Write a message to console.
     *
     * @param string $message Message to output.
     * @return void
     */
    public function write(string $message): void
    {
        echo $message . "\n";
    }

    /**
     * Write an error message to console.
     *
     * @param string $message Error message to output.
     * @return void
     */
    public function writeError(string $message): void
    {
        error_log($message);
        echo "ERROR: {$message}\n";
    }
}

