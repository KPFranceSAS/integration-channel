<?php

namespace App\Entity;

use App\Entity\ProductSaleChannel;
use App\Helper\Traits\TraitTimeUpdated;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity("code")
 */
class SaleChannel
{
    use TraitTimeUpdated;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * 
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity=ProductSaleChannel::class, mappedBy="saleChannel", orphanRemoval=true, cascade={"persist","remove"})
     */
    private $productSaleChannels;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $currencyCode;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $countryCode;

    /**
    * @ORM\Column(type="string", length=255)
    */
    private $company;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $channel;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $color;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, mappedBy="saleChannels")
     */
    private $users;

    /**
     * @ORM\ManyToOne(targetEntity=IntegrationChannel::class, inversedBy="saleChannels")
     */
    private $integrationChannel;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $codePim;

    
    public function __construct()
    {
        $this->productSaleChannels = new ArrayCollection();
        $this->users = new ArrayCollection();
    }


    public function getCurrencySymbol(){
        switch ($this->currencyCode){
            case 'EUR':
                return '€';
            case 'GBP' :
                return '£';    
            default :
                return '';
        }
    }


    public function __toString(){
        return $this->name;
    }   
    
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|ProductSaleChannel[]
     */
    public function getProductSaleChannels(): Collection
    {
        return $this->productSaleChannels;
    }

    public function addProductSaleChannel(ProductSaleChannel $productSaleChannel): self
    {
        if (!$this->productSaleChannels->contains($productSaleChannel)) {
            $this->productSaleChannels[] = $productSaleChannel;
            $productSaleChannel->setSaleChannel($this);
        }

        return $this;
    }

    public function removeProductSaleChannel(ProductSaleChannel $productSaleChannel): self
    {
        if ($this->productSaleChannels->removeElement($productSaleChannel)) {
            // set the owning side to null (unless already changed)
            if ($productSaleChannel->getSaleChannel() === $this) {
                $productSaleChannel->setSaleChannel(null);
            }
        }

        return $this;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

   
    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(string $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function setChannel(?string $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addSaleChannel($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeSaleChannel($this);
        }

        return $this;
    }

    public function getIntegrationChannel(): ?IntegrationChannel
    {
        return $this->integrationChannel;
    }

    public function setIntegrationChannel(?IntegrationChannel $integrationChannel): self
    {
        $this->integrationChannel = $integrationChannel;

        return $this;
    }

    public function getCodePim(): ?string
    {
        return $this->codePim;
    }

    public function setCodePim(?string $codePim): self
    {
        $this->codePim = $codePim;

        return $this;
    }
}
