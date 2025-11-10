<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Comment;
use App\Entity\Event;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CommentTest extends KernelTestCase
{
    private function getValidator(): ValidatorInterface
    {
        self::bootKernel();

        return self::getContainer()->get(ValidatorInterface::class);
    }

    private function getValidComment(): Comment
    {
        $comment = new Comment();
        $comment->setContent('Un commentaire valide de plus de 1 caractère');
        $comment->setRating(4);
        $comment->setEvent(new Event());
        $comment->setAuthor(new User());

        return $comment;
    }

    public function testValidCommentPassesValidation(): void
    {
        $validator = $this->getValidator();
        $comment = $this->getValidComment();

        $errors = $validator->validate($comment);

        $this->assertCount(0, $errors, 'Un commentaire valide ne doit pas produire d’erreurs');
    }

    public function testContentIsRequired(): void
    {
        $validator = $this->getValidator();
        $comment = $this->getValidComment();
        $comment->setContent('');

        $errors = $validator->validate($comment);

        $this->assertGreaterThan(0, $errors->count(), 'Le contenu vide doit être invalide');
    }
}
