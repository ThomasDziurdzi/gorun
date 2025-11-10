<?php

namespace App\Tests\Unit\Form;

use App\Entity\Comment;
use App\Form\CommentType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class CommentTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'content' => 'Ceci est un commentaire de test valide avec plus de 10 caractères.',
            'rating' => 4,
        ];

        $model = new Comment();
        $form = $this->factory->create(CommentType::class, $model);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertSame($formData['content'], $model->getContent());
        $this->assertSame($formData['rating'], $model->getRating());
    }

    public function testContentFieldIsRequired(): void
    {
        $form = $this->factory->create(CommentType::class);
        $form->submit([
            'content' => '',
            'rating' => null,
        ]);

        $this->assertFalse($form->isValid());
        $this->assertGreaterThanOrEqual(1, $form->get('content')->getErrors(true)->count());
    }

    public function testContentMinLength(): void
    {
        $form = $this->factory->create(CommentType::class);
        $form->submit([
            'content' => '',
            'rating' => null,
        ]);

        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, $form->get('content')->getErrors(true)->count());
    }

    public function testContentMaxLength(): void
    {
        $form = $this->factory->create(CommentType::class);
        $form->submit([
            'content' => str_repeat('a', 1001),
            'rating' => null,
        ]);

        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, $form->get('content')->getErrors(true)->count());
    }

    public function testRatingIsOptional(): void
    {
        $form = $this->factory->create(CommentType::class);
        $form->submit([
            'content' => 'Un commentaire valide sans note.',
            'rating' => null,
        ]);

        $this->assertTrue($form->isValid());
    }

    public function testRatingAcceptsValidValues(): void
    {
        foreach ([1, 2, 3, 4, 5] as $rating) {
            $form = $this->factory->create(CommentType::class);
            $form->submit([
                'content' => 'Commentaire avec note valide '.$rating,
                'rating' => $rating,
            ]);

            $this->assertTrue($form->isValid(), "Rating {$rating} should be valid");
        }
    }

    public function testRatingRejectsInvalidValues(): void
    {
        foreach ([0, 6, -1, 10] as $rating) {
            $form = $this->factory->create(CommentType::class);
            $form->submit([
                'content' => 'Commentaire avec note invalide '.$rating,
                'rating' => $rating,
            ]);

            $this->assertFalse($form->isValid(), "Rating {$rating} should be invalid");
            $this->assertGreaterThanOrEqual(1, $form->get('rating')->getErrors(true)->count());
        }
    }

    public function testFormHasCorrectFields(): void
    {
        $form = $this->factory->create(CommentType::class);

        $this->assertTrue($form->has('content'));
        $this->assertTrue($form->has('rating'));
    }

    public function testContentFieldType(): void
    {
        $form = $this->factory->create(CommentType::class);
        $contentField = $form->get('content');

        $this->assertSame(
            'Symfony\Component\Form\Extension\Core\Type\TextareaType',
            get_class($contentField->getConfig()->getType()->getInnerType())
        );
    }

    public function testRatingFieldType(): void
    {
        $form = $this->factory->create(CommentType::class);
        $ratingField = $form->get('rating');

        $this->assertSame(
            'Symfony\Component\Form\Extension\Core\Type\ChoiceType',
            get_class($ratingField->getConfig()->getType()->getInnerType())
        );
    }
}
