<?php

namespace App\EventSubscriber;

use App\Controller\Pricing\PricingCrudController;
use App\Entity\Product;
use App\Entity\ProductLogEntry;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class PricingSubscriber implements EventSubscriberInterface
{
    private $chartBuilder;

    private $managerRegistry;


    public function __construct(
        ChartBuilderInterface $chartBuilder,
        ManagerRegistry $managerRegistry
    ) {
        $this->chartBuilder = $chartBuilder;
        $this->managerRegistry = $managerRegistry->getManager();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterCrudActionEvent::class => ['setDisplayContent'],
        ];
    }


   

    public function setDisplayContent(AfterCrudActionEvent $event)
    {
        $context = $event->getAdminContext();
        $instance = $context->getEntity()->getInstance();

        if (!($instance instanceof Product)) {
            return;
        }

        if ($context->getCrud()->getControllerFqcn() == PricingCrudController::class) {
            $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);

            $dateNow = new DateTime();
            $dateNow->setTime(18, 0);
            $dateAfter = new DateTime();
            $dateAfter->add(new DateInterval('P15D'));
            $biggerPrice = 0;
            $labels = [];
            $datasets = [];
            while ($dateNow < $dateAfter) {
                foreach ($instance->getProductSaleChannels() as $productSaleChannel) {
                    if ($productSaleChannel->getEnabled()) {
                        if (!array_key_exists($productSaleChannel->getSaleChannel()->getCode(), $datasets)) {
                            $color = $productSaleChannel->getSaleChannel()->getColor();
                            $datasets[$productSaleChannel->getSaleChannel()->getCode()] =  [
                                'label' => $productSaleChannel->getSaleChannel()->getName(),
                                'backgroundColor' => $color,
                                'borderColor' => $color,
                                'data' => [],
                            ];
                        }
                        $salePrice =  $productSaleChannel->getSalePrice($dateNow);
                        $biggerPrice = $biggerPrice > $salePrice ? $biggerPrice : $salePrice;
                        $datasets[$productSaleChannel->getSaleChannel()->getCode()]['data'][] = $salePrice;
                    }
                }
                

                $labels[] = $dateNow->format('Y-m-d H:i');
                $dateNow->add(new DateInterval('PT12H'));
            }


            $chart->setData([
                'labels' => $labels,
                'datasets' => array_values($datasets),
            ]);

            

            $chart->setOptions([
                'scales' => [
                    'y' => [
                        'suggestedMin' => 0,
                        'suggestedMax' => 1.2*$biggerPrice,
                    ],
                ],
            ]);


            $event->getResponseParameters()->set('chart', $chart);



            $logs = $this->managerRegistry->getRepository(ProductLogEntry::class)->findBy([
                'productId' => $instance->getId()
            ], ['loggedAt'=>'DESC']);
            $event->getResponseParameters()->set('logs', $logs);
        }
    }
}
