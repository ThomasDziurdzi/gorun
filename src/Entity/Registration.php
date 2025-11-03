<?php

namespace App\Entity;

use App\Enum\RegistrationStatus;
use App\Repository\RegistrationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegistrationRepository::class)]
#[ORM\Table(name: 'registration')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_EVENT', columns: ['user_id', 'event_id'])]
class Registration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $registrationDate = null;

    #[ORM\Column(type: Types::STRING, enumType: RegistrationStatus::class)]
    private RegistrationStatus $status = RegistrationStatus::CONFIRMED;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $cancellationDate = null;

    #[ORM\ManyToOne(inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    public function __construct()
    {
        $this->registrationDate = new \DateTimeImmutable();
        $this->status = RegistrationStatus::CONFIRMED;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRegistrationDate(): ?\DateTimeImmutable
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(\DateTimeImmutable $registrationDate): static
    {
        $this->registrationDate = $registrationDate;

        return $this;
    }

    public function getStatus(): RegistrationStatus
    {
        return $this->status;
    }

    public function setStatus(RegistrationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCancellationDate(): ?\DateTimeImmutable
    {
        return $this->cancellationDate;
    }

    public function setCancellationDate(?\DateTimeImmutable $cancellationDate): static
    {
        $this->cancellationDate = $cancellationDate;

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

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function cancel(): void
    {
        $this->status = RegistrationStatus::CANCELLED;
        $this->cancellationDate = new \DateTimeImmutable();
    }

    public function confirm(): void
    {
        $this->status = RegistrationStatus::CONFIRMED;
        $this->cancellationDate = null;
    }

    public function isConfirmed(): bool
    {
        return RegistrationStatus::CONFIRMED === $this->status;
    }

    public function isCancelled(): bool
    {
        return RegistrationStatus::CANCELLED === $this->status;
    }

    public function isPending(): bool
    {
        return RegistrationStatus::PENDING === $this->status;
    }
}
