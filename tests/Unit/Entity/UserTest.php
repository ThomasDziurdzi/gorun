<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Enum\RunningLevel;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testCanBeCreated(): void
    {
        $this->assertInstanceOf(User::class, $this->user);
    }

    public function testEmail(): void
    {
        $this->assertNull($this->user->getEmail());
        
        $this->user->setEmail('test@example.com');
        $this->assertEquals('test@example.com', $this->user->getEmail());
    }

    public function testUserIdentifier(): void
    {
        $this->user->setEmail('john@example.com');
        $this->assertEquals('john@example.com', $this->user->getUserIdentifier());
    }

    public function testPassword(): void
    {
        $this->assertNull($this->user->getPassword());
        
        $hashedPassword = '$2y$13$hashedpassword';
        $this->user->setPassword($hashedPassword);
        $this->assertEquals($hashedPassword, $this->user->getPassword());
    }

    public function testDefaultRole(): void
    {
        $roles = $this->user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testRoles(): void
    {
        $this->user->setRoles(['ROLE_ADMIN']);
        $roles = $this->user->getRoles();
        
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testRolesUniqueness(): void
    {
        $this->user->setRoles(['ROLE_USER', 'ROLE_USER', 'ROLE_ADMIN']);
        $roles = $this->user->getRoles();
        
        $uniqueRoles = array_unique($roles);
        $this->assertCount(count($uniqueRoles), $roles);
    }

    public function testFirstname(): void
    {
        $this->assertNull($this->user->getFirstname());
        
        $this->user->setFirstname('John');
        $this->assertEquals('John', $this->user->getFirstname());
    }

    public function testLastname(): void
    {
        $this->assertNull($this->user->getLastname());
        
        $this->user->setLastname('Doe');
        $this->assertEquals('Doe', $this->user->getLastname());
    }

    public function testCity(): void
    {
        $this->assertNull($this->user->getCity());
        
        $this->user->setCity('Paris');
        $this->assertEquals('Paris', $this->user->getCity());
    }

    public function testRunningLevel(): void
    {
        $this->assertNull($this->user->getRunningLevel());
        
        $this->user->setRunningLevel(RunningLevel::ADVANCED);
        $this->assertEquals(RunningLevel::ADVANCED, $this->user->getRunningLevel());
    }

    public function testIsVerified(): void
    {
        $this->assertFalse($this->user->isVerified());
        
        $this->user->setIsVerified(true);
        $this->assertTrue($this->user->isVerified());
    }

    public function testEraseCredentials(): void
    {
        $this->user->eraseCredentials();
        $this->assertTrue(true);
    }

    public function testLastLogin(): void
    {
        $this->assertNull($this->user->getLastLogin());
        
        $now = new \DateTimeImmutable();
        $this->user->setLastLogin($now);
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->user->getLastLogin());
    }
}