#  GoRun - Plateforme de running collaboratif

![CI Pipeline](https://github.com/ThomasDziurdzi/gorun/actions/workflows/ci.yml/badge.svg)

Application web permettant aux coureurs de créer, découvrir et s'inscrire à des événements de course à pied.

##  Projet

Projet développé dans le cadre de la formation **Concepteur Développeur d'Applications (CDA)**.


##  Technologies utilisées

### Backend
- **PHP 8.4** avec **Symfony 7.2**
- **Doctrine ORM** pour la gestion de la base de données
- **Twig** pour le templating

### Frontend
- **Tailwind CSS** pour le design
- **Leaflet.js** pour les cartes interactives
- **Flatpickr** pour la sélection de dates

### Tests
- **PHPUnit 12.4**
- Tests unitaires
- Tests fonctionnels
- Base de données SQLite pour l'isolation des tests

## Installation

### Prérequis
- PHP 8.2 ou supérieur
- Composer
- Symfony CLI (optionnel)

```bash
# Cloner le repository
git clone git@github.com:ThomasDziurdzi/gorun.gitb
cd gorun

# Installer les dépendances
composer install

# Configurer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force

# Charger les données de test (optionnel)
php bin/console doctrine:fixtures:load

# Lancer le serveur
symfony serve
# ou
php -S localhost:8000 -t public
```

##  Tests
```bash
# Lancer tous les tests
./vendor/bin/phpunit

# Lancer uniquement les tests unitaires
./vendor/bin/phpunit tests/Unit

# Lancer uniquement les tests fonctionnels
./vendor/bin/phpunit tests/Functional

# Tests avec détails
./vendor/bin/phpunit --testdox
```


## Structure du projet
```
src/
├── Controller/      # Contrôleurs Symfony
├── Entity/          # Entités Doctrine
├── Form/            # Formulaires Symfony
├── Enum/            # Énumérations (statuts, niveaux)
├── Repository/      # Repositories Doctrine
└── DataFixtures/    # Fixtures pour données de test

tests/
├── Unit/            # Tests unitaires
└── Functional/      # Tests fonctionnels

templates/           # Templates Twig
public/              # Assets publics
```

## Sécurité

- Hashage des mots de passe avec bcrypt
- Protection CSRF sur tous les formulaires
- Contrôle d'accès basé sur les rôles (ROLE_USER, ROLE_ADMIN)
- Validation des données côté serveur
