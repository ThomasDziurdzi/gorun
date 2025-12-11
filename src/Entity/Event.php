<?php

namespace App\Entity;

use App\Enum\EventStatus;
use App\Enum\RunningLevel;
use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[Vich\Uploadable]
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
    private ?\DateTimeImmutable $eventDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $estimateDuration = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $distance = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxParticipants = null;

    #[ORM\Column(length: 20, enumType: RunningLevel::class)]
    private ?RunningLevel $requiredLevel = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $pace = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[Assert\File(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        mimeTypesMessage: 'Veuillez uploader une image valide (JPG, PNG ou WEBP)'
    )]
    #[Vich\UploadableField(mapping: 'event_images', fileNameProperty: 'coverImage')]
    private ?File $imageFile = null;

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

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'event')]
    private Collection $notifications;

    public function __construct()
    {
        $this->creationDate = new \DateTimeImmutable();
        $this->status = EventStatus::DRAFT;
        $this->requiredLevel = RunningLevel::ALL_LEVELS;
        $this->registrations = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->notifications = new ArrayCollection();
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

    public function getEventDate(): ?\DateTimeImmutable
    {
        return $this->eventDate;
    }

    public function setEventDate(\DateTimeImmutable $eventDate): static
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

    public function getRequiredLevel(): RunningLevel
    {
        return $this->requiredLevel;
    }

    public function setRequiredLevel(RunningLevel $requiredLevel): static
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
        if (
            $this->registrations->removeElement($registration)
            && $registration->getEvent() === $this
        ) {
            $registration->setEvent(null);
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
        if (
            $this->comments->removeElement($comment)
            && $comment->getEvent() === $this
        ) {
            $comment->setEvent(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setEvent($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if (
            $this->notifications->removeElement($notification)
            && $notification->getEvent() === $this
        ) {
            $notification->setEvent(null);
        }

        return $this;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            $this->updatedDate = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function isFull(): bool
    {
        if (null === $this->maxParticipants) {
            return false;
        }

        $confirmedCount = 0;
        foreach ($this->registrations as $registration) {
            if ('CONFIRMED' === $registration->getStatus()->value) {
                ++$confirmedCount;
            }
        }

        return $confirmedCount >= $this->maxParticipants;
    }

    public function getSpotsLeft(): ?int
    {
        if (null === $this->maxParticipants) {
            return null;
        }

        $confirmedCount = 0;
        foreach ($this->registrations as $registration) {
            if ('CONFIRMED' === $registration->getStatus()->value) {
                ++$confirmedCount;
            }
        }

        return max(0, $this->maxParticipants - $confirmedCount);
    }

    public function isPast(): bool
    {
        $now = new \DateTimeImmutable('today');

        return $this->eventDate < $now;
    }

    public function getEffectiveStatus(): EventStatus
    {
        if (EventStatus::PUBLISHED === $this->status && $this->isPast()) {
            return EventStatus::COMPLETED;
        }

        return $this->status;
    }

    public function canRegister(): bool
    {
        $effectiveStatus = $this->getEffectiveStatus();

        return EventStatus::PUBLISHED === $effectiveStatus && !$this->isFull();
    }

    public function isVisibleToPublic(): bool
    {
        $effectiveStatus = $this->getEffectiveStatus();

        return in_array($effectiveStatus, [
            EventStatus::PUBLISHED,
            EventStatus::COMPLETED,
            EventStatus::CANCELLED,
        ]);
    }
}
