<?php

declare(strict_types=1);

namespace WebpageChecksum;

use WebpageChecksum\Output\OutputInterface;

/**
 * Fetches webpage content.
 * Follows Single Responsibility Principle.
 */
class Fetcher
{
    public function __construct(
        private readonly OutputInterface $output
    ) {
    }

    /**
     * Fetch HTML content from a URL.
     *
     * @param string $url URL to fetch.
     * @return string HTML content.
     * @throws \RuntimeException If fetch fails.
     */
    public function fetch(string $url): string
    {
        $html = @file_get_contents($url);

        if ($html === false) {
            $error = "Failed to fetch webpage from {$url}";
            $this->output->writeError($error);
            throw new \RuntimeException($error);
        }

        return $html;
    }
}

