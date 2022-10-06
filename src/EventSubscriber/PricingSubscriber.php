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
            $chart = $this->buildChartNextPrices($instance);
            $event->getResponseParameters()->set('chartNextPrices', $chart);


            $chart = $this->buildChartHistoryPrices($instance);
            $event->getResponseParameters()->set('chartHistoryPrices', $chart);


            $logs = $this->managerRegistry->getRepository(ProductLogEntry::class)->findBy([
                'productId' => $instance->getId()
            ], ['loggedAt'=>'DESC']);
            $event->getResponseParameters()->set('logs', $logs);
        }
    }

    protected function buildChartNextPrices(Product $product): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $biggerPrice = 0;
        $labels = [];
        $datasets = [];
        $nextDays = 90;
        foreach ($product->getProductSaleChannels() as $productSaleChannel) {
            if ($productSaleChannel->getEnabled()) {
                $dateNow = new DateTime();
                $dateNow->setTime(8, 0);
                $dateAfter = new DateTime();
                $dateAfter->add(new DateInterval('P'.$nextDays.'D'));
                $dateAfter->setTime(8, 0);
                $currency = $productSaleChannel->getSaleChannel()->getCurrencyCode();
                $color = $productSaleChannel->getSaleChannel()->getColor();
                $codeSaleChannel = $productSaleChannel->getSaleChannel()->getCode();

                $datasets[$codeSaleChannel] =  [
                            'label' => $productSaleChannel->getSaleChannel()->getName(),
                            'backgroundColor' => $color,
                            'borderColor' => $color,
                            'data' => [],
                ];


                $salePrice =  $productSaleChannel->getSalePrice($dateNow);
                $datasets[$codeSaleChannel]['data'][] = [
                    'y'=>$salePrice,
                    'x'=> $dateNow->format('Y-m-d H:i'),
                    "label" => $productSaleChannel->getSalePriceDescription($dateNow),
                    "currency" => $currency
                ];
                        
                while ($dateNow < $dateAfter) {
                    $dateNow->add(new DateInterval('PT1M'));
                    $promoPrice =  $productSaleChannel->getSalePrice($dateNow);
                    if ($promoPrice!=$salePrice) {
                        $datenew = clone($dateNow);
                        $datenew->sub(new DateInterval('PT1M'));
                        $datasets[$codeSaleChannel]['data'][] = [
                            'y'=>$salePrice,
                            'x'=> $datenew->format('Y-m-d H:i'),
                            "label" => $productSaleChannel->getSalePriceDescription($datenew),
                            "currency" => $currency
                        ];

                        $salePrice = $promoPrice;
                        $datasets[$codeSaleChannel]['data'][] = [
                            'y'=>$promoPrice,
                            'x'=> $dateNow->format('Y-m-d H:i'),
                            "label" => $productSaleChannel->getSalePriceDescription($dateNow),
                            "currency" => $currency
                        ];
                    }

                    $biggerPrice = $biggerPrice > $salePrice ? $biggerPrice : $salePrice;
                }
                $datasets[$codeSaleChannel]['data'][] =
                    [
                     'y'=>$productSaleChannel->getSalePrice($dateAfter),
                     'x'=> $dateAfter->format('Y-m-d H:i'),
                     "label" => $productSaleChannel->getSalePriceDescription($dateAfter),
                     "currency" => $currency
                    ];
            }
        }
        
            


        $chart->setData([
            'labels' => $labels,
            'datasets' => array_values($datasets),
        ]);

            

        $chart->setOptions(
            [
            'plugins' => [
                    'title' => [
                        "display" => true,
                        'text' => 'Prices per marketplace for next '.$nextDays.' days'
                    ],
                ],
            'scales' => [
                'y' => [
                    'min' => 0,
                    'suggestedMax' => 1.2*$biggerPrice,
                ],
                'x' => [
                    'type' => 'time',
                    "title" => [
                        "display" => true,
                        "text" => 'Date'
                    ],
                        'time' => [
                            "unit" =>'day'
                          ],
                    ],
                    
                ],
            ],
        );

        return $chart;
    }




    protected function buildChartHistoryPrices(Product $product): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $labels = [];
        $datasets = [];
        $dateNow = new DateTime();
        $biggerPrice = 10;
        foreach ($product->getProductSaleChannels() as $productSaleChannel) {
            if (count($productSaleChannel->getProductSaleChannelHistories())>1) {
                $color = $productSaleChannel->getSaleChannel()->getColor();
                $currency = $productSaleChannel->getSaleChannel()->getCurrencyCode();
                $codeSaleChannel = $productSaleChannel->getSaleChannel()->getCode();
                $datasets[$codeSaleChannel] =  [
                    'label' => $productSaleChannel->getSaleChannel()->getName(),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'data' => [],
                 ];
             
                foreach ($productSaleChannel->getProductSaleChannelHistories() as $productSaleChannelHistory) {
                    if (count($datasets[$codeSaleChannel]['data'])>0) {
                        $lastEntry = $datasets[$codeSaleChannel]['data'][array_key_last($datasets[$codeSaleChannel]['data'])];
                        $datenew = clone($productSaleChannelHistory->getCreatedAt());
                        $datenew->sub(new DateInterval('PT1M'));
                        $datasets[$codeSaleChannel]['data'][] = [
                            'y'=> $lastEntry['y'],
                            'x'=> $datenew->format('Y-m-d H:i'),
                            "label" => $lastEntry['label'],
                            'currency' => $currency
                        ];
                    }
                
                    $datasets[$codeSaleChannel]['data'][] = [
                        'y'=>$productSaleChannelHistory->getPrice(),
                        'x'=> $productSaleChannelHistory->getCreatedAt()->format('Y-m-d H:i'),
                        "label" => $productSaleChannelHistory->getFullDescription(),
                        'currency' => $currency
                    ];
                    if ($productSaleChannelHistory->getPrice() > $biggerPrice) {
                        $biggerPrice = $productSaleChannelHistory->getPrice();
                    }
                }
            
                $lastEntry = $datasets[$codeSaleChannel]['data'][array_key_last($datasets[$codeSaleChannel]['data'])];
                $datasets[$codeSaleChannel]['data'][] = [
                    'y'=>$lastEntry['y'],
                    'x'=> $dateNow->format('Y-m-d H:i'),
                    "label" => $lastEntry['label'],
                    'currency' => $currency
                ];
            }
        }

        $chart->setData([
            'labels' => $labels,
            'datasets' => array_values($datasets),
        ]);

            

        $chart->setOptions(
            [
            'plugins' => [
                    'title' => [
                        "display" => true,
                        'text' => 'Prices records per marketplace'
                    ],
                ],
            'scales' => [
                'y' => [
                    'min' => 0,
                    'suggestedMax' => 1.2*$biggerPrice,
                ],
                'x' => [
                        'type' => 'time',
                        "title" => [
                            "display" => true,
                            "text" => 'Date'
                        ],
                        'time' => [
                            "unit" =>'day'
                        ],
                    ],
                ],
            ],
        );

        return $chart;
    }
}
