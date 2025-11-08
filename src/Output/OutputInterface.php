<?php

declare(strict_types=1);

namespace WebpageChecksum\Output;

/**
 * Interface for output services.
 * Follows Interface Segregation Principle.
 */
interface OutputInterface
{
    /**
     * Write a message to output.
     *
     * @param string $message Message to output.
     * @return void
     */
    public function write(string $message): void;

    /**
     * Write an error message to output.
     *
     * @param string $message Error message to output.
     * @return void
     */
    public function writeError(string $message): void;
}

