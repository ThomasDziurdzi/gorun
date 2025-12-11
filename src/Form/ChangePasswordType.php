<?php

namespace App\Form;

use App\Validator\PasswordRequirements;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordType extends AbstractType
{
    private const BASE_INPUT_CLASS = 'w-full px-4 py-3 rounded-2xl border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-transparent';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'mapped' => false,
                'attr' => [
                    'class' => self::BASE_INPUT_CLASS,
                    'placeholder' => 'Votre mot de passe actuel',
                    'autocomplete' => 'current-password',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez entrer votre mot de passe actuel',
                    ]),
                ],
            ])

           ->add('newPassword', RepeatedType::class, [
               'type' => PasswordType::class,
               'mapped' => false,
               'first_options' => [
                   'label' => 'Nouveau mot de passe',
                   'attr' => array_merge([
                       'class' => self::BASE_INPUT_CLASS,
                       'placeholder' => 'Nouveau mot de passe',
                       'autocomplete' => 'new-password',
                   ], PasswordRequirements::getHtmlAttributes()),
                   'help' => PasswordRequirements::getHelpMessage(),
                   'help_attr' => [
                       'class' => 'text-sm text-gray-600 mt-1',
                   ],
               ],
               'second_options' => [
                   'label' => 'Confirmer le nouveau mot de passe',
                   'attr' => [
                       'class' => self::BASE_INPUT_CLASS,
                       'placeholder' => 'Confirmer le mot de passe',
                       'autocomplete' => 'new-password',
                   ],
               ],
               'invalid_message' => 'Les deux mots de passe doivent être identiques',
               'constraints' => PasswordRequirements::getConstraints(required: true),
           ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}
