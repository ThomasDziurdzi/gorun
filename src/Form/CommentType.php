<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Votre commentaire',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Partagez votre expérience ou posez une question...',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Le commentaire ne peut pas être vide.',
                    ),
                    new Length(
                        min: 1,
                        max: 1000,
                        minMessage: 'Votre commentaire doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Votre commentaire ne peut pas dépasser {{ limit }} caractères.',
                    ),
                ],
            ])
            ->add('rating', ChoiceType::class, [
                'label' => 'Note (optionnelle)',
                'required' => false,
                'placeholder' => 'Choisir une note',
                'choices' => [
                    '⭐ 1/5' => 1,
                    '⭐⭐ 2/5' => 2,
                    '⭐⭐⭐ 3/5' => 3,
                    '⭐⭐⭐⭐ 4/5' => 4,
                    '⭐⭐⭐⭐⭐ 5/5' => 5,
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500',
                ],
                'constraints' => [
                    new Range(
                        min: 1,
                        max: 5,
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
