<?php

namespace App\Command\Pim;

use App\Entity\Product;
use App\Entity\ProductTypeCategorizacion;
use App\Service\Pim\AkeneoConnector;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:import-product-type-from-pim', 'import product type')]
class ImportProductTypeIntegration extends Command
{
    public function __construct(ManagerRegistry $manager, private readonly AkeneoConnector $akeneoConnector)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    private $manager;




    protected function execute(InputInterface $input, OutputInterface $output): int
    {



        $productTypes = $this->manager->getRepository(ProductTypeCategorizacion::class)->findAll();

        $productTypesIndexed = [];
        foreach($productTypes as $productType) {
            $productTypesIndexed[$productType->getPimProductType()]=$productType;
        }
        $productTypePims = $this->akeneoConnector->getAllOptionsAttribute('product_type');

        $productTypesPimCodes=[];

        foreach ($productTypePims as $productTypePim) {
            $productTypesPimCodes[] = $productTypePim['code'];
            if(!array_key_exists($productTypePim['code'], $productTypesIndexed)) {
                $productTypeCat=new ProductTypeCategorizacion();
                $productTypeCat->setPimProductType($productTypePim['code']);
                $productTypeCat->setExistInPim(true);
                $this->manager->persist($productTypeCat);
            } else {
                $productTypeCat = $productTypesIndexed[$productTypePim['code']];
            }

            $productTypeCat->setPimProductLabel($this->getLabel($productTypePim));
            $productTypeCat->setCountProducts($this->getCountProductsWithType($productTypePim['code']));
        }


        foreach($productTypesIndexed as $codeIndexed => $productTypesIndex) {
            $productTypesIndex->setExistInPim(in_array($codeIndexed, $productTypesPimCodes));
        }




        $this->manager->flush();
        return Command::SUCCESS;
    }


    public function getCountProductsWithType($productTypePimCode)
    {
        $queryBuilder = $this->manager->createQueryBuilder();

        $queryBuilder->select('COUNT(p.id)')
            ->from(Product::class, 'p')
            ->where('p.productType = :productType')
            ->setParameter('productType', $productTypePimCode);

        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        return $count;
    }



    public function getLabel($brand)
    {
        foreach($brand['labels'] as $locale => $label) {
            if(strlen($label)>0 && $locale=='en_GB') {
                return $label;
            }
        }
        return ucfirst(strtolower($brand['code']));
    }


}
