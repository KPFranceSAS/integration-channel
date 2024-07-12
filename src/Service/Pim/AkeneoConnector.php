<?php

namespace App\Service\Pim;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use Psr\Log\LoggerInterface;

class AkeneoConnector
{
    private $client;


    public function __construct(
        private readonly LoggerInterface $logger,
        string $akeneoUrl,
        string $akeneoClientId,
        string $akeneoClientSecret,
        string $akeneoUsername,
        string $akeneoPassword
    ) {
        $clientBuilder = new AkeneoPimClientBuilder($akeneoUrl);
        $this->client = $clientBuilder->buildAuthenticatedByPassword(
            $akeneoClientId,
            $akeneoClientSecret,
            $akeneoUsername,
            $akeneoPassword
        );
    }

    public function getFamily($family)
    {
        return $this->client->getFamilyApi()->get($family);
    }

    public function getFamilyVariant($family, $familyVariant)
    {
        return $this->client->getFamilyVariantApi()->get($family, $familyVariant);
    }


    public function getAttribute($code)
    {
        return $this->client->getAttributeApi()->get($code);
    }



    public function getAllAttributes()
    {
        return $this->client->getAttributeApi()->all();
    }



    public function getAllOptionsAttribute($attributeCode)
    {
        return $this->client->getAttributeOptionApi()->all($attributeCode);
    }


    public function getAttributeOption($attributeCode, $code)
    {
        return $this->client->getAttributeOptionApi()->get($attributeCode, $code);
    }

    
    public function getProductModel($productModel)
    {
        return $this->client->getProductModelApi()->get($productModel);
    }


    public function getAllProducts()
    {
        return $this->client->getProductApi()->all();
    }


  

    public function searchProducts(SearchBuilder $searchBuilder, $scope)
    {
        $searchFilters = $searchBuilder->getFilters();
        return $this->client->getProductApi()->all(50, ['search' => $searchFilters, 'scope' => $scope]);
    }



    public function getAllFiltreredProducts(SearchBuilder $searchFilters)
    {
        return $this->client->getProductApi()->all('50', ['search'=>$searchFilters->getFilters()]);
    }

    public function updateProduct($identifier, $values)
    {
        return $this->client->getProductApi()->upsert($identifier, $values);
    }



    public function getAllCategories()
    {
        return $this->client->getCategoryApi()->all();
    }


    public function getAllChildrenCategoriesByParent($parentCode)
    {
        $searchFilters = new SearchBuilder();
        $searchFilters->addFilter('parent', '=', $parentCode);
        
        return $this->client->getCategoryApi()->all('50', ['search'=>$searchFilters->getFilters()]);
    }



    public function getClient()
    {
        return $this->client;
    }


    public function getParent($identifier): array
    {
        return $this->client->getProductModelApi()->get($identifier);
    }

    public function updateParent($identifier, $values): int
    {
        return $this->client->getProductModelApi()->upsert($identifier, $values);
    }


    public function updateProductParent($identifier, $parent, $updateProductValue)
    {
        $this->logger->info('Update child '.$identifier.' '.json_encode($updateProductValue));
        $this->updateProduct($identifier, ['values' => $updateProductValue]);
        if ($parent) {
            $this->logger->info('Update parent '.$parent.' '.json_encode($updateProductValue));
            $parentPim = $this->getParent($parent);
            $this->updateParent($parent, ['values' => $updateProductValue]);
            if($parentPim['parent']) {
                $this->logger->info('Update grand paren '.$parentPim['parent'].' '.json_encode($updateProductValue));
                $this->updateParent($parentPim['parent'], ['values' => $updateProductValue]);
            }
        }
    }
}
