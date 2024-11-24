<?php

namespace app\services;
use api\phpmailer\PHPMailer;

class EmailSendingService
{
    private $phpMailer;
    private $emailConfig;

    public function __construct()
    {
        $this->phpMailer = new PHPMailer();
        $this->emailConfig = include __DIR__ . '/../config/email_config.php';

        $this->login();
        $this->configureMailer();
    }

    private function configureMailer()
    {
        $this->phpMailer->CharSet = $this->emailConfig['charset'];
        $this->phpMailer->setFrom($this->emailConfig['username'], $this->emailConfig['from_name']);
        $this->phpMailer->addReplyTo($this->emailConfig['username'], $this->emailConfig['from_name']);
        $this->phpMailer->isHTML(true);
    }

    private function login()
    {
        $this->phpMailer->isSMTP();
        $this->phpMailer->Host = $this->emailConfig['host'];
        $this->phpMailer->Username = $this->emailConfig['username'];
        $this->phpMailer->Password = $this->emailConfig['password'];
        $this->phpMailer->SMTPSecure = $this->emailConfig['smtp_secure'];
        $this->phpMailer->Port = $this->emailConfig['port'];
        $this->phpMailer->SMTPAuth = true;
    }

    public function sendEmail($to, $subject, $body)
    {
        $this->phpMailer->addAddress($to);
        $this->phpMailer->Subject = $subject;
        $this->phpMailer->Body = $body;

        if (!$this->phpMailer->send()) {
            throw new \Exception("Email could not be sent. Mailer Error: " . $this->phpMailer->ErrorInfo);
        }
    }
}