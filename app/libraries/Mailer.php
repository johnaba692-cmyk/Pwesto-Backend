<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Gmail SMTP mailer built on PHPMailer (Composer). Settings come from
 * config_item('mail') in app/config/pwesto.php.
 *
 * Why not the built-in LavaLust Email library: it sends with PHP mail(),
 * which (as the LavaLust docs state) has no SMTP authentication. Gmail
 * requires authenticated SMTP over STARTTLS, so this wrapper uses the
 * third-party PHPMailer package loaded through Composer autoloading.
 *
 * Load with: $this->call->library('mailer');
 */
class Mailer
{
    public function configurationError()
    {
        $settings = $this->settings();
        if ($settings['username'] === '' || $settings['password'] === '') {
            return 'Set the Gmail address and Google App Password in PWESTO_MAIL_USERNAME and PWESTO_MAIL_PASSWORD.';
        }
        if (!filter_var($this->senderAddress(), FILTER_VALIDATE_EMAIL)) {
            return 'Set a valid Gmail address in PWESTO_MAIL_USERNAME or PWESTO_MAIL_FROM.';
        }
        $port = $settings['port'];
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

        $settings = $this->settings();
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $settings['host'];
        $mail->Port = (int) $settings['port'];
        $mail->SMTPAuth = true;
        $mail->Timeout = 15;
        $mail->Username = $settings['username'];
        $mail->Password = preg_replace('/\s+/', '', $settings['password']);
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom($this->senderAddress(), $settings['from_name']);
        $mail->addAddress($recipient, $recipientName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody;
        $mail->send();
    }

    private function settings()
    {
        return array_merge([
            'host' => 'smtp.gmail.com',
            'port' => '587',
            'username' => '',
            'password' => '',
            'from' => '',
            'from_name' => 'Pwesto',
        ], (array) config_item('mail'));
    }

    private function senderAddress()
    {
        $settings = $this->settings();
        return $settings['from'] !== '' ? $settings['from'] : $settings['username'];
    }
}
