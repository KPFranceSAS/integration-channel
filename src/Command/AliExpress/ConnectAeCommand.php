<?php

namespace App\Command\AliExpress;

use App\Service\AliExpress\AliExpressApi;
use App\Service\AliExpress\AliExpressIntegrateOrder;
use App\Service\BusinessCentral\GadgetIberiaConnector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectAeCommand extends Command
{
    protected static $defaultName = 'app:ae-test';
    protected static $defaultDescription = 'Connection to Ali express';

    public function __construct(AliExpressApi $aliExpress, AliExpressIntegrateOrder $aliExpressIntegrateOrder, GadgetIberiaConnector $gadgetIberiaConnector)
    {
        $this->aliExpress = $aliExpress;
        $this->aliExpressIntegrateOrder = $aliExpressIntegrateOrder;
        $this->gadgetIberiaConnector = $gadgetIberiaConnector;
        parent::__construct();
    }

    private $aliExpress;

    private $gadgetIberiaConnector;

    private $aliExpressIntegrateOrder;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $result = $this->aliExpress->markOrderAsFulfill("8147638740916599", "SPAIN_LOCAL_DHL", "0837593990");
        var_dump($result);

        //------------------------------------
        //>>> Sending invoice 3016090030659243
        //------------------------------------
        // Invoice found by reference to the order number WPV21-00245
        // Tracking number is not retrieved from DHL GALV22/000657
        // Check on DHL API :GALV22/000657
        // Any tracking found for invoice GFV22/0300303
        // Check if late GFV22/0300303 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 8147638740916599
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00246
        // Tracking number is not retrieved from DHL GALV22/000632
        // Check on DHL API :GALV22/000632
        // Any tracking found for invoice GFV22/0300278
        // Check if late GFV22/0300278 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3015980345905895
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00247
        // Tracking number is not retrieved from DHL GALV22/000654
        // Check on DHL API :GALV22/000654
        // Any tracking found for invoice GFV22/0300300
        // Check if late GFV22/0300300 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3015981946086296
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00248
        // Tracking number is not retrieved from DHL GALV22/000633
        // Check on DHL API :GALV22/000633
        // Any tracking found for invoice GFV22/0300279
        // Check if late GFV22/0300279 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3015983143531833
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00249
        // Tracking number is not retrieved from DHL GALV22/000655
        // Check on DHL API :GALV22/000655
        // Any tracking found for invoice GFV22/0300301
        // Check if late GFV22/0300301 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016074289154337
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00250
        // Tracking number is not retrieved from DHL GALV22/000634
        // Check on DHL API :GALV22/000634
        // Any tracking found for invoice GFV22/0300280
        // Check if late GFV22/0300280 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016088780931950
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00251
        // Tracking number is not retrieved from DHL GALV22/000635
        // Check on DHL API :GALV22/000635
        // Any tracking found for invoice GFV22/0300281
        // Check if late GFV22/0300281 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016072217542727
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00252
        // Tracking number is not retrieved from DHL GALV22/000646
        // Check on DHL API :GALV22/000646
        // Any tracking found for invoice GFV22/0300292
        // Check if late GFV22/0300292 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016079083747367
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00253
        // Tracking number is not retrieved from DHL GALV22/000656
        // Check on DHL API :GALV22/000656
        // Any tracking found for invoice GFV22/0300302
        // Check if late GFV22/0300302 >> 2022-03-21

        // 
        // ------------------------------------
        // >>> Sending invoice 3016075097786716
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00254
        // Tracking number is not retrieved from DHL GALV22/000636
        // Check on DHL API :GALV22/000636
        // Any tracking found for invoice GFV22/0300282
        // Check if late GFV22/0300282 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016201036370578
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00255
        // Tracking number is not retrieved from DHL GALV22/000637
        // Check on DHL API :GALV22/000637
        // Any tracking found for invoice GFV22/0300283
        // Check if late GFV22/0300283 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016078131043960
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00256
        // Tracking number is not retrieved from DHL GALV22/000639
        // Check on DHL API :GALV22/000639
        // Any tracking found for invoice GFV22/0300285
        // Check if late GFV22/0300285 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016076059088912
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00257
        // Tracking number is not retrieved from DHL GALV22/000640
        // Check on DHL API :GALV22/000640
        // Any tracking found for invoice GFV22/0300286
        // Check if late GFV22/0300286 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016076299180594
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00258
        // Tracking number is not retrieved from DHL GALV22/000641
        // Check on DHL API :GALV22/000641
        // Any tracking found for invoice GFV22/0300287
        // Check if late GFV22/0300287 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016201439664069
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00259
        // Tracking number is not retrieved from DHL GALV22/000642
        // Check on DHL API :GALV22/000642
        // Any tracking found for invoice GFV22/0300288
        // Check if late GFV22/0300288 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016079492082373
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00260
        // Tracking number is not retrieved from DHL GALV22/000658
        // Check on DHL API :GALV22/000658
        // Any tracking found for invoice GFV22/0300304
        // Check if late GFV22/0300304 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 8147667313415597
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00261
        // Tracking number is not retrieved from DHL GALV22/000659
        // Check on DHL API :GALV22/000659
        // Any tracking found for invoice GFV22/0300305
        // Check if late GFV22/0300305 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016080051125840
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00262
        // Tracking number is not retrieved from DHL GALV22/000643
        // Check on DHL API :GALV22/000643
        // Any tracking found for invoice GFV22/0300289
        // Check if late GFV22/0300289 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016013851697961
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00263
        // Tracking number is not retrieved from DHL GALV22/000644
        // Check on DHL API :GALV22/000644
        // Any tracking found for invoice GFV22/0300290
        // Check if late GFV22/0300290 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016094463687698
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00264
        // Tracking number is not retrieved from DHL GALV22/000645
        // Check on DHL API :GALV22/000645
        // Any tracking found for invoice GFV22/0300291
        // Check if late GFV22/0300291 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016017051489303
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00265
        // Tracking number is not retrieved from DHL GALV22/000647
        // Check on DHL API :GALV22/000647
        // Any tracking found for invoice GFV22/0300293
        // Check if late GFV22/0300293 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016085961273859
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00266
        // Tracking number is not retrieved from DHL GALV22/000648
        // Check on DHL API :GALV22/000648
        // Any tracking found for invoice GFV22/0300294
        // Check if late GFV22/0300294 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 8147680676111147
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00267
        // Tracking number is not retrieved from DHL GALV22/000649
        // Check on DHL API :GALV22/000649
        // Any tracking found for invoice GFV22/0300295
        // Check if late GFV22/0300295 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3015999860063524
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00268
        // Tracking number is not retrieved from DHL GALV22/000650
        // Check on DHL API :GALV22/000650
        // Any tracking found for invoice GFV22/0300296
        // Check if late GFV22/0300296 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016089321857917
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00269
        // Tracking number is not retrieved from DHL GALV22/000660
        // Check on DHL API :GALV22/000660
        // Any tracking found for invoice GFV22/0300306
        // Check if late GFV22/0300306 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016084219011596
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00270
        // Tracking number is not retrieved from DHL GALV22/000651
        // Check on DHL API :GALV22/000651
        // Any tracking found for invoice GFV22/0300297
        // Check if late GFV22/0300297 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016097985466761
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00271
        // Tracking number is not retrieved from DHL GALV22/000652
        // Check on DHL API :GALV22/000652
        // Any tracking found for invoice GFV22/0300298
        // Check if late GFV22/0300298 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016000108765990
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00272
        // Tracking number is not retrieved from DHL GALV22/000631
        // Check on DHL API :GALV22/000631
        // Any tracking found for invoice GFV22/0300277
        // Check if late GFV22/0300277 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016002664626351
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00273
        // Tracking number is not retrieved from DHL GALV22/000661
        // Check on DHL API :GALV22/000661
        // Any tracking found for invoice GFV22/0300307
        // Check if late GFV22/0300307 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016089736671620
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00274
        // Tracking number is not retrieved from DHL GALV22/000662
        // Check on DHL API :GALV22/000662
        // Any tracking found for invoice GFV22/0300308
        // Check if late GFV22/0300308 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016112752200060
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00275
        // Tracking number is not retrieved from DHL GALV22/000663
        // Check on DHL API :GALV22/000663
        // Any tracking found for invoice GFV22/0300309
        // Check if late GFV22/0300309 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 8147899501066253
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00276
        // Tracking number is not retrieved from DHL GALV22/000664
        // Check on DHL API :GALV22/000664
        // Any tracking found for invoice GFV22/0300310
        // Check if late GFV22/0300310 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 8147718102830262
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00277
        // Tracking number is not retrieved from DHL GALV22/000670
        // Check on DHL API :GALV22/000670
        // Any tracking found for invoice GFV22/0300315
        // Check if late GFV22/0300315 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016028173005293
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00278
        // Tracking number is not retrieved from DHL GALV22/000666
        // Check on DHL API :GALV22/000666
        // Any tracking found for invoice GFV22/0300311
        // Check if late GFV22/0300311 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 8147612016920262
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00279
        // Tracking number is not retrieved from DHL GALV22/000667
        // Check on DHL API :GALV22/000667
        // Any tracking found for invoice GFV22/0300312
        // Check if late GFV22/0300312 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016118273452930
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00280
        // Tracking number is not retrieved from DHL GALV22/000653
        // Check on DHL API :GALV22/000653
        // Any tracking found for invoice GFV22/0300299
        // Check if late GFV22/0300299 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016109349236384
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00281
        // Tracking number is not retrieved from DHL GALV22/000668
        // Check on DHL API :GALV22/000668
        // Any tracking found for invoice GFV22/0300313
        // Check if late GFV22/0300313 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 3016119877226772
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00282
        // Tracking number is not retrieved from DHL GALV22/000669
        // Check on DHL API :GALV22/000669
        // Any tracking found for invoice GFV22/0300314
        // Check if late GFV22/0300314 >> 2022-03-21
        // 
        // ------------------------------------
        // >>> Sending invoice 8147632242770542
        // ------------------------------------
        // Invoice found by reference to the order number WPV21-00283
        // Tracking number is not retrieved from DHL GALV22/000671
        // Check on DHL API :GALV22/000671
        // Any tracking found for invoice GFV22/0300316
        // Check if late GFV22/0300316 >> 2022-03-21

        return Command::SUCCESS;
    }



    private function updateStockLevel()
    {
        $result = $this->aliExpress->updateStockLevel("1005001800940160", "X-PFJ4086EU", 1029);
        var_dump($result);
    }



    private function markCompanyTransport()
    {
        $order = $this->aliExpress->getOrder("3015645808691774");
        var_dump($order);

        /*$carriers = $this->aliExpress->getCarriers();
        foreach ($carriers as $carrier) {
            var_dump($carrier->service_name);
        }
        */



        $result = $this->aliExpress->markOrderAsFulfill("3015988148626826", "SPAIN_LOCAL_DHL", "0837590170");
        var_dump($result);
    }




    private function transformeOrder()
    {

        $order = $this->aliExpress->getOrder("8143448047401326");

        $transforme =  $this->aliExpressIntegrateOrder->transformToAnBcOrder($order);

        $orderIntegrate = $this->gadgetIberiaConnector->createSaleOrder($transforme->transformToArray());
        $orderIntegrate = $this->gadgetIberiaConnector->getFullSaleOrder($orderIntegrate['id']);
        var_dump($orderIntegrate);
        var_dump($orderIntegrate['totalAmountIncludingTax']);
    }
}
