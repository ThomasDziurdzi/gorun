<?php

namespace App\Form;

use App\Entity\User;
use App\Validator\PasswordRequirements;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    private const BASE_INPUT_CLASS = 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                    'placeholder' => 'Camille',
                ],
                'constraints' => [
                    new NotBlank(message: 'Le prénom est obligatoire'),
                    new Length(
                        min: 2,
                        max: 100,
                        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères',
                        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères'
                    ),
                ],
            ])

            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                    'placeholder' => 'Runner',
                ],
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire'),
                    new Length(
                        min: 2,
                        max: 100,
                        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
                        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ),
                ],
            ])

            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                    'placeholder' => 'vous@exemple.com',
                    'autocomplete' => 'email',
                    'inputmode' => 'email',
                ],
                'constraints' => [
                    new NotBlank(message: 'L\'email est obligatoire'),
                    new Email(
                        mode: Email::VALIDATION_MODE_STRICT,
                        message: 'L\'adresse email "{{ value }}" n\'est pas valide.'
                    ),
                    new Length(
                        max: 180,
                        maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères'
                    ),
                ],
            ])

            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => array_merge([
                        'class' => self::BASE_INPUT_CLASS,
                        'autocomplete' => 'new-password',
                    ], PasswordRequirements::getHtmlAttributes()),
                    'help' => PasswordRequirements::getHelpMessage(),
                    'help_attr' => [
                        'class' => 'text-sm text-gray-600 mt-1',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => [
                        'class' => self::BASE_INPUT_CLASS,
                        'autocomplete' => 'new-password',
                    ],
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'constraints' => PasswordRequirements::getConstraints(required: true),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
