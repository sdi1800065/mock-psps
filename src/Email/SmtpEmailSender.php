<?php

declare(strict_types=1);

namespace MockPsps\Email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

class SmtpEmailSender implements EmailSenderInterface
{
    public function __construct(
        private string $host,
        private int $port,
        private ?string $username,
        private ?string $password,
        private ?string $encryption,
        private string $fromEmail,
        private string $fromName,){}

    public function send(string $to, string $subject, string $body): string
    {
        $mailer = new PHPMailer(true);

        try 
        {
            $mailer->isSMTP();
            $mailer->Host = $this->host;
            $mailer->Port = $this->port;
            $mailer->SMTPAuth = $this->username !== null && $this->username !== '';

            if ($mailer->SMTPAuth) 
            {
                $mailer->Username = $this->username;
                $mailer->Password = $this->password ?? '';
            }

            if ($this->encryption === 'tls') 
            {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } 
            elseif ($this->encryption === 'ssl') 
            {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }

            $mailer->setFrom($this->fromEmail, $this->fromName);
            $mailer->addAddress($to);
            $mailer->Subject = $subject;
            $mailer->Body = $body;
            $mailer->isHTML(false);
            $mailer->send();
        } 
        catch (MailerException $exception) 
        {
            throw new \RuntimeException(sprintf('SMTP send failed: %s', $exception->getMessage()), 0, $exception);
        }

        return sprintf('smtp://%s:%d', $this->host, $this->port);
    }
}