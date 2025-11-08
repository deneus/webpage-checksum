<?php

declare(strict_types=1);

namespace WebpageChecksum;

/**
 * Calculates checksums.
 * Follows Single Responsibility Principle.
 */
class Calculator
{
    /**
     * Calculate MD5 checksum of content.
     *
     * @param string $content Content to checksum.
     * @return string MD5 checksum.
     */
    public function calculate(string $content): string
    {
        return md5($content);
    }
}

