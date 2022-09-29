<?php

namespace App\Tests\Service\BusinessCentral;

use App\Helper\BusinessCentral\Model\CustomerPayment;
use App\Service\BusinessCentral\KitPersonalizacionSportConnector;
use App\Service\Flashled\FlashledIntegrateOrder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PaymentJournalTest extends KernelTestCase
{
    public function testLists(): void
    {
        $bcConnector = static::getContainer()->get(KitPersonalizacionSportConnector::class);
        $customerJournals = $bcConnector->getAllCustomerPaymentJournals();
        $this->assertIsArray($customerJournals);
    }




    public function testGetJournal(): void
    {
        $bcConnector = static::getContainer()->get(KitPersonalizacionSportConnector::class);
        $customerJournal = $bcConnector->getCustomerPaymentJournalByCode('SABADELL');
        
        $this->assertIsArray($customerJournal);
        $this->assertArrayHasKey('code', $customerJournal);

        $payments = $bcConnector->getAllCustomerPaymentJournalByJournal($customerJournal['id']);
        $this->assertIsArray($payments);
    }


   public function testAddPaymentToCustomerJournal(): void
   {
       $bcConnector = static::getContainer()->get(KitPersonalizacionSportConnector::class);
       $customerJournal = $bcConnector->getCustomerPaymentJournalByCode('SABADELL');
       $payments = $bcConnector->getAllCustomerPaymentJournalByJournal($customerJournal['id']);

       $customerPayment = new CustomerPayment();
       $customerPayment->amount = 200.19;
       $customerPayment->customerNumber = FlashledIntegrateOrder::FLASHLED_CUSTOMER_NUMBER;
       $customerPayment->postingDate = date('Y-m-d');
       $customerPayment->externalDocumentNumber = 'XXXX-'.date('Ymd_His');
       $customerPayment->comment = 'Comment-'.date('Ymd_His');
       $customerPayment->description = 'Description-'.date('Ymd_His');

       $result = $bcConnector->createCustomerPayment($customerJournal['id'], $customerPayment->transformToArray());
       $this->assertIsArray($result);
       $paymentsNew = $bcConnector->getAllCustomerPaymentJournalByJournal($customerJournal['id']);
       $this->assertEquals(count($payments)+1, count($paymentsNew));
   }
}
