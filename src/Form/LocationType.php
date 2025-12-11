<?php

namespace App\Form;

use App\Entity\Location;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class LocationType extends AbstractType
{
    private const BASE_INPUT_CLASS = 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('locationName', TextType::class, [
                'label' => 'Nom du lieu',
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                    'placeholder' => 'Ex: Parc de l\'Orangerie',
                ],
                'constraints' => [
                    new NotBlank(message: 'Le nom du lieu est obligatoire'),
                ],
            ])

            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                    'placeholder' => 'Avenue de l\'Europe (ou cliquez sur la carte)',
                    'id' => 'location_address',
                ],
            ])

            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                    'placeholder' => '67000',
                ],
            ])

            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                    'placeholder' => 'Strasbourg',
                ],
                'constraints' => [
                    new NotBlank(message: 'La ville est obligatoire'),
                ],
            ])

            ->add('country', TextType::class, [
                'label' => 'Pays',
                'required' => false,
                'data' => 'France',
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                ],
            ])

            ->add('latitude', HiddenType::class, [
                'attr' => ['id' => 'location_latitude'],
            ])

            ->add('longitude', HiddenType::class, [
                'attr' => ['id' => 'location_longitude'],
            ])

            ->add('meetingPoint', TextType::class, [
                'label' => 'Point de RDV précis',
                'required' => false,
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                    'placeholder' => 'Ex: Devant la fontaine',
                ],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description du lieu',
                'required' => false,
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                    'rows' => 3,
                    'placeholder' => 'Indications complémentaires...',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
            'csrf_protection' => true,
            'constraints' => [
                new Callback(function (Location $location, ExecutionContextInterface $context) {
                    $hasAddress = !empty($location->getAddress());
                    $hasCoords = !empty($location->getLatitude()) && !empty($location->getLongitude());

                    if (!$hasAddress && !$hasCoords) {
                        $context->buildViolation('Vous devez soit saisir une adresse, soit placer un point sur la carte.')
                            ->atPath('address')
                            ->addViolation();
                    }

                    if (!$hasCoords) {
                        $context->buildViolation('Veuillez valider la position sur la carte en cliquant sur "Géocoder" ou en cliquant directement sur la carte.')
                            ->atPath('latitude')
                            ->addViolation();
                    }
                }),
            ],
        ]);
    }
}
