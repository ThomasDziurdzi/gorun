<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Event;
use App\Entity\Location;
use App\Entity\User;
use App\Enum\EventStatus;
use App\Enum\RunningLevel;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    private Event $event;

    protected function setUp(): void
    {
        $this->event = new Event();
    }

    public function testCanBeCreated(): void
    {
        $this->assertInstanceOf(Event::class, $this->event);
    }

    public function testTitle(): void
    {
        $this->assertNull($this->event->getTitle());
        
        $this->event->setTitle('Sortie 10K Paris');
        $this->assertEquals('Sortie 10K Paris', $this->event->getTitle());
    }

    public function testDescription(): void
    {
        $this->assertNull($this->event->getDescription());
        
        $description = 'Belle sortie dans Paris';
        $this->event->setDescription($description);
        $this->assertEquals($description, $this->event->getDescription());
    }

    public function testEventDate(): void
    {
        $date = new \DateTimeImmutable('2025-12-31 10:00:00');
        $this->event->setEventDate($date);
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->event->getEventDate());
        $this->assertEquals('2025-12-31', $this->event->getEventDate()->format('Y-m-d'));
    }

    public function testDistance(): void
    {
        $this->assertNull($this->event->getDistance());
        
        $this->event->setDistance('10.5');
        $this->assertEquals('10.5', $this->event->getDistance());
    }

    public function testMaxParticipants(): void
    {
        $this->assertNull($this->event->getMaxParticipants());
        
        $this->event->setMaxParticipants(20);
        $this->assertEquals(20, $this->event->getMaxParticipants());
    }

    public function testRequiredLevel(): void
    {
        $this->assertEquals(RunningLevel::ALL_LEVELS, $this->event->getRequiredLevel());
        
        $this->event->setRequiredLevel(RunningLevel::INTERMEDIATE);
        $this->assertEquals(RunningLevel::INTERMEDIATE, $this->event->getRequiredLevel());
    }

    public function testStatus(): void
    {
        $this->assertEquals(EventStatus::DRAFT, $this->event->getStatus());
        
        $this->event->setStatus(EventStatus::PUBLISHED);
        $this->assertEquals(EventStatus::PUBLISHED, $this->event->getStatus());
    }

    public function testOrganizer(): void
    {
        $this->assertNull($this->event->getOrganizer());
        
        $user = new User();
        $user->setEmail('test@example.com');
        $this->event->setOrganizer($user);
        
        $this->assertInstanceOf(User::class, $this->event->getOrganizer());
        $this->assertEquals('test@example.com', $this->event->getOrganizer()->getEmail());
    }

    public function testLocation(): void
    {
        $this->assertNull($this->event->getLocation());
        
        $location = new Location();
        $location->setCity('Paris');
        $this->event->setLocation($location);
        
        $this->assertInstanceOf(Location::class, $this->event->getLocation());
        $this->assertEquals('Paris', $this->event->getLocation()->getCity());
    }
}