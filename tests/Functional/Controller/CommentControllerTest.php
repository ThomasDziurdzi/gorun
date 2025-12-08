<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Comment;
use App\Entity\Event;
use App\Entity\User;
use App\Enum\EventStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CommentControllerTest extends WebTestCase
{
    private const EVENT_SHOW_URL = '/evenement/';
    private const COMMENT_BASE_URL = '/commentaire/';
    private const USER_OR_EVENT_NOT_FOUND = 'Utilisateur ou événement non trouvé';

    public function testCommentsAreDisplayedOnEventShow(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $event = $entityManager->getRepository(Event::class)->findOneBy([
            'status' => EventStatus::PUBLISHED,
        ]);

        if (!$event) {
            $this->markTestSkipped('Aucun événement publié trouvé');
        }

        $client->request('GET', self::EVENT_SHOW_URL.$event->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Commentaires', $client->getResponse()->getContent());
    }

    public function testAddCommentRequiresAuthentication(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $event = $entityManager->getRepository(Event::class)->findOneBy([
            'status' => EventStatus::PUBLISHED,
        ]);

        if (!$event) {
            $this->markTestSkipped('Aucun événement publié trouvé');
        }

        $client->request('POST', self::COMMENT_BASE_URL.'evenement/'.$event->getId().'/ajouter');

        $this->assertResponseRedirects();
    }

    public function testAuthenticatedUserCanAddComment(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = $entityManager->getRepository(User::class)->findOneBy([]);
        $event = $entityManager->getRepository(Event::class)->findOneBy([
            'status' => EventStatus::PUBLISHED,
        ]);

        if (!$user || !$event) {
            $this->markTestSkipped(self::USER_OR_EVENT_NOT_FOUND);
        }

        $client->loginUser($user);

        $crawler = $client->request('GET', self::EVENT_SHOW_URL.$event->getId());
        $form = $crawler->selectButton('Publier le commentaire')->form();

        $form['comment[content]'] = 'Ceci est un commentaire de test fonctionnel avec suffisamment de caractères.';
        $form['comment[rating]'] = '5';

        $client->submit($form);

        $this->assertResponseRedirects(self::EVENT_SHOW_URL.$event->getId());

        $client->followRedirect();
        $this->assertSelectorExists('.bg-green-50');
    }

    public function testAddCommentWithInvalidDataShowsError(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = $entityManager->getRepository(User::class)->findOneBy([]);
        $event = $entityManager->getRepository(Event::class)->findOneBy([
            'status' => EventStatus::PUBLISHED,
        ]);

        if (!$user || !$event) {
            $this->markTestSkipped(self::USER_OR_EVENT_NOT_FOUND);
        }

        $client->loginUser($user);

        $crawler = $client->request('GET', self::EVENT_SHOW_URL.$event->getId());
        $form = $crawler->selectButton('Publier le commentaire')->form();

        $form->disableValidation();
        $form['comment[content]'] = '';

        $client->submit($form);

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertSelectorExists('.bg-red-50');
    }

    public function testUserCanEditOwnComment(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = $entityManager->getRepository(User::class)->findOneBy([]);
        $event = $entityManager->getRepository(Event::class)->findOneBy([
            'status' => EventStatus::PUBLISHED,
        ]);

        if (!$user || !$event) {
            $this->markTestSkipped(self::USER_OR_EVENT_NOT_FOUND);
        }

        $comment = new Comment();
        $comment->setContent('Commentaire à modifier avec suffisamment de texte');
        $comment->setRating(3);
        $comment->setAuthor($user);
        $comment->setEvent($event);

        $entityManager->persist($comment);
        $entityManager->flush();

        $commentId = $comment->getId();

        $client->loginUser($user);

        $crawler = $client->request('GET', self::COMMENT_BASE_URL.$commentId.'/modifier');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');

        $form = $crawler->selectButton('Enregistrer')->form();
        $form['comment[content]'] = 'Commentaire modifié avec du contenu supplémentaire';
        $form['comment[rating]'] = '5';

        $client->submit($form);

        $this->assertResponseRedirects();

        $entityManager->clear();
        $updatedComment = $entityManager->getRepository(Comment::class)->find($commentId);

        $this->assertStringContainsString('modifié', $updatedComment->getContent());
        $this->assertNotNull($updatedComment->getUpdatedDate());
    }

    public function testUserCannotEditOtherUserComment(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $users = $entityManager->getRepository(User::class)->findBy([], null, 2);

        if (count($users) < 2) {
            $this->markTestSkipped('Pas assez d\'utilisateurs');
        }

        $author = $users[0];
        $otherUser = $users[1];

        $event = $entityManager->getRepository(Event::class)->findOneBy([
            'status' => EventStatus::PUBLISHED,
        ]);

        if (!$event) {
            $this->markTestSkipped('Événement publié non trouvé');
        }

        $comment = new Comment();
        $comment->setContent('Commentaire d\'un autre utilisateur pour test');
        $comment->setAuthor($author);
        $comment->setEvent($event);

        $entityManager->persist($comment);
        $entityManager->flush();

        $client->loginUser($otherUser);

        $client->request('GET', self::COMMENT_BASE_URL.$comment->getId().'/modifier');

        $this->assertResponseRedirects();
    }

    public function testUserCanDeleteOwnComment(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = $entityManager->getRepository(User::class)->findOneBy([]);
        $event = $entityManager->getRepository(Event::class)->findOneBy([
            'status' => EventStatus::PUBLISHED,
        ]);

        if (!$user || !$event) {
            $this->markTestSkipped(self::USER_OR_EVENT_NOT_FOUND);
        }

        $comment = new Comment();
        $comment->setContent('Commentaire à supprimer avec assez de caractères');
        $comment->setAuthor($user);
        $comment->setEvent($event);

        $entityManager->persist($comment);
        $entityManager->flush();

        $commentId = $comment->getId();

        $client->loginUser($user);

        $crawler = $client->request('GET', self::EVENT_SHOW_URL.$event->getId());

        $deleteForm = $crawler
            ->filter(sprintf('form[action*="%s%s/supprimer"]', self::COMMENT_BASE_URL, $commentId))
            ->form();

        $client->submit($deleteForm);

        $this->assertResponseRedirects();

        $deletedComment = $entityManager->getRepository(Comment::class)->find($commentId);
        $this->assertNull($deletedComment);
    }

    public function testDeleteCommentRequiresValidCsrfToken(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = $entityManager->getRepository(User::class)->findOneBy([]);
        $event = $entityManager->getRepository(Event::class)->findOneBy([
            'status' => EventStatus::PUBLISHED,
        ]);

        if (!$user || !$event) {
            $this->markTestSkipped(self::USER_OR_EVENT_NOT_FOUND);
        }

        $comment = new Comment();
        $comment->setContent('Commentaire pour test CSRF avec du texte suffisant');
        $comment->setAuthor($user);
        $comment->setEvent($event);

        $entityManager->persist($comment);
        $entityManager->flush();

        $client->loginUser($user);

        $client->request('GET', self::EVENT_SHOW_URL.$event->getId());

        $client->request('POST', self::COMMENT_BASE_URL.$comment->getId().'/supprimer', [
            '_token' => 'invalid_token',
        ]);

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertSelectorExists('.bg-red-50');
    }
}
