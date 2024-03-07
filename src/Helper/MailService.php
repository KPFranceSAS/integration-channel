<?php

namespace App\Helper;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use function Symfony\Component\String\s;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailService
{
    private $manager;

    /**
     * Constructor
     */
    public function __construct(private readonly MailerInterface $mailer, private readonly LoggerInterface $logger, ManagerRegistry $managerRegistry)
    {
        $this->manager = $managerRegistry->getManager();
    }


    /**
     * Send an email
     *
     * @param string $titre tht title of the email
     * @param string $contenu the content of the email
     * @param string|Address|array $emails the recipients
     * @return void
     */
    public function sendEmail($titre, $contenu, $emails = 'devops@kpsport.com')
    {
        $this->logger->info("Sending email $titre to " . json_encode($emails) . "  > $contenu ");

        if ($this->needTobeRoute($titre, $contenu)) {
            $emails = 'devops@kpsport.com';
            $this->logger->info("Reroute email $titre to $emails");
        }

        $email = (new Email())
            ->from(new Address('devops@kpsport.com', 'DEV OPS'))
            ->subject("[Patxira] ".$titre)
            ->html($contenu);
        if (is_array($emails)) {
            foreach ($emails as $emailRecipient) {
                $email->addTo($emailRecipient);
            }
        } else {
            $email->to($emails);
        }

        $this->mailer->send($email);
    }


    /**
     */
    public function sendEmailChannel($channel, $titre, $contenu)
    {
        $this->logger->info("Sending email $titre to " . $channel . "  > $contenu ");

        $emails = [];

        $users = $this->manager->getRepository(User::class)->findAll();
        foreach ($users as $user) {
            if ($user->hasChannel($channel)) {
                $emails[] = $user->getEmail();
            }
        }

        $newTitre = '[' . $channel . '] ' . $titre;

        if (count($emails) > 0) {
            $this->sendEmail($newTitre, $contenu, $emails);
        } else {
            $this->sendEmail($newTitre, $contenu);
        }
    }


    private function needTobeRoute($titre, $contenu)
    {
        $stringForbiddens = [
            'REPORT AMAZON',
            "Syntax error",
            'cURL error',
            'Client error:',
            'stock files published',
            'Server error:',
            'Unable to authenticate using a private key',
            'CURLE_OPERATION_TIMEDOUT',
            'Error connecting to the API',
            'Error class',
            "InternalError",
            'SQLSTATE',
            'Exception'
        ];
        if (s($titre)->containsAny($stringForbiddens)) {
            return true;
        }

        if (s($contenu)->containsAny($stringForbiddens)) {
            return true;
        }


        return false;
    }



    /**
     */
    public function sendEmailRole($role, $titre, $contenu)
    {
        $this->logger->info("Sending email $titre to role " . $role . "  > $contenu ");

        $emails = [];

        $users = $this->manager->getRepository(User::class)->findAll();
        foreach ($users as $user) {
            if ($user->hasRole($role)) {
                $emails[] = $user->getEmail();
            }
        }

        $newTitre = $titre;

        if (count($emails) > 0) {
            $this->sendEmail($newTitre, $contenu, $emails);
        } else {
            $this->sendEmail($newTitre, $contenu);
        }
    }
}
