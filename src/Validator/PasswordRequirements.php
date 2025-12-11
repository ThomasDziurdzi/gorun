<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraints as Assert;

class PasswordRequirements
{
    public static function getConstraints(bool $required = true): array
    {
        $constraints = [
            new Assert\Length([
                'min' => 12,
                'max' => 4096,
                'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                'maxMessage' => 'Le mot de passe ne peut pas dépasser {{ limit }} caractères',
            ]),
            new Assert\NotCompromisedPassword([
                'message' => 'Ce mot de passe est trop commun ou a été compromis. Choisissez-en un plus sécurisé.',
                'skipOnError' => true,
            ]),
            new Assert\Regex([
                'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])(?!.*\s).+$/',
                'message' => 'Le mot de passe doit contenir au moins : une majuscule, une minuscule, un chiffre et un caractère spécial (@$!%*?&#-_=+)',
            ]),
        ];

        if ($required) {
            array_unshift($constraints, new Assert\NotBlank([
                'message' => 'Le mot de passe ne peut pas être vide',
            ]));
        }

        return $constraints;
    }

    public static function getHelpMessage(): string
    {
        return 'Minimum 12 caractères avec au moins : une majuscule, une minuscule, un chiffre et un caractère spécial';
    }

    public static function getHtmlAttributes(): array
    {
        return [
            'minlength' => 12,
            'pattern' => '^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])(?!.*\s).{12,}$',
            'title' => self::getHelpMessage(),
        ];
    }
}
