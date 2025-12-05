<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\Registration;
use App\Enum\RegistrationStatus;
use App\Repository\RegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'api_registrations_')]
#[OA\Tag(name: 'Registrations', description: 'Gestion des inscriptions aux événements')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class RegistrationApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private RegistrationRepository $registrationRepository
    ) {
    }

    #[Route('/events/{eventId}/register', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/events/{eventId}/register',
        summary: 'S\'inscrire à un événement',
        description: 'Inscrit l\'utilisateur connecté à un événement',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'eventId',
                in: 'path',
                description: 'ID de l\'événement',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Inscription réussie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Successfully registered'),
                        new OA\Property(
                            property: 'registration',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'status', type: 'string', example: 'CONFIRMED'),
                                new OA\Property(property: 'registrationDate', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Déjà inscrit ou événement complet'
            ),
            new OA\Response(
                response: 404,
                description: 'Événement non trouvé'
            ),
        ]
    )]
    public function register(int $eventId): JsonResponse
    {
        $event = $this->em->getRepository(Event::class)->find($eventId);

        if (!$event) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();

        // Vérifier si déjà inscrit
        $existingRegistration = $this->registrationRepository->findOneBy([
            'user' => $user,
            'event' => $event,
            'status' => RegistrationStatus::CONFIRMED,
        ]);

        if ($existingRegistration) {
            return $this->json(
                ['error' => 'Already registered to this event'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Vérifier si l'événement est complet
        if ($event->isFull()) {
            return $this->json(
                ['error' => 'Event is full'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $registration = new Registration();
        $registration->setUser($user);
        $registration->setEvent($event);
        $registration->setStatus(RegistrationStatus::CONFIRMED);

        $this->em->persist($registration);
        $this->em->flush();

        return $this->json([
            'message' => 'Successfully registered',
            'registration' => [
                'id' => $registration->getId(),
                'status' => $registration->getStatus()->value,
                'registrationDate' => $registration->getRegistrationDate()->format('c'),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/events/{eventId}/unregister', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/events/{eventId}/unregister',
        summary: 'Se désinscrire d\'un événement',
        description: 'Annule l\'inscription de l\'utilisateur à un événement',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'eventId',
                in: 'path',
                description: 'ID de l\'événement',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Désinscription réussie'
            ),
            new OA\Response(
                response: 404,
                description: 'Inscription non trouvée'
            ),
        ]
    )]
    public function unregister(int $eventId): JsonResponse
    {
        $event = $this->em->getRepository(Event::class)->find($eventId);

        if (!$event) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();

        $registration = $this->registrationRepository->findOneBy([
            'user' => $user,
            'event' => $event,
            'status' => RegistrationStatus::CONFIRMED,
        ]);

        if (!$registration) {
            return $this->json(
                ['error' => 'Registration not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $this->em->remove($registration);
        $this->em->flush();

        return $this->json(['message' => 'Successfully unregistered']);
    }

    #[Route('/my-registrations', name: 'my_list', methods: ['GET'])]
#[OA\Get(
    path: '/api/my-registrations',
    summary: 'Mes inscriptions',
    description: 'Récupère toutes les inscriptions de l\'utilisateur connecté',
    security: [['Bearer' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Liste des inscriptions',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'upcoming',
                        type: 'array',
                        description: 'Inscriptions aux événements à venir',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(
                                    property: 'status',
                                    type: 'string',
                                    enum: ['PENDING', 'CONFIRMED', 'CANCELLED'],
                                    example: 'CONFIRMED'
                                ),
                                new OA\Property(
                                    property: 'registrationDate',
                                    type: 'string',
                                    format: 'date-time',
                                    example: '2025-12-01T10:00:00+01:00'
                                ),
                                new OA\Property(
                                    property: 'event',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'title', type: 'string', example: 'Course 10K Paris'),
                                        new OA\Property(
                                            property: 'eventDate',
                                            type: 'string',
                                            format: 'date-time',
                                            example: '2025-12-15T10:00:00+01:00'
                                        ),
                                        new OA\Property(property: 'distance', type: 'string', example: '10.0'),
                                        new OA\Property(
                                            property: 'location',
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'city', type: 'string', example: 'Paris'),
                                                new OA\Property(property: 'name', type: 'string', example: 'Parc des Buttes-Chaumont'),
                                            ]
                                        ),
                                    ]
                                ),
                            ]
                        )
                    ),
                    new OA\Property(
                        property: 'past',
                        type: 'array',
                        description: 'Inscriptions aux événements passés',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 2),
                                new OA\Property(property: 'status', type: 'string', example: 'CONFIRMED'),
                                new OA\Property(property: 'registrationDate', type: 'string', format: 'date-time'),
                                new OA\Property(
                                    property: 'event',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer'),
                                        new OA\Property(property: 'title', type: 'string'),
                                        new OA\Property(property: 'eventDate', type: 'string', format: 'date-time'),
                                        new OA\Property(property: 'distance', type: 'string'),
                                        new OA\Property(
                                            property: 'location',
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'city', type: 'string'),
                                                new OA\Property(property: 'name', type: 'string'),
                                            ]
                                        ),
                                    ]
                                ),
                            ]
                        )
                    ),
                ]
            )
        ),
    ]
)]
public function myRegistrations(): JsonResponse
{
    $user = $this->getUser();

    $upcoming = $this->registrationRepository->findUpcomingByUser($user);
    $past = $this->registrationRepository->findPastByUser($user);

    return $this->json([
        'upcoming' => array_map(fn($r) => $this->serializeRegistration($r), $upcoming),
        'past' => array_map(fn($r) => $this->serializeRegistration($r), $past),
    ]);
}

    private function serializeRegistration(Registration $registration): array
    {
        $event = $registration->getEvent();

        return [
            'id' => $registration->getId(),
            'status' => $registration->getStatus()->value,
            'registrationDate' => $registration->getRegistrationDate()->format('c'),
            'event' => [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'eventDate' => $event->getEventDate()->format('c'),
                'distance' => $event->getDistance(),
                'location' => [
                    'city' => $event->getLocation()->getCity(),
                    'name' => $event->getLocation()->getLocationName(),
                ],
            ],
        ];
    }
}