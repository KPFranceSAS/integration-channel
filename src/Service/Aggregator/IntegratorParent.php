<?php

namespace App\Service\Aggregator;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Model\SaleOrder;
use App\BusinessCentral\Model\SaleOrderLine;
use App\BusinessCentral\ProductTaxFinder;
use App\Entity\IntegrationChannel;
use App\Entity\ProductCorrelation;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Helper\Traits\TraitServiceLog;
use App\Service\Aggregator\ApiAggregator;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

abstract class IntegratorParent
{
    use TraitServiceLog;
    protected $logger;

    protected $productTaxFinder;

    protected $manager;

    protected $errors;

    protected $mailer;

    protected $businessCentralAggregator;

    protected $apiAggregator;


    public function __construct(ProductTaxFinder $productTaxFinder, ManagerRegistry $manager, LoggerInterface $logger, MailService $mailer, BusinessCentralAggregator $businessCentralAggregator, ApiAggregator $apiAggregator)
    {
        $this->productTaxFinder = $productTaxFinder;
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->businessCentralAggregator = $businessCentralAggregator;
        $this->apiAggregator = $apiAggregator;
    }


    abstract public function transformToAnBcOrder($orderApi): SaleOrder;

    abstract public function getChannel();

    abstract public function getCompanyIntegration($orderApi);

    abstract public function getCustomerBC($orderApi);

    abstract protected function getOrderId($orderApi);



    public function getApi()
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }



    public function getBusinessCentralConnector($companyName): BusinessCentralConnector
    {
        return $this->businessCentralAggregator->getBusinessCentralConnector($companyName);
    }
    public function processOrders($reIntegrate = false)
    {
        try {
            $this->errors = [];

            if ($reIntegrate) {
                $this->logger->info('Start reintegrations ' . $this->getChannel());
                $this->reIntegrateAllOrders();
                $this->logger->info('Ended reintegrations ' . $this->getChannel());
            } else {
                $this->logger->info('Start integrations ' . $this->getChannel());
                $this->integrateAllOrders();
                $this->logger->info('Ended integration ' . $this->getChannel());
            }

            if (count($this->errors) > 0) {
                $messageError = implode('<br/><br/>', array_unique($this->errors));
                throw new Exception($messageError);
            }
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmailChannel($this->getChannel(), 'Order Integration - Error', $e->getMessage());
        }
    }


    public function reIntegrateAllOrders()
    {
        /** @var array[\App\Entity\WebOrder] */
        $ordersToReintegrate = $this->manager->getRepository(WebOrder::class)->findBy(
            [
                'status' => WebOrder::STATE_ERROR,
                "channel" => $this->getChannel()
            ]
        );
        $this->logger->info(count($ordersToReintegrate) . ' orders to re-integrate ' . $this->getChannel());
        foreach ($ordersToReintegrate as $orderToReintegrate) {
            $this->logLine('>>> Reintegration of order ' . $orderToReintegrate->getExternalNumber());
            $this->reIntegrateOrder($orderToReintegrate);
        }
    }


    public function integrateAllOrders()
    {
        $counter = 0;
        $ordersApi = $this->getApi()->getAllOrdersToSend();

        foreach ($ordersApi as $orderApi) {
            if ($this->integrateOrder($orderApi)) {
                $counter++;
                $this->logger->info("Orders integrated : $counter ");
            }
        }
    }



    public function integrateOrder($order)
    {
        $idOrder = $this->getOrderId($order);
        $company =  $this->getCompanyIntegration($order);
        $customer =  $this->getCustomerBC($order);


        $this->logLine(">>> Integration order marketplace " . $this->getChannel() . " $idOrder in the account $customer in BC instance $company");
        if ($this->checkToIntegrateToInvoice($order)) {
            $this->logger->info('To integrate ');

            try {
                $this->logger->info('Creation of webOrder entity ');
                $webOrder = WebOrder::createOneFrom($order, $this->getChannel());
                $this->logger->info('WebOrder entity created for '.$this->getChannel());
                $webOrder->setCompany($company);
                $this->manager->persist($webOrder);
                $this->checkAfterPersist($webOrder, $order);
                // creation of the order
                $this->addLogToOrder($webOrder, 'Order transformation to fit to ERP model');
                $orderBC = $this->transformToAnBcOrder($order);
                $this->addLogToOrder($webOrder, 'Order transformation adjustements prices regarding to taxes');
                $this->adjustSaleOrder($webOrder, $orderBC);
                
                $this->addLogToOrder($webOrder, 'Define best carriers');
                $this->defineBestCarrier($webOrder, $orderBC);
                
                $webOrder->setWarehouse($orderBC->locationCode);
                $webOrder->setCustomerNumber($orderBC->customerNumber);

                $businessCentralConnector = $this->businessCentralAggregator->getBusinessCentralConnector($webOrder->getCompany());
                $this->addLogToOrder($webOrder, 'Order creation in the ERP ' . $businessCentralConnector->getCompanyName());
                $webOrder->setStatus(WebOrder::STATE_SYNC_TO_ERP);
                // creation in Business central
                $erpOrder = $businessCentralConnector->createSaleOrder($orderBC->transformToArray());
                $this->addLogToOrder($webOrder, 'Order created in the ERP ' . $businessCentralConnector->getCompanyName() . ' with number ' . $erpOrder['number'].' and content '.json_encode($orderBC->transformToArray()));
                $webOrder->setStatus(WebOrder::STATE_SYNC_TO_ERP);
                $webOrder->setOrderErp($erpOrder['number']);
                if($webOrder->getFulfilledBy()==WebOrder::FULFILLED_BY_SELLER) {
                    $this->createReservationEntries($webOrder);
                }
                $this->addLogToOrder($webOrder, 'Integration finished ' . $erpOrder['number']);
                $this->checkAfterIntegration($webOrder, $order);
            } catch (Exception $e) {
                $message = mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");
                $webOrder->addError($message);
                $webOrder->setStatus(WebOrder::STATE_ERROR);
                $this->addError('Integration Problem ' . $idOrder . ' > ' . $message);
            }
            $this->manager->flush();
            $this->logger->info('Integration finished');
            return true;
        } else {
            $this->logger->info('No Integration');
            return false;
        }
    }


    protected function checkAfterPersist(WebOrder $order, $orderApi)
    {
    }

    protected function checkAfterIntegration(WebOrder $order, $orderApi)
    {
    }



    public function adjustSaleOrder(WebOrder $order, SaleOrder $saleOrder)
    {
        foreach ($saleOrder->salesLines as $keySaleLine => $saleLine) {
            if ($saleLine->lineType  == SaleOrderLine::TYPE_ITEM) {
                $this->logger->info('Have this product some canon digital');
                $addtitionalTax = $this->productTaxFinder->getAdditionalTaxes(
                    $saleLine->itemId,
                    $order->getCompany(),
                    $saleOrder->shippingPostalAddress->countryLetterCode,
                    $saleOrder->sellingPostalAddress->countryLetterCode
                );
                if ($addtitionalTax > 0) {
                    $newPrice = $saleLine->unitPrice - $addtitionalTax;
                    $this->logger->info('Remove additional tax amount ' . $addtitionalTax . ' Change product price ' . $newPrice);
                    $saleOrder->salesLines[$keySaleLine]->unitPrice = $newPrice;
                }
            }
        }
    }




    public function defineBestCarrier(WebOrder $order, SaleOrder $saleOrder)
    {
        if($order->isFulfiledBySeller()) {
            if($saleOrder->shippingAgent=='ARISE') {
                $order->setCarrierService(WebOrder::CARRIER_ARISE);
            } elseif($order->getChannel()==IntegrationChannel::CHANNEL_PAXUK) {
                $order->setCarrierService(WebOrder::CARRIER_DPDUK);
                $saleOrder->shippingAgent="DPD1";
                $saleOrder->shippingAgentService="DPD32";
                $saleOrder->locationCode=WebOrder::DEPOT_3PLUK;
            } else { // case DHL Parcel
                $order->setCarrierService(WebOrder::CARRIER_DHL);
                if(in_array($saleOrder->shippingPostalAddress->countryLetterCode, ['ES', 'PT'])) {
                    $saleOrder->shippingAgent="DHL PARCEL";
                    $saleOrder->shippingAgentService="DHL1";
                }
            }
        } else { // case Aamzon
            $saleOrder->shippingAgent = 'FBA';
            $saleOrder->shippingAgentService = '1';
            $saleOrder->locationCode = WebOrder::DEPOT_FBA_AMAZON;
            $order->setCarrierService(WebOrder::CARRIER_FBA);
        }
    }






    public function reIntegrateOrder(WebOrder $order)
    {
        try {
            $order->cleanErrors();
            $this->addLogToOrder($order, 'Attempt to new integration');

            $this->addLogToOrder($order, 'Order transformation to fit to ERP model');
            $orderApi = $order->getOrderContent();
            $orderBC = $this->transformToAnBcOrder($orderApi);
            $this->adjustSaleOrder($order, $orderBC);
            $order->setWarehouse($orderBC->locationCode);
            $this->addLogToOrder($order, 'Define best carriers');
            $this->defineBestCarrier($order, $orderBC);
            $order->setWarehouse($orderBC->locationCode);
            $order->setCustomerNumber($orderBC->customerNumber);

            $this->addLogToOrder($order, 'Order creation in the ERP');
            $erpOrder = $this->getBusinessCentralConnector($order->getCompany())->createSaleOrder($orderBC->transformToArray());
            $this->addLogToOrder($order, 'Order created in the ERP ' . $order->getCompany() . ' with number ' . $erpOrder['number']);

            $order->setStatus(WebOrder::STATE_SYNC_TO_ERP);
            $order->setOrderErp($erpOrder['number']);
            $this->addLogToOrder($order, 'Integration done ' . $erpOrder['number']);
            
            if($order->getFulfilledBy()==WebOrder::FULFILLED_BY_SELLER) {
                $this->createReservationEntries($order);
            }

            $this->checkAfterIntegration($order, $orderApi);
        } catch (Exception $e) {
            $message =  mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");
            $order->addError($message);
            $order->setStatus(WebOrder::STATE_ERROR);
            $this->addError('Reintegration Problem ' . $order->getExternalNumber() . ' > ' . $message);
            $this->addError($message);
        }
        $this->manager->flush();
        $this->logger->info('Reintegration finished');
        return true;
    }




    public function createReservationEntries(WebOrder $orderDb)
    {
        try {
          
            $this->addLogToOrder($orderDb, 'Add reservation to sale order lines');
            $connector = $this->businessCentralAggregator->getBusinessCentralConnector($orderDb->getCompany());
            $orderBc = $connector->getFullSaleOrderByNumber($orderDb->getOrderErp());

            foreach($orderBc['salesOrderLines'] as $saleOrderLine) {
                if($saleOrderLine['lineType'] == SaleOrderLine::TYPE_ITEM) {
                    $reservation = [
                        "QuantityBase" => $saleOrderLine['quantity'],
                        "CreationDate" => date('Y-m-d'),
                        "ItemNo" => $saleOrderLine['lineDetails']['number'],
                        "LocationCode" =>  $orderBc['locationCode'],
                        "SourceID" => $orderDb->getOrderErp(),
                        "SourceRefNo"=> $saleOrderLine['sequence'],
                    ];
                    $connector->createReservation($reservation);

                    $this->addLogToOrder($orderDb, 'Add reservation for line '.$saleOrderLine['sequence'].' for '.$saleOrderLine['quantity'].' '.$saleOrderLine['lineDetails']['number']);
                }
            }
        } catch(Exception $e) {
            $message = mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");
            $$this->addError($orderDb, 'Error during reservation creation in BC '.$message);
        }
    }




    protected function checkToIntegrateToInvoice($order): bool
    {
        $idOrder = $this->getOrderId($order);
        $company = $this->getCompanyIntegration($order);
        $customer = $this->getCustomerBC($order);
        if ($this->isAlreadyRecordedDatabase($idOrder)) {
            $this->logger->info('Is Already Recorded Database');
            return false;
        }
        if ($this->alreadyIntegratedErp($idOrder, $company, $customer)) {
            $this->logger->info('Is Already Recorded on ERP');
            return false;
        }
        return true;
    }


    protected function isAlreadyRecordedDatabase(string $idOrderApi): bool
    {
        $orderRecorded = $this->manager->getRepository(WebOrder::class)->findBy(
            [
                'externalNumber' => $idOrderApi,
                'channel' => $this->getChannel()
            ]
        );
        return count($orderRecorded) > 0;
    }


    protected function alreadyIntegratedErp(string $idOrderApi, string $company, string $customer): bool
    {
        return $this->checkIfInvoice($idOrderApi, $company, $customer) || $this->checkIfOrder($idOrderApi, $company, $customer);
    }



    protected function checkIfOrder(string $idOrderApi, string $company, string $customer): bool
    {
        $this->logger->info('Check order in BC ' . $idOrderApi . ' in the account ' . $customer . ' in the instance ' . $company);
        $saleOrder = $this->getBusinessCentralConnector($company)->getSaleOrderByExternalNumberAndCustomer($idOrderApi, $customer);
        return $saleOrder != null;
    }



    protected function checkIfInvoice(string $idOrderApi, string $company, string $customer): bool
    {
        $this->logger->info('Check invoice in BC ' . $idOrderApi . ' in the account ' . $customer . ' in the instance ' . $company);
        $saleOrder = $this->getBusinessCentralConnector($company)->getSaleInvoiceByExternalDocumentNumberCustomer($idOrderApi, $customer);
        return $saleOrder != null;
    }





    protected function getProductCorrelationSku(string $sku, string $company): string
    {
        $skuSanitized = strtoupper($sku);
        $productCorrelation = $this->manager->getRepository(ProductCorrelation::class)->findOneBy(['skuUsed' => $skuSanitized]);
        $skuFinal = $productCorrelation ? $productCorrelation->getSkuErpBc() : $skuSanitized;

        $product = $this->getBusinessCentralConnector($company)->getItemByNumber($skuFinal);
        if (!$product) {
            throw new Exception("Product with Sku $skuFinal cannot be found in business central $company. Create a Sku mapping");
        } else {
            return  $product['id'];
        }
    }




    public function simplifyAddress($adress)
    {
        $simplificationAddress = [
            "APARTAMENTO" => "APTO",
            "AVENIDA" => "AVDA",
            "AVENUE" => "AVE",
            "AVINGUDA" => "AVDA",
            "BARRIO" => "BO",
            "BAJO" => "BJ",
            "BLOQUE" => "BL",
            "BOULEVARD" => "BLVD",
            "CALLE" => "C/",
            "CARRER" => "C/",
            "CAMINITO" => "CMT",
            "CAMINO" => "CAM",
            "CAMI" => "CAM",
            "CARRETERA" => "CTRA",
            "CERRADA" => "CER",
            "CIRCULO" => "CIR",
            "CIUDAD" => "CDAD",
            "CHEMIN" => "CHE",
            "DERECHA" => "DCHA",
            "EDIFICIO" => "EDIF",
            "ENTRADA" => "ENT",
            "ESCALERA" => "ESC",
            "ESCALIER" => "ESC",
            "IZQUIERDA" => "IZDA",
            "IMMEUBLE" => "IMB",
            "IMPASSE" => "IMP",
            "NUMBER" => "No",
            "NUMERO" => "No",
            "NúMERO" => "No",
            "PASEO" => "PSO",
            "PASSAGE" => "PAS",
            "PISO" => "PS",
            "PLACITA" => "PLA",
            "PLANTA" => "PLTA",
            "PLACE" => "PL",
            "PLAZA" => "PZA",
            "POBLACIóN" => "POBL",
            "POBLACION" => "POBL",
            "PORTAL" => "POR",
            "PUERTO" => "PTO",
            "PUERTA" => "PTA",
            "PRESIDENTE" => "PDTE",
            "ROUTE" => "RTE",
            "TRAVERSíA" => "TRVA",
            "TRAVERSIA" => "TRVA",
            "URBANIZACION" => "URB",
            "URBANIZACIóN" => "URB",
            "VILLAGE" => "VLGE",
        ];

        $keysTofind = [];
        $simplificationAddressKeys = array_keys($simplificationAddress);
        foreach ($simplificationAddressKeys as $simplificationAddressKey) {
            $keysTofind[] = "/\b" . $simplificationAddressKey . "\b/";
        }

        $adress = strtoupper($adress);
        $adress = preg_replace($keysTofind, array_values($simplificationAddress), $adress);

        return ucwords(strtolower($adress));
    }
}
