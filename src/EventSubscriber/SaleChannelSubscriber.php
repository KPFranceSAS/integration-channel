<?php

namespace App\EventSubscriber;

use App\Entity\Product;
use App\Entity\ProductSaleChannel;
use App\Entity\SaleChannel;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SaleChannelSubscriber implements EventSubscriberInterface
{
    private $manager;

    public function __construct(
        ManagerRegistry $manager
    ) {
        $this->manager = $manager->getManager();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['saveProductSaleChannels'],
        ];
    }


    public function saveProductSaleChannels(BeforeEntityPersistedEvent $event)
    {
        $entity = $event->getEntityInstance();
        if (!($entity instanceof SaleChannel)) {
            return;
        }
        /** @var array[\App\Entity\Product] */
        $products= $this->manager->getRepository(Product::class)->findAll();
        foreach ($products as $product) {
            $productSaleChannel = new ProductSaleChannel();
            $productSaleChannel->setProduct($product);
            $entity->addProductSaleChannel($productSaleChannel);
        }
    }
}
