<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Event;
use App\Entity\Location;
use App\Entity\Notification;
use App\Entity\Registration;
use App\Entity\User;
use App\Enum\EventStatus;
use App\Enum\RegistrationStatus;
use App\Enum\RunningLevel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $faker;
    
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        $users = $this->createUsers($manager, 20);
        
        $locations = $this->createLocations($manager, $users, 15);
        
        $events = $this->createEvents($manager, $users, $locations, 30);
        
        $this->createRegistrations($manager, $users, $events);
        
        $this->createComments($manager, $users, $events);
        
        $this->createNotifications($manager, $users, $events);
        
        $manager->flush();
    }

    private function createUsers(ObjectManager $manager, int $count): array
    {
        $users = [];
        
        $admin = new User();
        $admin->setEmail('admin@test.com')
            ->setFirstname('Admin')
            ->setLastname('Test')
            ->setPassword($this->passwordHasher->hashPassword($admin, 'password'))
            ->setRoles(['ROLE_ADMIN', 'ROLE_USER'])
            ->setIsVerified(true)
            ->setCity('Paris')
            ->setRunningLevel(RunningLevel::ADVANCED)
            ->setPreferredPace('5:00 min/km')
            ->setBio('Administrateur de la plateforme')
            ->setPhoneNumber('0601020304')
            ->setBirthDate(new \DateTime('1985-05-15'))
            ->setLastLogin(new \DateTimeImmutable());
        
        $manager->persist($admin);
        $users[] = $admin;
        
        for ($i = 0; $i < $count; $i++) {
            $user = new User();
            $user->setEmail($this->faker->unique()->email())
                ->setFirstname($this->faker->firstName())
                ->setLastname($this->faker->lastName())
                ->setPassword($this->passwordHasher->hashPassword($user, 'password'))
                ->setIsVerified($this->faker->boolean(80))
                ->setCity($this->faker->city())
                ->setRunningLevel($this->faker->randomElement(RunningLevel::cases()))
                ->setPreferredPace($this->faker->randomElement(['5:00 min/km', '5:30 min/km', '6:00 min/km', '6:30 min/km']))
                ->setBio($this->faker->optional()->paragraph())
                ->setPhoneNumber($this->faker->optional()->phoneNumber())
                ->setBirthDate($this->faker->optional()->dateTimeBetween('-50 years', '-18 years'));
            
            if ($user->isVerified() && $this->faker->boolean(70)) {
                $user->setLastLogin(new \DateTimeImmutable($this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d H:i:s')));
            }
            
            $manager->persist($user);
            $users[] = $user;
        }
        
        return $users;
    }

    private function createLocations(ObjectManager $manager, array $users, int $count): array
    {
        $locations = [];
        $parisLocations = [
            ['Parc des Buttes-Chaumont', '1 Rue Botzaris', '75019', 'Paris', 48.8799, 2.3828],
            ['Bois de Vincennes', 'Route de la Pyramide', '75012', 'Paris', 48.8280, 2.4325],
            ['Jardin du Luxembourg', '6 Rue de Médicis', '75006', 'Paris', 48.8462, 2.3372],
            ['Parc Montsouris', '2 Rue Gazan', '75014', 'Paris', 48.8225, 2.3364],
            ['Promenade Plantée', '1 Coulée Verte René-Dumont', '75012', 'Paris', 48.8473, 2.3712],
        ];
        
        foreach ($parisLocations as $loc) {
            $location = new Location();
            $location->setLocationName($loc[0])
                ->setAddress($loc[1])
                ->setPostalCode($loc[2])
                ->setCity($loc[3])
                ->setCountry('France')
                ->setLatitude((string) $loc[4])
                ->setLongitude((string) $loc[5])
                ->setDescription($this->faker->optional()->paragraph())
                ->setMeetingPoint($this->faker->optional()->sentence())
                ->setCreationDate(new \DateTimeImmutable($this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d H:i:s')))
                ->setCreatedBy($this->faker->randomElement($users));
            
            $manager->persist($location);
            $locations[] = $location;
        }
        
        for ($i = count($parisLocations); $i < $count; $i++) {
            $location = new Location();
            $location->setLocationName($this->faker->streetName() . ' - ' . $this->faker->city())
                ->setAddress($this->faker->streetAddress())
                ->setPostalCode($this->faker->postcode())
                ->setCity($this->faker->city())
                ->setCountry('France')
                ->setLatitude((string) $this->faker->latitude(48.8, 48.9))
                ->setLongitude((string) $this->faker->longitude(2.2, 2.5))
                ->setDescription($this->faker->optional()->paragraph())
                ->setMeetingPoint($this->faker->optional()->sentence())
                ->setCreationDate(new \DateTimeImmutable($this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d H:i:s')))
                ->setCreatedBy($this->faker->randomElement($users));
            
            $manager->persist($location);
            $locations[] = $location;
        }
        
        return $locations;
    }

    private function createEvents(ObjectManager $manager, array $users, array $locations, int $count): array
    {
        $events = [];
        $eventTitles = [
            'Sortie matinale au parc',
            'Running du dimanche',
            'Préparation marathon',
            'Jogging découverte',
            'Trail urbain',
            'Sortie longue distance',
            'Course en groupe',
            'Entraînement fractionné',
        ];
        
        for ($i = 0; $i < $count; $i++) {
            $event = new Event();
            $creationDate = new \DateTimeImmutable($this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d H:i:s'));
            $eventDate = new \DateTimeImmutable($this->faker->dateTimeBetween('now', '+3 months')->format('Y-m-d H:i:s'));
            
            $status = $this->faker->randomElement([
                EventStatus::PUBLISHED,
                EventStatus::PUBLISHED,
                EventStatus::PUBLISHED,
                EventStatus::DRAFT,
                EventStatus::CANCELLED,
            ]);
            
            $event->setTitle($this->faker->randomElement($eventTitles) . ' ' . $this->faker->numberBetween(1, 100))
                ->setDescription($this->faker->paragraphs(3, true))
                ->setEventDate($eventDate)
                ->setEstimateDuration($this->faker->numberBetween(30, 180))
                ->setDistance((string) $this->faker->randomFloat(2, 5, 42))
                ->setMaxParticipants($this->faker->optional(0.7)->numberBetween(10, 50))
                ->setRequiredLevel([$this->faker->randomElement(RunningLevel::cases())])
                ->setPace($this->faker->randomElement(['5:00 min/km', '5:30 min/km', '6:00 min/km', '6:30 min/km', '7:00 min/km']))
                ->setStatus($status)
                ->setCreationDate($creationDate)
                ->setLocation($this->faker->randomElement($locations))
                ->setOrganizer($this->faker->randomElement($users));
            
            if ($this->faker->boolean(30)) {
                $updatedDate = new \DateTimeImmutable($this->faker->dateTimeBetween($creationDate->format('Y-m-d H:i:s'), 'now')->format('Y-m-d H:i:s'));
                $event->setUpdatedDate($updatedDate);
            }
            
            $manager->persist($event);
            $events[] = $event;
        }
        
        return $events;
    }

    private function createRegistrations(ObjectManager $manager, array $users, array $events): void
    {
        $processedPairs = [];
        
        foreach ($events as $event) {
            if ($event->getStatus() !== EventStatus::PUBLISHED) {
                continue;
            }
            
            $maxParticipants = $event->getMaxParticipants() ?? 50;
            $participantsCount = $this->faker->numberBetween(1, min($maxParticipants, 15));
            
            $selectedUsers = $this->faker->randomElements($users, $participantsCount);
            
            foreach ($selectedUsers as $user) {
                $pairKey = $user->getId() . '-' . $event->getId();
                if (isset($processedPairs[$pairKey])) {
                    continue;
                }
                $processedPairs[$pairKey] = true;
                
                $registration = new Registration();
                $registration->setUser($user)
                    ->setEvent($event)
                    ->setRegistrationDate(new \DateTimeImmutable($this->faker->dateTimeBetween('-2 months', 'now')->format('Y-m-d H:i:s')));
                
                if ($this->faker->boolean(10)) {
                    $registration->cancel();
                }
                
                $manager->persist($registration);
            }
        }
    }

    private function createComments(ObjectManager $manager, array $users, array $events): void
    {
        foreach ($events as $event) {
            if ($event->getStatus() !== EventStatus::PUBLISHED) {
                continue;
            }
            
            $commentsCount = $this->faker->numberBetween(0, 8);
            
            for ($i = 0; $i < $commentsCount; $i++) {
                $comment = new Comment();
                $publicationDate = new \DateTimeImmutable($this->faker->dateTimeBetween('-2 months', 'now')->format('Y-m-d H:i:s'));
                
                $comment->setContent($this->faker->paragraph())
                    ->setRating($this->faker->optional(0.8)->numberBetween(1, 5))
                    ->setPublicationDate($publicationDate)
                    ->setEvent($event)
                    ->setAuthor($this->faker->randomElement($users));
                
                if ($this->faker->boolean(20)) {
                    $updatedDate = new \DateTimeImmutable($this->faker->dateTimeBetween($publicationDate->format('Y-m-d H:i:s'), 'now')->format('Y-m-d H:i:s'));
                    $comment->setUpdatedDate($updatedDate);
                }
                
                $manager->persist($comment);
            }
        }
    }

    private function createNotifications(ObjectManager $manager, array $users, array $events): void
    {
        $notificationTypes = [
            ['Nouvel événement', 'Un nouvel événement de running a été publié près de chez vous !'],
            ['Rappel d\'événement', 'N\'oubliez pas votre événement de demain à {time} !'],
            ['Annulation', 'L\'événement auquel vous étiez inscrit a été annulé.'],
            ['Modification', 'L\'événement a été modifié. Consultez les détails.'],
            ['Nouveau commentaire', 'Un nouveau commentaire a été posté sur un événement.'],
        ];
        
        foreach ($users as $user) {
            $notifCount = $this->faker->numberBetween(0, 5);
            
            for ($i = 0; $i < $notifCount; $i++) {
                $notification = new Notification();
                $type = $this->faker->randomElement($notificationTypes);
                
                $notification->setTitle($type[0])
                    ->setMessage($type[1])
                    ->setSentDate(new \DateTimeImmutable($this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d H:i:s')))
                    ->setRecipient($user)
                    ->setEvent($this->faker->optional(0.8)->randomElement($events));
                
                $manager->persist($notification);
            }
        }
    }
}