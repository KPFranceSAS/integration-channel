<?php

namespace App\Command\Pricing;

use App\Service\Import\ImportPricingsImporter;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[\Symfony\Component\Console\Attribute\AsCommand('app:clean-promotions', 'Clean promotions')]
class CleanPromotionsCommand extends Command
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
        $date =new DateTime();
        $date->sub(new DateInterval('P30D'));
        $dateEndLitt = $date->format('Y-m-d H:i:s');
        $batchSize = 50;
        $i = 0;
        $output->writeln("Remove all promos with end date before  ".$dateEndLitt);
        $q = $this->manager->createQuery('select ps from App\Entity\Promotion ps where ps.endDate < :dateEnd')->setParameter('dateEnd', $dateEndLitt);
        foreach ($q->toIterable() as $promo) {
            $this->manager->remove($promo);
            if (($i % $batchSize) === 0) {
                $output->writeln("Saved  $i promotions ");
                $this->manager->flush();
                $this->manager->clear();
            }
            $i++;
        }
        $output->writeln("Remove  $i promotions");
        $this->manager->flush();
        $this->manager->clear();

        return Command::SUCCESS;
    }
}
