<?php

namespace App\Entity;

use App\Helper\Traits\TraitTimeUpdated;
use App\Repository\JobRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Job
{

    final public const Status_Created = 0;
    final public const Status_Processing = 1;
    final public const Status_Finished = 2;
    final public const Status_Cancelled = 3;
    final public const Status_Error = 3;

    final public const Type_Sync_Products = 'Sync products';
    final public const Type_Sync_Prices = 'Sync prices';

    use TraitTimeUpdated;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $status = self::Status_Created;

    #[ORM\ManyToOne]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $jobType = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?IntegrationChannel $channel = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->user ? $this->user->getUserIdentifier() : null;
    }


    public function getJobType(): ?string
    {
        return $this->jobType;
    }

    public function setJobType(string $jobType): static
    {
        $this->jobType = $jobType;

        return $this;
    }

    public function getChannel(): ?IntegrationChannel
    {
        return $this->channel;
    }

    public function setChannel(?IntegrationChannel $channel): static
    {
        $this->channel = $channel;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }
}
