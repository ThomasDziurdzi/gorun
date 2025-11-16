<?php

namespace App\Form;

use App\Enum\EventStatus;
use App\Enum\RunningLevel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventSearchType extends AbstractType
{
    private const BASE_INPUT_CLASS = 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('query', SearchType::class, [
                'label' => 'Rechercher',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Rechercher un événement...',
                    'class' => self::BASE_INPUT_CLASS,
                ],
            ])

            ->add('level', ChoiceType::class, [
                'label' => 'Niveau',
                'required' => false,
                'placeholder' => 'Tous les niveaux',
                'choices' => [
                    'Tous niveaux' => RunningLevel::ALL_LEVELS->value,
                    'Débutant' => RunningLevel::BEGINNER->value,
                    'Intermédiaire' => RunningLevel::INTERMEDIATE->value,
                    'Avancé' => RunningLevel::ADVANCED->value,
                ],
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                ],
            ])

            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'required' => false,
                'choices' => [
                    'Tous les statuts' => 'all',
                    'Publié' => EventStatus::PUBLISHED->value,
                    'Brouillon' => EventStatus::DRAFT->value,
                    'Annulé' => EventStatus::CANCELLED->value,
                    'Terminé' => EventStatus::COMPLETED->value,
                ],
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                ],
            ])

            ->add('dateFrom', DateType::class, [
                'label' => 'Date de début',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                ],
            ])

            ->add('dateTo', DateType::class, [
                'label' => 'Date de fin',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
