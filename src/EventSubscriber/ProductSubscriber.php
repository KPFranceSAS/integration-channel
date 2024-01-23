<?php

namespace App\EventSubscriber;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Controller\Pricing\PricingCrudController;
use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityDeletedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityRemoveException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;


class ProductSubscriber implements EventSubscriberInterface
{
   

    private $connector;


    public function __construct(
        KitPersonalizacionSportConnector $connector
        
    ) {
        $this->connector = $connector;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityDeletedEvent::class => ['checkIfInBc'],
        ];
    }


   

    public function checkIfInBc(BeforeEntityDeletedEvent $event)
    {
        $instance = $event->getEntityInstance();

        if (!($instance instanceof Product)) {
            return;
        }


        $itemBc = $this->connector->getItemByNumber($instance->getSku());

        if(!$itemBc){
            return;
        } else {
            $event->setResponse(new Response('Product exists in BC and cannot be deleted'));
        }

    }

   
}
