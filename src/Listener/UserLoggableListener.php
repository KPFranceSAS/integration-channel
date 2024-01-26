<?php

namespace App\Listener;

use App\Entity\ProductLogEntry;
use App\Entity\ProductSaleChannel;
use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Events;
use Gedmo\Loggable\LoggableListener;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::onFlush, priority: 500, connection: 'default')]
class UserLoggableListener extends LoggableListener
{
    public function __construct(private readonly Security $security)
    {
        
    }


    public function onFlush(EventArgs $eventArgs): void
    {
        if ($this->security->getUser()) {
            $this->setUsername($this->security->getUser());
        }
        parent::onFlush($eventArgs);
    }



    protected function prePersistLogEntry($logEntry, $object): void
    {
        /** @var ProductLogEntry $logEntry   */

        if ($object instanceof ProductSaleChannel) {
            $logEntry->setProductId($object->getProduct()->getId());
            $logEntry->setProductSku($object->getProduct()->getSku());
            $logEntry->setSaleChannelId($object->getSaleChannel()->getId());
            $logEntry->setSaleChannelName($object->getSaleChannel()->getName());
        }


        if ($object instanceof Promotion) {
            
            $productSaleChannel = $object->getProductSaleChannel();
            $logEntry->setProductId($productSaleChannel->getProduct()->getId());
            $logEntry->setProductSku($productSaleChannel->getProduct()->getSku());
            $logEntry->setSaleChannelId($productSaleChannel->getSaleChannel()->getId());
            $logEntry->setSaleChannelName($productSaleChannel->getSaleChannel()->getName());
        }
    }
}
