<?php

namespace App\Service;

use function Symfony\Component\String\s;
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
    public function sendEmail($titre, $contenu, $emails = 'marketplace-alerts@kpsport.com')
    {
        $this->logger->info("Sending email $titre to " . json_encode($emails) . "  > $contenu ");

        if ($this->needTobeRoute($titre, $contenu)) {
            $emails = 'stephane.lanjard@kpsport.com';
            $this->logger->info("Reroute email $titre to $emails");
        }

        $email = (new Email())
            ->from(new Address('devops@kpsport.com', 'DEV OPS'))
            ->to($emails)
            ->subject($titre)
            ->html($contenu);
        $this->mailer->send($email);
    }


    private function needTobeRoute($titre, $contenu)
    {
        $stringForbiddens = ['REPORT AMAZON',  'cURL error', 'Client error:', 'stock files published'];
        if (s($titre)->containsAny($stringForbiddens)) {
            return true;
        }

        if (s($contenu)->containsAny($stringForbiddens)) {
            return true;
        }


        return false;
    }
}
