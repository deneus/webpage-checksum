<?php

declare(strict_types=1);

namespace WebpageChecksum\Notification;

/**
 * Interface for notification services.
 * Follows Interface Segregation Principle.
 */
interface NotificationInterface
{
    /**
     * Send a notification.
     *
     * @param string $subject Notification subject.
     * @param string $message Notification message.
     * @return bool True if notification sent successfully.
     */
    public function send(string $subject, string $message): bool;
}

