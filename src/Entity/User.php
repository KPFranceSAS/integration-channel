<?php

namespace App\Entity;

use App\Helper\Traits\TraitTimeUpdated;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TraitTimeUpdated;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON)]
    private $roles = ["ROLE_USER"];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING)]
    private ?string $password = null;


    public $plainPassword;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON, nullable: true)]
    private $channels = [];





    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\SaleChannel>
     */
    #[ORM\ManyToMany(targetEntity: SaleChannel::class, inversedBy: 'users')]
    private \Doctrine\Common\Collections\Collection $saleChannels;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    private ?bool $isAdmin = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    private ?bool $isPricingManager = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    private ?bool $isFbaManager = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    private ?bool $isOrderManager = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    private ?bool $isSuperAdmin = null;



    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->updateRoles();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updateRoles();
    }

    private function updateRoles(): void
    {
        $roles = ["ROLE_USER"];


        if ($this->isAdmin) {
            $roles[] = 'ROLE_ADMIN';
            if ($this->isSuperAdmin) {
                $roles[] = 'ROLE_SUPER_ADMIN';
            }
        }

        

        if ($this->isOrderManager) {
            $roles[] = 'ROLE_ORDER';
        } else {
            $this->channels =[];
        }

        if ($this->isFbaManager) {
            $roles[] = 'ROLE_AMAZON';
        }

        if ($this->isPricingManager) {
            $roles[] = 'ROLE_PRICING';
        } else {
            $this->saleChannels =[];
        }

        $this->roles = $roles;
    }


    public function __construct()
    {
        $this->saleChannels = new ArrayCollection();
    }



    public function validate(ExecutionContextInterface $context, $payload)
    {
        if ($this->isPricingManager && count($this->saleChannels)==0) {
            $context->buildViolation('You must define at least one sale channel')
                ->atPath('saleChannels')
                ->addViolation();
        }
    }


    public function getId(): ?int
    {
        return $this->id;
    }


    public function getUsername(): ?string
    {
        return $this->email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }


    public function hasChannel(string $channel): bool
    {
        if ($this->channels && is_array($this->channels)) {
            foreach ($this->channels as $channelDb) {
                if ($channelDb == $channel) {
                    return true;
                }
            }
        }
        return false;
    }



    public function hasRole(string $role): bool
    {
        if ($this->roles && is_array($this->roles)) {
            foreach ($this->roles as $roleDb) {
                if ($roleDb == $role) {
                    return true;
                }
            }
        }
        return false;
    }


    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }



    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

   

    public function getChannels(): ?array
    {
        return $this->channels;
    }

    public function setChannels(?array $channels): self
    {
        $this->channels = $channels;

        return $this;
    }




    /**
     * @return Collection<int, SaleChannel>
     */
    public function getSaleChannels(): Collection
    {
        return $this->saleChannels;
    }

    public function addSaleChannel(SaleChannel $saleChannel): self
    {
        if (!$this->saleChannels->contains($saleChannel)) {
            $this->saleChannels[] = $saleChannel;
        }

        return $this;
    }

    public function hasSaleChannel(SaleChannel $saleChannel): bool
    {
        return $this->saleChannels->contains($saleChannel);
    }

    public function removeSaleChannel(SaleChannel $saleChannel): self
    {
        $this->saleChannels->removeElement($saleChannel);

        return $this;
    }

    public function isIsAdmin(): ?bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(?bool $isAdmin): self
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    public function isIsPricingManager(): ?bool
    {
        return $this->isPricingManager;
    }

    public function setIsPricingManager(?bool $isPricingManager): self
    {
        $this->isPricingManager = $isPricingManager;

        return $this;
    }

    public function isIsFbaManager(): ?bool
    {
        return $this->isFbaManager;
    }

    public function setIsFbaManager(?bool $isFbaManager): self
    {
        $this->isFbaManager = $isFbaManager;

        return $this;
    }

    public function isIsOrderManager(): ?bool
    {
        return $this->isOrderManager;
    }

    public function setIsOrderManager(?bool $isOrderManager): self
    {
        $this->isOrderManager = $isOrderManager;

        return $this;
    }

    public function isIsSuperAdmin(): ?bool
    {
        return $this->isSuperAdmin;
    }

    public function setIsSuperAdmin(?bool $isSuperAdmin): self
    {
        $this->isSuperAdmin = $isSuperAdmin;

        return $this;
    }
}
