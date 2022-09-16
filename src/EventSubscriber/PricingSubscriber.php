<?php

namespace App\EventSubscriber;

use App\Controller\Pricing\PricingCrudController;
use App\Entity\Product;
use DateInterval;
use DateTime;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class PricingSubscriber implements EventSubscriberInterface
{
    private $chartBuilder;

    public function __construct(
        ChartBuilderInterface $chartBuilder
    ) {
        $this->chartBuilder = $chartBuilder;
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
            $dateNow->setTime(12, 0);
            $dateAfter = new DateTime();
            $dateAfter->add(new DateInterval('P10D'));

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
                        $datasets[$productSaleChannel->getSaleChannel()->getCode()]['data'][] = $productSaleChannel->getSalePrice($dateNow);
                    }
                }
                

                $labels[] = $dateNow->format('Y-m-d H:i');
                $dateNow->add(new DateInterval('PT6H'));
            }


            $chart->setData([
                'labels' => $labels,
                'datasets' => array_values($datasets),
            ]);

            $chart->setOptions([
                'scales' => [
                    'y' => [
                        'suggestedMin' => 0,
                    ],
                ],
            ]);


            $event->getResponseParameters()->set('chart', $chart);
        }
    }
}
