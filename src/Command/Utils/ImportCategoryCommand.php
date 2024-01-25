<?php

namespace App\Command\Utils;

use App\Entity\Category;
use App\Helper\Utils\CsvExtracter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCategoryCommand extends Command
{
    protected static $defaultName = 'app:import-category';
    protected static $defaultDescription = 'Import all categories';

    public function __construct(ManagerRegistry $manager, private readonly CsvExtracter $csvExtracter)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    private $manager;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('pathFile', InputArgument::REQUIRED, 'Path of the file for injecting correlation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $categories = $this->getCategories($input->getArgument('pathFile'));
        $output->writeln('Start imports ' . count($categories));
        foreach ($categories as $category) {
            $categoryDb = $this->manager->getRepository(Category::class)->findOneBy(["name" => $category]);
            if (!$categoryDb) {
                $categoryDb = new Category();
                $categoryDb->setName($category);
                $this->manager->persist($categoryDb);
                $this->manager->flush();
            }
        }
        $output->writeln('Finish imports ' . count($categories));
        return Command::SUCCESS;
    }


    public function getCategories($pathFile)
    {
        $categories = $this->csvExtracter->extractAssociativeDatasFromCsv($pathFile);
        $categorieMerges = [];
        foreach ($categories as $categorie) {
            if (!in_array($categorie['Category'], $categorieMerges)) {
                $categorieMerges[] = $categorie['Category'];
            }
        }
        return $categorieMerges;
    }
}
