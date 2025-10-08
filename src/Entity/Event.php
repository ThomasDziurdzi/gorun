<?php

namespace App\Entity;

use App\Enum\EventStatus;
use App\Enum\RunningLevel;
use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTime $eventDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $estimateDuration = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $distance = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxParticipants = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, enumType: RunningLevel::class)]
    private array $requiredLevel = [];

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $pace = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[ORM\Column(length: 20, enumType: EventStatus::class)]
    private ?EventStatus $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $creationDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedDate = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Location $location = null;

    #[ORM\ManyToOne(inversedBy: 'organizedEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $organizer = null;

    /**
     * @var Collection<int, Registration>
     */
    #[ORM\OneToMany(targetEntity: Registration::class, mappedBy: 'event')]
    private Collection $registrations;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'event', orphanRemoval: true)]
    private Collection $comments;

    public function __construct()
    {
        $this->creationDate = new \DateTimeImmutable();
        $this->status = EventStatus::DRAFT;
        $this->registrations = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getEventDate(): ?\DateTime
    {
        return $this->eventDate;
    }

    public function setEventDate(\DateTime $eventDate): static
    {
        $this->eventDate = $eventDate;

        return $this;
    }

    public function getEstimateDuration(): ?int
    {
        return $this->estimateDuration;
    }

    public function setEstimateDuration(?int $estimateDuration): static
    {
        $this->estimateDuration = $estimateDuration;

        return $this;
    }

    public function getDistance(): ?string
    {
        return $this->distance;
    }

    public function setDistance(?string $distance): static
    {
        $this->distance = $distance;

        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(?int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;

        return $this;
    }

    /**
     * @return RunningLevel[]
     */
    public function getRequiredLevel(): array
    {
        return $this->requiredLevel;
    }

    public function setRequiredLevel(array $requiredLevel): static
    {
        $this->requiredLevel = $requiredLevel;

        return $this;
    }

    public function getPace(): ?string
    {
        return $this->pace;
    }

    public function setPace(?string $pace): static
    {
        $this->pace = $pace;

        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    public function getStatus(): ?EventStatus
    {
        return $this->status;
    }

    public function setStatus(EventStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeImmutable
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeImmutable $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getUpdatedDate(): ?\DateTimeImmutable
    {
        return $this->updatedDate;
    }

    public function setUpdatedDate(?\DateTimeImmutable $updatedDate): static
    {
        $this->updatedDate = $updatedDate;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }

    public function setOrganizer(?User $organizer): static
    {
        $this->organizer = $organizer;

        return $this;
    }

    /**
     * @return Collection<int, Registration>
     */
    public function getRegistrations(): Collection
    {
        return $this->registrations;
    }

    public function addRegistration(Registration $registration): static
    {
        if (!$this->registrations->contains($registration)) {
            $this->registrations->add($registration);
            $registration->setEvent($this);
        }

        return $this;
    }

    public function removeRegistration(Registration $registration): static
    {
        if ($this->registrations->removeElement($registration)) {
            // set the owning side to null (unless already changed)
            if ($registration->getEvent() === $this) {
                $registration->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setEvent($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getEvent() === $this) {
                $comment->setEvent(null);
            }
        }

        return $this;
    }
}