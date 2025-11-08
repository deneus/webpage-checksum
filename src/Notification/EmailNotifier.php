<?php

declare(strict_types=1);

namespace WebpageChecksum\Notification;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use WebpageChecksum\Output\OutputInterface;

/**
 * Email notification implementation using PHPMailer with SMTP.
 * Follows Single Responsibility Principle.
 */
class EmailNotifier implements NotificationInterface
{
    public function __construct(
        private readonly string $recipient,
        private readonly string $from,
        private readonly ?string $replyTo,
        private readonly OutputInterface $output,
        private readonly ?string $smtpHost = null,
        private readonly ?int $smtpPort = null,
        private readonly ?string $smtpUsername = null,
        private readonly ?string $smtpPassword = null,
        private readonly ?string $smtpEncryption = null
    ) {
    }

    /**
     * Send email notification using PHPMailer.
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

        // Validate sender email.
        if (!filter_var($this->from, FILTER_VALIDATE_EMAIL)) {
            $this->output->writeError("Invalid sender email address: {$this->from}");
            return false;
        }

        $this->output->write("Attempting to send email...");
        $this->output->write("To: {$this->recipient}");
        $this->output->write("From: {$this->from}");
        $this->output->write("Subject: {$subject}");

        try {
            $mail = new PHPMailer(true);

            // If SMTP is configured, use it. Otherwise, use PHP mail().
            if ($this->smtpHost !== null && $this->smtpUsername !== null && $this->smtpPassword !== null) {
                // SMTP configuration.
                $mail->isSMTP();
                $mail->Host = $this->smtpHost;
                $mail->SMTPAuth = true;
                $mail->Username = $this->smtpUsername;
                $mail->Password = $this->smtpPassword;
                $mail->Port = $this->smtpPort ?? 587;

                if ($this->smtpEncryption !== null) {
                    $mail->SMTPSecure = $this->smtpEncryption;
                } else {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                }

                $this->output->write("Using SMTP: {$this->smtpHost}:{$mail->Port}");
            } else {
                // Use PHP mail() function.
                $mail->isMail();
                $this->output->write("Using PHP mail() function (SMTP not configured)");
            }

            // Set email details.
            $mail->setFrom($this->from);
            $mail->addAddress($this->recipient);

            if ($this->replyTo !== null) {
                $mail->addReplyTo($this->replyTo);
            }

            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->CharSet = 'UTF-8';

            // Send email.
            $mail->send();
            $this->output->write("Email sent successfully!");

            return true;
        } catch (Exception $e) {
            $errorInfo = isset($mail) ? $mail->ErrorInfo : 'Unknown error';
            $this->output->writeError("Failed to send email: {$errorInfo}");
            $this->output->writeError("Exception: {$e->getMessage()}");
            return false;
        }
    }
}

