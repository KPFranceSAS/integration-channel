<?php

namespace App\Command\Pricing;

use App\Service\Import\ImportPricingsImporter;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[\Symfony\Component\Console\Attribute\AsCommand('app:clean-product-sale-channels', 'Clean product-sale-channels')]
class CleanProductSaleChannelCommand extends Command
{
    private $manager;

    public function __construct(ManagerRegistry $manager)
    {
         /** @var \Doctrine\ORM\EntityManagerInterface */
        $this->manager = $manager->getManager();
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchSize = 50;
        $i = 0;
        $j = 0;
        $output->writeln("Sanitize price");
        $q = $this->manager->createQuery('select ps from App\Entity\ProductSaleChannel ps where ps.price is not null');
        foreach ($q->toIterable() as $promo) {

            if($promo->getProductPrice() && $promo->getPrice() >= $promo->getProductPrice()){
                $promo->setPrice(null);
                $promo->setOverridePrice(false);
                $j++;
            }


            if (($i % $batchSize) === 0) {
                $output->writeln("Saved  $i /  $j Sanitized ");
                $this->manager->flush();
                $this->manager->clear();
            }
            $i++;
        }
        $output->writeln("Change  $j on $i prize");
        $this->manager->flush();
        $this->manager->clear();

        return Command::SUCCESS;
    }
}
