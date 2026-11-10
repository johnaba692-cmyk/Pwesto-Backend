<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once ROOT_DIR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

class Pwesto_mailer
{
    public function isConfigured()
    {
        return $this->configurationError() === null;
    }

    public function configurationError()
    {
        $username = $this->setting('PWESTO_MAIL_USERNAME');
        $password = $this->setting('PWESTO_MAIL_PASSWORD');
        $from = $this->senderAddress();
        $port = $this->setting('PWESTO_MAIL_PORT') ?: '587';

        if ($username === '' || $password === '') {
            return 'Set the Gmail address and Google App Password in PWESTO_MAIL_USERNAME and PWESTO_MAIL_PASSWORD.';
        }
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return 'Set a valid Gmail address in PWESTO_MAIL_USERNAME or PWESTO_MAIL_FROM.';
        }
        if (!ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
            return 'PWESTO_MAIL_PORT must be a valid TCP port.';
        }
        return null;
    }

    public function send($recipient, $recipientName, $subject, $htmlBody, $textBody)
    {
        $configurationError = $this->configurationError();
        if ($configurationError !== null) {
            throw new RuntimeException($configurationError);
        }
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid email recipient is required.');
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $this->setting('PWESTO_MAIL_HOST') ?: 'smtp.gmail.com';
        $mail->Port = (int) ($this->setting('PWESTO_MAIL_PORT') ?: '587');
        $mail->SMTPAuth = true;
        $mail->Timeout = 15;
        $mail->Username = $this->setting('PWESTO_MAIL_USERNAME');
        $mail->Password = preg_replace('/\s+/', '', $this->setting('PWESTO_MAIL_PASSWORD'));
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom(
            $this->senderAddress(),
            $this->setting('PWESTO_MAIL_FROM_NAME') ?: 'Pwesto'
        );
        $mail->addAddress($recipient, $recipientName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody;
        $mail->send();
    }

    private function setting($key)
    {
        $value = getenv($key);
        return is_string($value) ? trim($value) : '';
    }

    private function senderAddress()
    {
        return $this->setting('PWESTO_MAIL_FROM') ?: $this->setting('PWESTO_MAIL_USERNAME');
    }
}
