<?php

namespace App\Listener;

use App\Entity\ProductSaleChannel;
use App\Entity\Promotion;
use Doctrine\Common\EventArgs;
use Gedmo\Loggable\LoggableListener;
use Symfony\Component\Security\Core\Security;

class UserLoggableListener extends LoggableListener
{
    public function setSecurity(Security $security)
    {
        $this->security = $security;
    }


    public function onFlush(EventArgs $eventArgs)
    {
        if ($this->security->getUser()) {
            $this->setUsername($this->security->getUser());
        }
        parent::onFlush($eventArgs);
    }



    protected function prePersistLogEntry($logEntry, $object)
    {
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
