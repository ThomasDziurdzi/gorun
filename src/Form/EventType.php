<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Location;
use App\Enum\EventStatus;
use App\Enum\RunningLevel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'événement',
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500',
                    'placeholder' => 'Ex: Sortie 10km - Forêt de la Robertsau'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire']),
                ]
            ])
            
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500',
                    'rows' => 5,
                    'placeholder' => 'Décrivez l\'événement, le parcours, l\'ambiance...'
                ]
            ])
            
            ->add('eventDate', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La date est obligatoire']),
                    new GreaterThan([
                        'value' => 'now',
                        'message' => 'La date doit être dans le futur'
                    ])
                ]
            ])
            
            ->add('estimateDuration', IntegerType::class, [
                'label' => 'Durée estimée (minutes)',
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500',
                    'placeholder' => '60'
                ],
                'constraints' => [
                    new Positive(['message' => 'La durée doit être positive'])
                ]
            ])
            
            ->add('distance', NumberType::class, [
                'label' => 'Distance (km)',
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500',
                    'placeholder' => '10.0',
                    'step' => '0.1'
                ],
                'constraints' => [
                    new Positive(['message' => 'La distance doit être positive'])
                ]
            ])
            
            ->add('maxParticipants', IntegerType::class, [
                'label' => 'Nombre maximum de participants',
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500',
                    'placeholder' => '20'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nombre de participants est obligatoire']),
                    new Positive(['message' => 'Doit être un nombre positif'])
                ]
            ])
            
            ->add('requiredLevel', ChoiceType::class, [
                'label' => 'Niveau(x) requis',
                'choices' => [
                    'Débutant' => RunningLevel::BEGINNER,
                    'Intermédiaire' => RunningLevel::INTERMEDIATE,
                    'Avancé' => RunningLevel::ADVANCED,
                    'Tous niveaux' => RunningLevel::ALL_LEVELS,
                ],
                'multiple' => true,
                'expanded' => true,
                'attr' => [
                    'class' => 'space-y-2'
                ]
            ])
            
            ->add('pace', TextType::class, [
                'label' => 'Allure prévue',
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500',
                    'placeholder' => 'Ex: 5\'30/km'
                ]
            ])
            
            ->add('location', LocationType::class, [
                'label' => false,
            ])
            
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Brouillon' => EventStatus::DRAFT,
                    'Publié' => EventStatus::PUBLISHED,
                    'Annulé' => EventStatus::CANCELLED,
                    'Terminé' => EventStatus::COMPLETED,
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'event_form',
        ]);
    }
}