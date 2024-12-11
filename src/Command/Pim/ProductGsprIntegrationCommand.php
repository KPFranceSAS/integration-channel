<?php

namespace App\Command\Pim;


use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Entity\Product;
use App\Helper\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:product-gspr-integration', 'Import all products and brands')]
class ProductGsprIntegrationCommand extends Command
{
    public function __construct(
        ManagerRegistry $manager,
        private readonly KitPersonalizacionSportConnector $kitPerzonalizacionConnector,
        private BusinessCentralAggregator $businessCentralAggregator,
        private readonly MailService $mailService
    ) {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    private $manager;




    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->getAllVendors();
        $products = $this->manager->getRepository(Product::class)->findBy(['active'=>true]);
        foreach ($products as $product) {
            $item = $this->kitPerzonalizacionConnector->getItemByNumber($product->getSku());
            if ($item) {
                $manufacturerNumber =  $this->businessCentralAggregator->getInitiales($item['owningCompany']).'_'.$item['VendorNo'];
                if (array_key_exists($manufacturerNumber, $this->vendors)) {
                    $vendor = $this->vendors[$manufacturerNumber];
                    if ($this->isCountryInEU($vendor['address']['countryLetterCode'])) {
                        $product->setGsprName($vendor['displayName']);
                        $product->setGsprAddress($vendor['address']['street']);
                        $product->setGsprEmail($vendor['email']);
                        $product->setGsprCountry($vendor['address']['countryLetterCode']);
                        $product->setGsprCity($vendor['address']['city']);
                        $product->setGsprPostalCode($vendor['address']['postalCode']);
                        $product->setGsprWebsite(strlen($vendor['website'])>0 ? $vendor['website'] : null);
                        $product->setGsprPhone(null);
                    } else {
                        $product->setGsprName('KIT PERSONALIZACIÓN SPORT S.L');
                        $product->setGsprAddress('Carrer Isaac Newton, 8');
                        $product->setGsprEmail("info@kpsport.com");
                        $product->setGsprCountry("ES");
                        $product->setGsprCity('La Roca del Vallès, Barcelona');
                        $product->setGsprPostalCode('08430');
                        $product->setGsprWebsite("https://kp-group.eu");
                        $product->setGsprPhone('+34) 93 572 30 21');
                    }
                } 
            } 
            $this->manager->flush();
        }


        return self::SUCCESS;
    }


    protected function isCountryInEU($countryCode)
    {
        // Array of country codes that belong to the European Union (ISO 3166-1 alpha-2)
        $euCountries = [
            'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
            'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
            'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE'
        ];
    
        // Convert input country code to uppercase to ensure case insensitivity
        $countryCode = strtoupper($countryCode);
    
        // Check if the country code is in the array of EU country codes
        return in_array($countryCode, $euCountries);
    }


    private $vendors = [];


    public function getAllVendors()
    {
        $companies = $this->businessCentralAggregator->getAllCompanies();
        foreach ($companies as $company) {
            $vendors = $this->businessCentralAggregator->getBusinessCentralConnector($company)->getAllVendors();
            foreach ($vendors as $vendor) {
                $this->vendors[$this->businessCentralAggregator->getInitiales($company).'_'.$vendor['number']]=$vendor;
            }
        }
    }
}
