<?php

namespace App\Entity;

use App\Helper\Traits\TraitLoggable;
use App\Helper\Traits\TraitTimeUpdated;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class ImportPricing
{
    public const Status_Created = 0;
    public const Status_ToConfirm = 1;
    public const Status_ToImport = 2;
    public const Status_Imported = 3;
    public const Status_Importing = 4;
    public const Status_Cancelled = 5;


    public const Type_Import_Pricing = 'Import Pricing';

    public const Type_Import_Promotion = 'Import promotions';

    use TraitLoggable;

    use TraitTimeUpdated;
    
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;


    /**
     * @ORM\Column(type="string", length=255)
     */
    private $importType;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $content = [];

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    public $uploadedFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $comments;



    public function jobLitteral(): string
    {
        return $this->getImportType();
    }


    public function getContentHeader()
    {
        return count($this->content) > 0 ? array_keys($this->content[0]) : [];
    }


    public function getUsername(): ?string
    {
        return $this->user ? $this->user->getUserIdentifier() : null;
    }



    public function getId(): ?int
    {
        return $this->id;
    }



    public function getImportType(): ?string
    {
        return $this->importType;
    }

    public function setImportType(string $importType): self
    {
        $this->importType = $importType;

        return $this;
    }

    public function getContent(): ?array
    {
        return $this->content;
    }

    public function setContent(?array $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): self
    {
        $this->comments = $comments;

        return $this;
    }
}
