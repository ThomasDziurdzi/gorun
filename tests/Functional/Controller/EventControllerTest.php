<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Enum\EventStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventControllerTest extends WebTestCase
{
    private const EVENT_INDEX_URL = '/evenements';
    private const EVENT_NEW_URL = '/evenement/nouveau';
    private const EVENT_LOGIN_URL = '/connexion';

    public function testEventIndexIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', self::EVENT_INDEX_URL);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'événements');
    }

    public function testEventShowIsAccessible(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $event = $entityManager->getRepository(\App\Entity\Event::class)->findOneBy([
            'status' => EventStatus::PUBLISHED,
        ]);

        if ($event) {
            $client->request('GET', '/evenement/'.$event->getId());
            $this->assertResponseIsSuccessful();
        } else {
            $this->markTestSkipped('Aucun événement publié trouvé en base');
        }
    }

    public function testEventNewRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', self::EVENT_NEW_URL);

        $this->assertResponseRedirects(self::EVENT_LOGIN_URL);
    }

    public function testEventNewRequiresAdminRole(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'user@example.com']);

        if (!$user) {
            $this->markTestSkipped('Utilisateur de test non trouvé');
        }

        $client->loginUser($user);
        $client->request('GET', self::EVENT_NEW_URL);

        $this->assertResponseRedirects(self::EVENT_INDEX_URL);
    }

    public function testEventNewIsAccessibleForAdmin(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $admin = $entityManager->getRepository(User::class)->findOneBy([]);

        if (!$admin) {
            $this->markTestSkipped('Admin non trouvé');
        }

        $admin->setRoles(['ROLE_ADMIN']);
        $entityManager->flush();

        $client->loginUser($admin);
        $client->request('GET', self::EVENT_NEW_URL);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testEventIndexContainsEvents(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::EVENT_INDEX_URL);

        $this->assertResponseIsSuccessful();

        $hasEvents = $crawler->filter('article')->count() > 0;
        $hasNoEventsMessage = $crawler->filter('p:contains("Aucun événement")')->count() > 0;

        $this->assertTrue($hasEvents || $hasNoEventsMessage);
    }
}
