<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\Location;
use App\Enum\EventStatus;
use App\Enum\RunningLevel;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/events', name: 'api_events_')]
#[OA\Tag(name: 'Events', description: 'Gestion des événements de running')]
class EventApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/events',
        summary: 'Lister tous les événements',
        description: 'Récupère la liste paginée des événements avec filtres optionnels',
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Numéro de page',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Nombre d\'éléments par page',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10, minimum: 1, maximum: 100)
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                description: 'Filtrer par statut',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['DRAFT', 'PUBLISHED', 'CANCELLED', 'COMPLETED']
                )
            ),
            new OA\Parameter(
                name: 'level',
                in: 'query',
                description: 'Filtrer par niveau requis',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['ALL_LEVELS', 'BEGINNER', 'INTERMEDIATE', 'ADVANCED']
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des événements',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'title', type: 'string', example: 'Course 10K Paris'),
                                    new OA\Property(property: 'description', type: 'string', example: 'Belle sortie running dans Paris'),
                                    new OA\Property(property: 'eventDate', type: 'string', format: 'date-time', example: '2025-06-15T10:00:00+02:00'),
                                    new OA\Property(property: 'distance', type: 'string', example: '10.0'),
                                    new OA\Property(property: 'maxParticipants', type: 'integer', example: 20),
                                    new OA\Property(property: 'status', type: 'string', example: 'PUBLISHED'),
                                    new OA\Property(property: 'requiredLevel', type: 'string', example: 'INTERMEDIATE'),
                                    new OA\Property(
                                        property: 'location',
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'name', type: 'string', example: 'Parc des Buttes-Chaumont'),
                                            new OA\Property(property: 'city', type: 'string', example: 'Paris'),
                                            new OA\Property(property: 'latitude', type: 'string', example: '48.8799'),
                                            new OA\Property(property: 'longitude', type: 'string', example: '2.3828'),
                                        ]
                                    ),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'pagination',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'page', type: 'integer', example: 1),
                                new OA\Property(property: 'limit', type: 'integer', example: 10),
                                new OA\Property(property: 'total', type: 'integer', example: 42),
                                new OA\Property(property: 'pages', type: 'integer', example: 5),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function list(EventRepository $eventRepository, Request $request): JsonResponse
    {
        $pagination = $this->extractPaginationParams($request);
        $filters = $this->extractFilters($request);

        $qb = $this->buildFilteredQuery($eventRepository, $filters);

        $total = count($qb->getQuery()->getResult());

        $events = $qb->setFirstResult($pagination['offset'])
                    ->setMaxResults($pagination['limit'])
                    ->getQuery()
                    ->getResult();

        return $this->json([
            'data' => array_map(fn (Event $e) => $this->serializeEvent($e), $events),
            'pagination' => $this->buildPaginationResponse($pagination, $total),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/events/{id}',
        summary: 'Récupérer un événement',
        description: 'Récupère les détails complets d\'un événement spécifique',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID de l\'événement',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détails de l\'événement',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'title', type: 'string', example: 'Course 10K Paris'),
                        new OA\Property(property: 'description', type: 'string', example: 'Belle sortie running'),
                        new OA\Property(property: 'eventDate', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'distance', type: 'string', example: '10.0'),
                        new OA\Property(property: 'estimateDuration', type: 'integer', example: 60),
                        new OA\Property(property: 'pace', type: 'string', example: '5:30 min/km'),
                        new OA\Property(property: 'maxParticipants', type: 'integer', example: 20),
                        new OA\Property(property: 'spotsLeft', type: 'integer', example: 15),
                        new OA\Property(property: 'status', type: 'string', example: 'PUBLISHED'),
                        new OA\Property(property: 'requiredLevel', type: 'string', example: 'INTERMEDIATE'),
                        new OA\Property(property: 'creationDate', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updatedDate', type: 'string', format: 'date-time', nullable: true),
                        new OA\Property(
                            property: 'organizer',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'firstname', type: 'string', example: 'John'),
                                new OA\Property(property: 'lastname', type: 'string', example: 'Doe'),
                            ]
                        ),
                        new OA\Property(
                            property: 'location',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'address', type: 'string'),
                                new OA\Property(property: 'city', type: 'string'),
                                new OA\Property(property: 'postalCode', type: 'string'),
                                new OA\Property(property: 'latitude', type: 'string'),
                                new OA\Property(property: 'longitude', type: 'string'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Événement non trouvé'
            ),
        ]
    )]
    public function show(Event $event): JsonResponse
    {
        return $this->json($this->serializeEvent($event, true));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Post(
        path: '/api/events',
        summary: 'Créer un événement (Admin)',
        description: 'Crée un nouvel événement de running. Nécessite le rôle ADMIN.',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'eventDate', 'location'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Course 10K Paris'),
                    new OA\Property(property: 'description', type: 'string', example: 'Belle sortie running'),
                    new OA\Property(property: 'eventDate', type: 'string', format: 'date-time', example: '2025-06-15T10:00:00+02:00'),
                    new OA\Property(property: 'distance', type: 'number', format: 'float', example: 10.0),
                    new OA\Property(property: 'estimateDuration', type: 'integer', example: 60),
                    new OA\Property(property: 'maxParticipants', type: 'integer', example: 20),
                    new OA\Property(property: 'pace', type: 'string', example: '5:30 min/km'),
                    new OA\Property(property: 'status', type: 'string', enum: ['DRAFT', 'PUBLISHED'], example: 'PUBLISHED'),
                    new OA\Property(property: 'requiredLevel', type: 'string', enum: ['ALL_LEVELS', 'BEGINNER', 'INTERMEDIATE', 'ADVANCED'], example: 'INTERMEDIATE'),
                    new OA\Property(
                        property: 'location',
                        type: 'object',
                        required: ['locationName', 'address', 'city', 'latitude', 'longitude'],
                        properties: [
                            new OA\Property(property: 'locationName', type: 'string', example: 'Parc des Buttes-Chaumont'),
                            new OA\Property(property: 'address', type: 'string', example: '1 Rue Botzaris'),
                            new OA\Property(property: 'city', type: 'string', example: 'Paris'),
                            new OA\Property(property: 'postalCode', type: 'string', example: '75019'),
                            new OA\Property(property: 'latitude', type: 'string', example: '48.8799'),
                            new OA\Property(property: 'longitude', type: 'string', example: '2.3828'),
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Événement créé avec succès'
            ),
            new OA\Response(
                response: 400,
                description: 'Données invalides'
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié'
            ),
            new OA\Response(
                response: 403,
                description: 'Accès refusé - rôle ADMIN requis'
            ),
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $event = new Event();
        $location = new Location();

        $this->hydrateEvent($event, $location, $data);

        $errors = $this->validator->validate($event);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->em->persist($location);
        $this->em->persist($event);
        $this->em->flush();

        return $this->json(
            $this->serializeEvent($event, true),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(
        path: '/api/events/{id}',
        summary: 'Modifier un événement (Admin)',
        description: 'Met à jour un événement existant. Nécessite le rôle ADMIN.',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID de l\'événement',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Course 10K Paris - Modifié'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'eventDate', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'distance', type: 'number', format: 'float'),
                    new OA\Property(property: 'estimateDuration', type: 'integer'),
                    new OA\Property(property: 'maxParticipants', type: 'integer'),
                    new OA\Property(property: 'pace', type: 'string'),
                    new OA\Property(
                        property: 'status',
                        type: 'string',
                        enum: ['DRAFT', 'PUBLISHED', 'CANCELLED', 'COMPLETED'],
                        example: 'PUBLISHED'
                    ),
                    new OA\Property(
                        property: 'requiredLevel',
                        type: 'string',
                        enum: ['ALL_LEVELS', 'BEGINNER', 'INTERMEDIATE', 'ADVANCED'],
                        example: 'INTERMEDIATE'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Événement modifié avec succès'
            ),
            new OA\Response(
                response: 400,
                description: 'Données invalides'
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié'
            ),
            new OA\Response(
                response: 403,
                description: 'Accès refusé - rôle ADMIN requis'
            ),
            new OA\Response(
                response: 404,
                description: 'Événement non trouvé'
            ),
        ]
    )]
    public function update(Event $event, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $location = $event->getLocation();
        $this->hydrateEvent($event, $location, $data);
        $event->setUpdatedDate(new \DateTimeImmutable());

        $errors = $this->validator->validate($event);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->em->flush();

        return $this->json($this->serializeEvent($event, true));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(
        path: '/api/events/{id}',
        summary: 'Supprimer un événement (Admin)',
        description: 'Supprime définitivement un événement. Nécessite le rôle ADMIN.',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID de l\'événement',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Événement supprimé avec succès'
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié'
            ),
            new OA\Response(
                response: 403,
                description: 'Accès refusé - rôle ADMIN requis'
            ),
            new OA\Response(
                response: 404,
                description: 'Événement non trouvé'
            ),
        ]
    )]
    public function delete(Event $event): JsonResponse
    {
        $this->em->remove($event);
        $this->em->flush();

        return $this->json(['message' => 'Event deleted successfully'], Response::HTTP_NO_CONTENT);
    }

    // Toutes les méthodes privées restent identiques...
    private function extractPaginationParams(Request $request): array
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 10)));

        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => ($page - 1) * $limit,
        ];
    }

    private function extractFilters(Request $request): array
    {
        return [
            'status' => $request->query->get('status'),
            'level' => $request->query->get('level'),
        ];
    }

    private function buildFilteredQuery(EventRepository $repository, array $filters)
    {
        $qb = $repository->createQueryBuilder('e')
            ->orderBy('e.eventDate', 'DESC');

        if ($filters['status']) {
            $qb->andWhere('e.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if ($filters['level']) {
            $qb->andWhere('e.requiredLevel = :level')
               ->setParameter('level', $filters['level']);
        }

        return $qb;
    }

    private function buildPaginationResponse(array $pagination, int $total): array
    {
        return [
            'page' => $pagination['page'],
            'limit' => $pagination['limit'],
            'total' => $total,
            'pages' => ceil($total / $pagination['limit']),
        ];
    }

    private function serializeEvent(Event $event, bool $detailed = false): array
    {
        $data = [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'description' => $event->getDescription(),
            'eventDate' => $event->getEventDate()->format('c'),
            'distance' => $event->getDistance(),
            'maxParticipants' => $event->getMaxParticipants(),
            'status' => $event->getStatus()->value,
            'requiredLevel' => $event->getRequiredLevel()->value,
            'location' => $this->serializeLocation($event->getLocation(), false),
        ];

        if ($detailed) {
            $data = array_merge($data, $this->getDetailedEventData($event));
        }

        return $data;
    }

    private function getDetailedEventData(Event $event): array
    {
        return [
            'estimateDuration' => $event->getEstimateDuration(),
            'pace' => $event->getPace(),
            'creationDate' => $event->getCreationDate()->format('c'),
            'updatedDate' => $event->getUpdatedDate()?->format('c'),
            'organizer' => [
                'id' => $event->getOrganizer()->getId(),
                'firstname' => $event->getOrganizer()->getFirstname(),
                'lastname' => $event->getOrganizer()->getLastname(),
            ],
            'location' => $this->serializeLocation($event->getLocation(), true),
            'spotsLeft' => $event->getSpotsLeft(),
        ];
    }

    private function serializeLocation(Location $location, bool $detailed): array
    {
        $data = [
            'id' => $location->getId(),
            'name' => $location->getLocationName(),
            'city' => $location->getCity(),
            'latitude' => $location->getLatitude(),
            'longitude' => $location->getLongitude(),
        ];

        if ($detailed) {
            $data['address'] = $location->getAddress();
            $data['postalCode'] = $location->getPostalCode();
        }

        return $data;
    }

    private function hydrateEvent(Event $event, Location $location, array $data): void
    {
        $this->hydrateEventBasicData($event, $data);
        $this->hydrateLocation($location, $data['location'] ?? []);
        $this->setEventRelations($event, $location);
    }

    private function hydrateEventBasicData(Event $event, array $data): void
    {
        $basicFields = [
            'title' => fn ($v) => $event->setTitle($v),
            'description' => fn ($v) => $event->setDescription($v),
            'distance' => fn ($v) => $event->setDistance((string) $v),
            'estimateDuration' => fn ($v) => $event->setEstimateDuration($v),
            'maxParticipants' => fn ($v) => $event->setMaxParticipants($v),
            'pace' => fn ($v) => $event->setPace($v),
        ];

        foreach ($basicFields as $field => $setter) {
            if (isset($data[$field])) {
                $setter($data[$field]);
            }
        }

        if (isset($data['eventDate'])) {
            $event->setEventDate(new \DateTimeImmutable($data['eventDate']));
        }

        if (isset($data['status'])) {
            $event->setStatus(EventStatus::from($data['status']));
        }

        if (isset($data['requiredLevel'])) {
            $event->setRequiredLevel(RunningLevel::from($data['requiredLevel']));
        }
    }

    private function hydrateLocation(Location $location, array $locData): void
    {
        $locationFields = [
            'locationName' => fn ($v) => $location->setLocationName($v),
            'address' => fn ($v) => $location->setAddress($v),
            'city' => fn ($v) => $location->setCity($v),
            'postalCode' => fn ($v) => $location->setPostalCode($v),
            'latitude' => fn ($v) => $location->setLatitude((string) $v),
            'longitude' => fn ($v) => $location->setLongitude((string) $v),
        ];

        foreach ($locationFields as $field => $setter) {
            if (isset($locData[$field])) {
                $setter($locData[$field]);
            }
        }
    }

    private function setEventRelations(Event $event, Location $location): void
    {
        if (!$event->getLocation()) {
            $location->setCreatedBy($this->getUser());
            $event->setLocation($location);
        }

        if (!$event->getOrganizer()) {
            $event->setOrganizer($this->getUser());
        }
    }

    private function formatErrors($errors): array
    {
        $formatted = [];
        foreach ($errors as $error) {
            $formatted[] = [
                'field' => $error->getPropertyPath(),
                'message' => $error->getMessage(),
            ];
        }

        return $formatted;
    }
}
