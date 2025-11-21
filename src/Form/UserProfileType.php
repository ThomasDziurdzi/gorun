<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\RunningLevel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserProfileType extends AbstractType
{
    private const BASE_INPUT_CLASS = 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-transparent';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                    'placeholder' => 'Votre prénom',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prénom est obligatoire']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le prénom doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])

            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                    'placeholder' => 'Votre nom',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le nom doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])

            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                    'placeholder' => 'votre@email.com',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'email est obligatoire']),
                    new Assert\Email(['message' => 'L\'email {{ value }} n\'est pas valide']),
                ],
            ])

            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                    'placeholder' => 'Votre ville',
                ],
            ])

            ->add('phoneNumber', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                    'placeholder' => '06 12 34 56 78',
                ],
            ])

            ->add('birthDate', DateType::class, [
                'label' => 'Date de naissance',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                ],
            ])

            ->add('runningLevel', ChoiceType::class, [
                'label' => 'Niveau',
                'choices' => [
                    'Tous niveaux' => RunningLevel::ALL_LEVELS,
                    'Débutant' => RunningLevel::BEGINNER,
                    'Intermédiaire' => RunningLevel::INTERMEDIATE,
                    'Avancé' => RunningLevel::ADVANCED,
                ],
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                ],
            ])

            ->add('preferredPace', TextType::class, [
                'label' => 'Allure préférée',
                'required' => false,
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                    'placeholder' => '5:30 min/km',
                ],
                'help' => 'Format: X:XX min/km',
            ])

            ->add('bio', TextareaType::class, [
                'label' => 'Bio',
                'required' => false,
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                    'rows' => 4,
                    'placeholder' => 'Parlez de vous, de vos objectifs running...',
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 500,
                        'maxMessage' => 'La bio ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
