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

                // Enable verbose debugging for SMTP.
                $mail->SMTPDebug = 0; // Set to 2 for detailed debugging.
                $mail->Debugoutput = function ($str, $level) {
                    // Only output errors, not all debug info.
                    if (strpos($str, 'Error') !== false || strpos($str, 'Failed') !== false) {
                        error_log("SMTP Debug: {$str}");
                    }
                };

                $this->output->write("Using SMTP: {$this->smtpHost}:{$mail->Port}");
                $this->output->write("SMTP Username: {$this->smtpUsername}");
                $this->output->write("SMTP Encryption: {$mail->SMTPSecure}");
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
            
            // Provide helpful troubleshooting information for authentication errors.
            if (strpos($errorInfo, 'authenticate') !== false || strpos($e->getMessage(), 'authenticate') !== false) {
                $this->output->writeError("");
                $this->output->writeError("SMTP Authentication Troubleshooting:");
                $this->output->writeError("1. You MUST use an App Password, not your regular password.");
                $this->output->writeError("2. Enable 2-factor authentication in your email account.");
                $this->output->writeError("3. Use the full email address as SMTP_USERNAME.");
                $this->output->writeError("");
                $this->output->writeError("For Gmail:");
                $this->output->writeError("   - Generate App Password: https://myaccount.google.com/apppasswords");
                $this->output->writeError("   - SMTP_HOST: smtp.gmail.com");
                $this->output->writeError("   - SMTP_PORT: 587");
                $this->output->writeError("   - SMTP_ENCRYPTION: tls");
                $this->output->writeError("");
                $this->output->writeError("For Hotmail/Outlook:");
                $this->output->writeError("   - Generate App Password: https://account.microsoft.com/security");
                $this->output->writeError("   - SMTP_HOST: smtp-mail.outlook.com");
                $this->output->writeError("   - SMTP_PORT: 587");
                $this->output->writeError("   - SMTP_ENCRYPTION: tls");
                $this->output->writeError("");
                $this->output->writeError("4. Verify your SMTP credentials are correct in GitHub Secrets.");
            }
            
            return false;
        }
    }
}

