<?php

namespace App\Helper\BusinessCentral\Connector;

use App\Helper\BusinessCentral\Connector\NTLMSoapClient;
use Psr\Log\LoggerInterface;

abstract class Connector
{

    /**
     *
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * 
     *
     * @var string
     */
    protected $companyUsed;

    /**
     * 
     *
     * @var string
     */
    protected $urlBC;

    /**
     * 
     *
     * @var NTLMSoapClient
     */
    protected $client;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger, string $urlBC, string  $loginBC, string  $passwordBC)
    {
        $this->logger = $logger;
        $this->urlBC = $urlBC;

        $this->credentials = [
            'user' => $loginBC,
            'password' => $passwordBC,
        ];
    }


    abstract protected function getExtensionService();


    protected function initiateClient()
    {
        if (!$this->client) {
            $this->logger->info('Initialise with ' . $this->getCompanyUrl() . ' ' . $this->credentials['user']);
            $this->client = new NTLMSoapClient($this->urlBC . '/WS/' . $this->getCompanyUrl() . '/Page/' . $this->getExtensionService(), $this->credentials);
        }
    }


    protected function getCompanyUrl()
    {
        return $this->companyUsed ? $this->companyUsed : self::KPS_FR;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function useKPFrance()
    {
        $this->changeCompany(self::KPS_FR);
    }


    /**
     * Undocumented function
     *
     * @return void
     */
    public function useKPSport()
    {
        $this->changeCompany(self::KPS_GROUP);
    }


    /**
     * Undocumented function
     *
     * @return void
     */
    protected  function changeCompany($nameCompany)
    {
        if ($this->companyUsed != $nameCompany) {
            $this->companyUsed = $nameCompany;
            $this->initiateClient();
        }
    }


    const KPS_GROUP = 'KIT%20PERSONALIZACION%20SPORT%20SL';

    const KPS_FR = 'KP%20FRANCE';


    public function create($object)
    {
        $this->initiateClient();
        return $this->client->Create($object);
    }

    public function update($object)
    {
        $this->initiateClient();
        return $this->client->Update($object);
    }


    public function read($numberOrder)
    {
        $this->initiateClient();
        return $this->client->read(["No" => $numberOrder]);
    }


    public function search($filter, $limit = 0)
    {
        $this->initiateClient();
        $result = $this->client->ReadMultiple(
            [
                "filter" => $filter,
                'setSize' => $limit,
                'bookmarkKey' => null
            ]
        );
        return $result->ReadMultiple_Result;
    }
}
