<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Event;
use App\Entity\Registration;
use App\Entity\User;
use App\Enum\RegistrationStatus;
use PHPUnit\Framework\TestCase;

class RegistrationTest extends TestCase
{
    private Registration $registration;

    protected function setUp(): void
    {
        $this->registration = new Registration();
    }

    public function testCanBeCreated(): void
    {
        $this->assertInstanceOf(Registration::class, $this->registration);
    }

    public function testUserRelation(): void
    {
        $this->assertNull($this->registration->getUser());

        $user = new User();
        $user->setEmail('runner@test.com');
        $this->registration->setUser($user);

        $this->assertInstanceOf(User::class, $this->registration->getUser());
        $this->assertEquals('runner@test.com', $this->registration->getUser()->getEmail());
    }

    public function testEventRelation(): void
    {
        $this->assertNull($this->registration->getEvent());

        $event = new Event();
        $event->setTitle('Test Event');
        $this->registration->setEvent($event);

        $this->assertInstanceOf(Event::class, $this->registration->getEvent());
        $this->assertEquals('Test Event', $this->registration->getEvent()->getTitle());
    }

    public function testDefaultStatus(): void
    {
        $this->assertEquals(RegistrationStatus::CONFIRMED, $this->registration->getStatus());
    }

    public function testStatusChange(): void
    {
        $this->registration->setStatus(RegistrationStatus::CANCELLED);
        $this->assertEquals(RegistrationStatus::CANCELLED, $this->registration->getStatus());

        $this->registration->setStatus(RegistrationStatus::PENDING);
        $this->assertEquals(RegistrationStatus::PENDING, $this->registration->getStatus());
    }

    public function testCancelMethod(): void
    {
        $this->assertEquals(RegistrationStatus::CONFIRMED, $this->registration->getStatus());

        $this->registration->cancel();

        $this->assertEquals(RegistrationStatus::CANCELLED, $this->registration->getStatus());
    }

    public function testRegistrationDate(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->registration->getRegistrationDate());

        $newDate = new \DateTimeImmutable('2025-12-31 10:00:00');
        $this->registration->setRegistrationDate($newDate);

        $this->assertEquals('2025-12-31', $this->registration->getRegistrationDate()->format('Y-m-d'));
    }

    public function testCompleteRegistrationScenario(): void
    {
        $user = new User();
        $user->setEmail('scenario@test.com');
        $user->setFirstname('John');

        $event = new Event();
        $event->setTitle('Marathon Paris');
        $event->setDistance('42.195');

        $registration = new Registration();
        $registration->setUser($user);
        $registration->setEvent($event);
        $registration->setRegistrationDate(new \DateTimeImmutable());

        $this->assertEquals(RegistrationStatus::CONFIRMED, $registration->getStatus());
        $this->assertEquals('scenario@test.com', $registration->getUser()->getEmail());
        $this->assertEquals('Marathon Paris', $registration->getEvent()->getTitle());
        $this->assertInstanceOf(\DateTimeImmutable::class, $registration->getRegistrationDate());
    }
}
