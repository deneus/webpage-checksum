<?php

declare(strict_types=1);

namespace WebpageChecksum\Notification;

use WebpageChecksum\Output\OutputInterface;

/**
 * Email notification implementation.
 * Follows Single Responsibility Principle.
 */
class EmailNotifier implements NotificationInterface
{
    public function __construct(
        private readonly string $recipient,
        private readonly string $from,
        private readonly ?string $replyTo,
        private readonly OutputInterface $output
    ) {
    }

    /**
     * Send email notification.
     *
     * @param string $subject Email subject.
     * @param string $message Email message body.
     * @return bool True if email sent successfully.
     */
    public function send(string $subject, string $message): bool
    {
        if (empty($this->recipient)) {
            $this->output->writeError("Email recipient not provided.");
            return false;
        }

        if (!filter_var($this->recipient, FILTER_VALIDATE_EMAIL)) {
            $this->output->writeError("Invalid email address: {$this->recipient}");
            return false;
        }

        $headers = $this->buildHeaders();
        $result = @mail($this->recipient, $subject, $message, implode("\r\n", $headers));

        if (!$result) {
            $this->output->writeError("Failed to send email notification.");
            return false;
        }

        return true;
    }

    /**
     * Build email headers.
     *
     * @return array<string> Email headers.
     */
    private function buildHeaders(): array
    {
        $headers = [
            'From: ' . $this->from,
            'X-Mailer: PHP/' . phpversion(),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8'
        ];

        if ($this->replyTo !== null) {
            $headers[] = 'Reply-To: ' . $this->replyTo;
        }

        return $headers;
    }
}

