<?php

namespace App\Form;

use App\Entity\Location;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class LocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('locationName', TextType::class, [
                'label' => 'Nom du lieu',
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500',
                    'placeholder' => 'Ex: Parc de l\'Orangerie'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom du lieu est obligatoire'])
                ]
            ])
            
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500',
                    'placeholder' => 'Avenue de l\'Europe',
                    'id' => 'location_address'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'L\'adresse est obligatoire'])
                ]
            ])
            
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500',
                    'placeholder' => '67000'
                ]
            ])
            
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500',
                    'placeholder' => 'Strasbourg'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La ville est obligatoire'])
                ]
            ])
            
            ->add('country', TextType::class, [
                'label' => 'Pays',
                'required' => false,
                'data' => 'France', 
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500'
                ]
            ])
            
          
            ->add('latitude', HiddenType::class, [
                'attr' => ['id' => 'location_latitude'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez placer un point sur la carte'])
                ]
            ])
            
            ->add('longitude', HiddenType::class, [
                'attr' => ['id' => 'location_longitude'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez placer un point sur la carte'])
                ]
            ])
            
            ->add('meetingPoint', TextType::class, [
                'label' => 'Point de RDV précis',
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500',
                    'placeholder' => 'Ex: Devant la fontaine'
                ]
            ])
            
            ->add('description', TextareaType::class, [
                'label' => 'Description du lieu',
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500',
                    'rows' => 3,
                    'placeholder' => 'Indications complémentaires...'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
            'csrf_protection' => true,
        ]);
    }
}