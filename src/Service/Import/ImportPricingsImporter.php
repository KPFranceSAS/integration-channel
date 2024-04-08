<?php

namespace App\Service\Import;

use App\Entity\ImportPricing;
use App\Entity\Product;
use App\Entity\ProductSaleChannel;
use App\Entity\Promotion;
use App\Entity\SaleChannel;
use App\Entity\User;
use App\Helper\MailService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use function Symfony\Component\String\u;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;

class ImportPricingsImporter
{
    private $manager;


    public function __construct(
        private readonly MailService $mailService,
        ManagerRegistry $manager,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
        private readonly Environment $twig,
        private readonly TokenStorageInterface $tokenStorage
    ) {
        $this->manager = $manager->getManager();
    }




    public function importImportPricings()
    {
        /** @var array[\App\Entity\ImportPricing] */
        $importPricings = $this->manager
            ->getRepository(ImportPricing::class)
            ->findBy([
                'status' => ImportPricing::Status_ToImport,
            ]);
        foreach ($importPricings as $importPricing) {
            $this->setUser($importPricing->getUser());
            $importPricing->setStatus(ImportPricing::Status_Importing);
            $this->manager->flush();
            $this->importLines($importPricing);
            $this->tokenStorage->setToken(null);
        }
    }

    public function addLog(ImportPricing $importPricing, $log)
    {
        $this->logger->info($log);
        $importPricing->addLog($log);
    }


    public function addWarning(ImportPricing $importPricing, $log)
    {
        $this->logger->warning($log);
        $importPricing->addWarning($log);
    }

    public function addSuccess(ImportPricing $importPricing, $log)
    {
        $this->logger->emergency($log);
        $importPricing->addSuccess($log);
    }

    public function addError(ImportPricing $importPricing, $log)
    {
        $this->logger->critical($log);
        $importPricing->addError($log);
    }

    public function setUser(User $user)
    {
        $token = new UsernamePasswordToken(
            $user,
            $user->getEmail(),
            $user->getRoles()
        );

        $this->tokenStorage->setToken($token);
    }



    private function importLinePricing(ImportPricing $importPricing, array $line, int $lineNumber)
    {
        if (!array_key_exists('sku', $line)) {
            $this->addError($importPricing, 'Column Sku is required');
            return false;
        }

        $productDb = $this->manager->getRepository(Product::class)->findOneBy(['sku' => $line["sku"]]);
        if (!$productDb) {
            $this->addError($importPricing, 'No product with sku ' . $line["sku"]. ' on line '.$lineNumber);
            return false;
        } else {
            $this->addLog($importPricing, 'Find sale code with sale channel ' . $line["sku"]. ' on line '.$lineNumber);
        }

        $saleChannelDbs = $this->getSaleChannelsPricing();

      
        $attributes= ['enabled', 'price', 'enabledFbm'];
        foreach ($line as $column=> $value) {
            foreach ($attributes as $attribute) {
                if (u($column)->endsWith('-'.$attribute) && strlen((string) $value)> 0) {
                    $channelCode = str_replace('-'.$attribute, '', $column);

                    if (!array_key_exists($channelCode, $saleChannelDbs)) {
                        $this->addError($importPricing, 'The sale channel '.$channelCode." doesn't exists");
                        return false;
                    }
                    if (!$importPricing->getUser()->hasSaleChannel($saleChannelDbs[$channelCode])) {
                        $this->addError($importPricing, "You cannot import pricing for this sale channel ".$channelCode);
                        return false;
                    }
                }
            }
        }



        foreach ($line as $column=> $value) {
            foreach ($attributes as $attribute) {
                if (u($column)->endsWith('-'.$attribute) && strlen((string) $value)> 0) {
                    $channelCode = str_replace('-'.$attribute, '', $column);

                    $productSaleChannel =  $productDb->getProductSaleChannelByCode($channelCode);
                    if ($productSaleChannel) {
                        $valueFormatted = $attribute == 'price' ? floatval(str_replace(',', '.', (string) $value)) : (bool)$value;
                        $productSaleChannel->{'set'.ucfirst($attribute)}($valueFormatted);
                        $this->addLog($importPricing, 'Put on channel '.$channelCode." ".$column." to value ".$value);
                    } else {
                        $this->addError($importPricing, 'The sale channel '.$channelCode." doesn't link to any product");
                    }
                }
            }
        }


        $errors = $this->validator->validate($productDb);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addError($importPricing, 'The product '.$productDb.' has some issues on ' . $error->getPropertyPath() . ' > ' . $error->getMessage() . ' on line ' . $lineNumber);
            }
            $this->manager->detach($productDb);
            foreach ($productDb->getProductSaleChannels() as $productSaleChannel) {
                foreach ($productSaleChannel->getPromotions() as $promotion) {
                    $this->manager->detach($promotion);
                }
                $this->manager->detach($productSaleChannel);
            }
            return false;
        }
        return true;
    }





    private $saleChannelDbArrays;

    


    public function getSaleChannelsPricing()
    {
        if (!$this->saleChannelDbArrays) {
            $this->saleChannelDbArrays= [];
        
            $saleChannelDbs = $this->manager->getRepository(SaleChannel::class)->findAll();
            foreach ($saleChannelDbs as $saleChannelDb) {
                $this->saleChannelDbArrays[$saleChannelDb->getCode()] = $saleChannelDb;
            }
        }
        return $this->saleChannelDbArrays;
    }
   


    public function importLines(ImportPricing $importPricing)
    {
        $i = 1;
        $created = 0;
        $notCreated = 0;
        $contentLines = $importPricing->getContent();
        foreach ($contentLines as $contentLine) {
            $this->addLog($importPricing, '######################################################');
            $this->addLog($importPricing, 'Processing line ' . $i);
            if ($importPricing->getImportType()==ImportPricing::Type_Import_Promotion) {
                $importLineOk = $this->importLinePromotion($importPricing, $contentLine, $i);
            } else {
                $importLineOk = $this->importLinePricing($importPricing, $contentLine, $i);
            }

            if ($importLineOk) {
                $created++;
                $this->addSuccess($importPricing, 'Items updated on line ' . $i);
            } else {
                $notCreated++;
                $this->addError($importPricing, 'Items skipped on line ' . $i);
            }
            $this->manager->flush();
            $i++;
        }
        $this->addLog($importPricing, '-------------------');
        $this->addLog($importPricing, "Result import on :" . count($contentLines) . " lines");
        $this->addLog($importPricing, "$created lines succeeded");
        $this->addLog($importPricing, "$notCreated lines skipped");
        $importPricing->setStatus(ImportPricing::Status_Imported);
        $this->manager->flush();
        $this->sendReport($importPricing);
    }


    

    private function importLinePromotion(ImportPricing $importPricing, array $line, int $lineNumber)
    {
        $promotions = $this->transformLineInArrayPromotion($importPricing, $line, $lineNumber);
        if (count($promotions)>0) {
            $this->addLog($importPricing, count($promotions).' promotions found');
            $errorPrences = false;
            

            foreach ($promotions as $promotion) {
                $productMarketplace = $promotion->getProductSaleChannel();
                $errors = $this->validator->validate($productMarketplace);
                if (count($errors) > 0) {
                    foreach ($errors as $error) {
                        $this->addError($importPricing, 'Property : ' . $error->getPropertyPath() . ' > ' . $error->getMessage() . ' on line ' . $lineNumber);
                    }
                    $errorPrences = true;
                }
            }
            

            if ($errorPrences) {
                foreach ($promotions as $promotion) {
                    $productMarketplace = $promotion->getProductSaleChannel();
                    $productMarketplace->removePromotion($promotion);
                }
                return false;
            } else {
                $this->addLog($importPricing, 'Promotion data go through validation');
                return true;
            }
        } else {
            $this->addError($importPricing, 'Promotions were not valid on line '.$lineNumber);
            return false;
        }
    }

    private function transformLineInArrayPromotion(ImportPricing $importPricing, array $line, int $lineNumber):array
    {
        $promotions = [];
        if (!$this->checkIfNecessaryColumnPromotion($importPricing, $line, $lineNumber)) {
            return $promotions;
        } else {
            $this->addLog($importPricing, 'Mandatory column ok ');
        }

        $products = $this->getProductsPromotions($importPricing, $line, $lineNumber);
        if (count($products)==0) {
            return $promotions;
        } else {
            $this->addLog($importPricing, 'Found '.count($products).' products');
        }

        $marketplaces = $this->getSaleChannelsPromotions($importPricing, $line, $lineNumber);
        if (count($marketplaces)==0) {
            return $promotions;
        } else {
            $this->addLog($importPricing, 'Found '.count($marketplaces).' sale channels');
        }

        $beginDate = strlen((string) $line['beginDate']) == 10 ? DateTime::createFromFormat('Y-m-d H:i', $line['beginDate'].' 00:00') : DateTime::createFromFormat('Y-m-d H:i', $line['beginDate']);
        if (!$beginDate) {
            $this->addError($importPricing, 'Begin date '.$line['beginDate'].' is incorrect  on line '.$lineNumber);
            return $promotions;
        } else {
            $this->addLog($importPricing, 'Begin date '.$beginDate->format('d-m-Y H:i'));
        }

        if (!in_array($line['type'], Promotion::TYPES)) {
            $this->addError($importPricing, 'Promotion type date '.$line['type'].' is incorrect on line '.$lineNumber);
            return $promotions;
        } else {
            $this->addLog($importPricing, 'Promotion Type '.$line['type']);
        }

        $endDate = strlen((string) $line['endDate']) == 10 ? DateTime::createFromFormat('Y-m-d H:i', $line['endDate'].' 23:59') : DateTime::createFromFormat('Y-m-d H:i', $line['endDate']);
        if (!$endDate) {
            $this->addError($importPricing, 'End date '.$line['endDate'].' is incorrect  on line '.$lineNumber);
            return $promotions;
        } else {
            $this->addLog($importPricing, 'End date '. $endDate->format('d-m-Y H:i'));
        }


        if (!in_array($line['frequency'], Promotion::FREQUENCIES)) {
            $this->addError($importPricing, 'Promotion frequency date '.$line['frequency'].' is incorrect on line '.$lineNumber);
            return $promotions;
        } else {
            $this->addLog($importPricing, 'Promotion frequency '.$line['frequency']);
        }


        if ($line['frequency'] == Promotion::FREQUENCY_TIMETOTIME) {
            if (!array_key_exists('beginHour', $line)) {
                $this->addError($importPricing, 'Column beginHour  is missing  on line '.$lineNumber);
            } else {
                $beginHour = DateTime::createFromFormat('H:i', $line['beginHour']);
                if (!$beginHour) {
                    $this->addError($importPricing, 'Begin hour '.$line['beginHour'].' is incorrect  on line '.$lineNumber);
                    return $promotions;
                } else {
                    $this->addLog($importPricing, 'Begin hour '. $beginHour->format('H:i'));
                }
            }

            if (!array_key_exists('endHour', $line)) {
                $this->addError($importPricing, 'Column endHour  is missing  on line '.$lineNumber);
            } else {
                $endHour = DateTime::createFromFormat('H:i', $line['endHour']);
                if (!$endHour) {
                    $this->addError($importPricing, 'End hour '.$line['endHour'].' is incorrect  on line '.$lineNumber);
                    return $promotions;
                } else {
                    $this->addLog($importPricing, 'End hour '. $endHour->format('H:i'));
                }
            }

            if (!array_key_exists('weekDays', $line) || strlen((string) $line['weekDays']) == 0) {
                $this->addError($importPricing, 'Column weekDays  is missing  on line '.$lineNumber);
            } else {
                $weekDaysValue = explode(',', (string) $line['weekDays']);
                $weekDays = [];
                foreach ($weekDaysValue as $weekDayValue) {
                    if (in_array((int)$weekDayValue, range(1, 7))) {
                        $weekDays[] = $weekDayValue;
                    }
                }
                $weekDays = array_unique($weekDays);

                if (count($weekDays)== 0) {
                    $this->addError($importPricing, 'weekDays'.$line['weekDays'].' is incorrect  on line '.$lineNumber);
                    return $promotions;
                } else {
                    $this->addLog($importPricing, 'End hour '. $endHour->format('H:i'));
                }
            }
        }




        foreach ($products as $product) {
            foreach ($marketplaces as $marketplace) {
                /**@var ProductSaleChannel */
                $productMarketplace = $this->manager->getRepository(ProductSaleChannel::class)->findOneBy([
                    'saleChannel' => $marketplace,
                    'product'=> $product
                ]);

                $promotion = new Promotion();
                $productMarketplace->addPromotion($promotion);
                $promotion->setBeginDate($beginDate);
                $promotion->setEndDate($endDate);
                $promotion->setDiscountType($line['type']);
                if ($line['type']==Promotion::TYPE_PERCENT) {
                    $promotion->setPercentageAmount(floatval($line['amount']));
                } else {
                    $promotion->setFixedAmount(floatval($line['amount']));
                }
                if (array_key_exists('comment', $line)) {
                    $promotion->setComment($line['comment']);
                }

                $promotion->setFrequency($line['frequency']);
                if ($line['frequency']==Promotion::FREQUENCY_TIMETOTIME) {
                    $promotion->setBeginHour($beginHour);
                    $promotion->setEndHour($endHour);
                    $promotion->setWeekDays($weekDays);
                }
                if (array_key_exists('comment', $line)) {
                    $promotion->setComment($line['comment']);
                }

                if (array_key_exists('priority', $line)) {
                    $promotion->setPriority((int)$line['priority']);
                }

                $promotions[]=$promotion;
            }
        }


        return $promotions;
    }


    private function checkIfNecessaryColumnPromotion(ImportPricing $importPricing, array $line, int $lineNumber)
    {
        $required = [
            'skus',
            'beginDate',
            'endDate',
            'saleChannels',
            'type',
            'amount',
            'frequency',
        ];

        $lineOk = true;

        foreach ($required as $req) {
            if (!array_key_exists($req, $line)) {
                $this->addError($importPricing, 'Missing required column '.$req.' on line '.$lineNumber);
                $lineOk=false;
            } else {
                if (strlen((string) $line[$req])==0) {
                    $this->addError($importPricing, 'Missing required column '.$req.' on line '.$lineNumber);
                    $lineOk=false;
                }
            }
        }

        return $lineOk;
    }


    private function getSaleChannelsPromotions(ImportPricing $importPricing, array $line, int $lineNumber): array
    {
        $saleChannels = explode(",", (string) $line['saleChannels']);
        $saleChannelsDb = [];
        foreach ($saleChannels as $saleChannel) {
            /** @var \App\Entity\SaleChannel */
            $saleChannelDb = $this->manager->getRepository(SaleChannel::class)->findOneBy(['code'=>$saleChannel]);
            if (!$saleChannelDb) {
                $this->addError($importPricing, 'No sale channel with code ' . $saleChannel. ' on line '.$lineNumber);
                return [];
            } else {
                $this->addLog($importPricing, 'Find sale code with sale channel ' . $saleChannel. ' on line '.$lineNumber);
                
                if (!$importPricing->getUser()->hasSaleChannel($saleChannelDb)) {
                    $this->addError($importPricing, "You cannot import pricing for this sale channel ".$saleChannel);
                } else {
                    $saleChannelsDb[]=$saleChannelDb;
                }
            }
        }
        
        return $saleChannelsDb;
    }


    private function getProductsPromotions(ImportPricing $importPricing, array $line, int $lineNumber): array
    {
        $skus = explode(",", (string) $line['skus']);
        $productsDb = [];
        foreach ($skus as $sku) {
            $productDb = $this->manager->getRepository(Product::class)->findOneBy(['sku' => $sku]);
            if (!$productDb) {
                $this->addError($importPricing, 'No product with sku ' . $sku. ' on line '.$lineNumber);
                return [];
            } else {
                $this->addLog($importPricing, 'Find sale code with sale channel ' . $sku. ' on line '.$lineNumber);
                $productsDb[]=$productDb;
            }
        }
        return $productsDb;
    }


    protected function sendReport(ImportPricing $importPricing)
    {
        $contenu = $this->twig->render('email/reportImport.html.twig', [
            'job' => $importPricing,
        ]);

        $newTitre = '[Paxtira] Your import is finished';
        $this->mailService->sendEmail($newTitre, $contenu, $importPricing->getUser()->getEmail());
    }
}
