<?php

namespace App\EventListener;

use App\Entity\Event;
use App\Enum\EventStatus;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, entity: Event::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Event::class)]
class EventStatusListener
{
    public function prePersist(Event $event): void
    {
        $this->updateStatusIfPast($event);
    }

    public function preUpdate(Event $event): void
    {
        $this->updateStatusIfPast($event);
    }

    private function updateStatusIfPast(Event $event): void
    {
        if ($event->isPast() && EventStatus::CANCELLED !== $event->getStatus()) {
            $event->setStatus(EventStatus::COMPLETED);
        }
    }
}
