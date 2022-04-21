<?php

namespace App\Service\Amazon\History;

use App\Entity\AmazonFinancialEvent;
use App\Entity\AmazonOrder;
use App\Entity\AmazonReimbursement;
use App\Entity\AmazonReturn;

class AmzHistoryEvent
{

    const TYPE_FINANCIAL = 'Financial';

    const TYPE_ORDER = 'Order';

    const TYPE_REIMBURSEMENT = 'Reimbursement';

    const TYPE_RETURN = 'Return';


    public $dateEvent;

    public $name;

    public $type;

    public $object;



    public static function createOneFromAmazonFinancialEvent(AmazonFinancialEvent $amazonFinancialEvent)
    {
        $amzHistoryEvent = new AmzHistoryEvent();
        $amzHistoryEvent->dateEvent = $amazonFinancialEvent->getPostedDate();
        $amzHistoryEvent->type = AmzHistoryEvent::TYPE_FINANCIAL;
        $amzHistoryEvent->object = $amazonFinancialEvent;
        return $amzHistoryEvent;
    }


    public static function createOneFromAmazonOrder(AmazonOrder $amazonOrder)
    {
        $amzHistoryEvent = new AmzHistoryEvent();
        $amzHistoryEvent->dateEvent = $amazonOrder->getPurchaseDate();
        $amzHistoryEvent->type = AmzHistoryEvent::TYPE_ORDER;
        $amzHistoryEvent->object = $amazonOrder;
        return $amzHistoryEvent;
    }



    public static function createOneFromAmazonReturn(AmazonReturn $amazonReturn)
    {
        $amzHistoryEvent = new AmzHistoryEvent();
        $amzHistoryEvent->dateEvent = $amazonReturn->getReturnDate();
        $amzHistoryEvent->type = AmzHistoryEvent::TYPE_RETURN;
        $amzHistoryEvent->object = $amazonReturn;
        return $amzHistoryEvent;
    }


    public static function createOneFromAmazonReimbursement(AmazonReimbursement $amazonReimbursement)
    {
        $amzHistoryEvent = new AmzHistoryEvent();
        $amzHistoryEvent->dateEvent = $amazonReimbursement->getApprovalDate();
        $amzHistoryEvent->type = AmzHistoryEvent::TYPE_REIMBURSEMENT;
        $amzHistoryEvent->object = $amazonReimbursement;
        return $amzHistoryEvent;
    }
}
