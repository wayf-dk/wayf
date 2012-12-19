<?php
namespace WAYF;

use WAYF\EventLoggeInterface;

class MailLogger implements EventLoggeInterface
{
    public $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function log(\WAYF\Event $event)
    {
        $to      = $this->config['notification.email'];
        $subject = "MDApprover - {$event->title}";
        $message = $event->message . "\n\n\n{$event->user} - {$event->time}";
        $headers = 'From: mdapprover@wayf.dk' . "\r\n" .
                   'Reply-To: no-reply@wayf.dk' . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();

        mail($to, $subject, $message, $headers);
    }
}
