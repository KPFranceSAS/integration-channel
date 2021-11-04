<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;


class MailService
{


    private $logger;

    private $mailer;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(MailerInterface $mailer, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }


    /**
     * Send an email
     *
     * @param string $titre tht title of the email
     * @param string $contenu the content of the email
     * @param string|Adress $emails the recipients
     * @return void
     */
    public function sendEmail($titre, $contenu, $emails = 'devops@kpsport.com')
    {
        $this->logger->info("Sending email $titre  > $contenu");

        $email = (new Email())
            ->from(new Address('devops@kpsport.com', 'DEV OPS'))
            ->to($emails)
            ->subject($titre)
            ->html($contenu);
        $this->mailer->send($email);
    }
}
