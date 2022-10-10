<?php

namespace App\Tests\Helper;

use App\Helper\MailService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;

class MailServiceTest extends KernelTestCase
{
    use MailerAssertionsTrait;

    public function testEmailBasic(): void
    {
        $mailService = static::getContainer()->get(MailService::class);
        $mailService->sendEmail('Test', 'Test contenu', 'devops@kpsport.com');
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'Test');
    }



    public function testEmailMulti(): void
    {
        $mailService = static::getContainer()->get(MailService::class);
        $mailService->sendEmail('Test', 'Test contenu', ['test@kpsport.com', 'stephane.lanjard@kpsport.com']);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'Test');
    }




    public function testEmailChannelBasic(): void
    {
        $mailService = static::getContainer()->get(MailService::class);
        $mailService->sendEmailChannel('CHANNELADVISOR', 'Test', 'Test contenu');
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'Test');
    }
}
