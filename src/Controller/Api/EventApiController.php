<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\Location;
use App\Enum\EventStatus;
use App\Enum\RunningLevel;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/events', name: 'api_events_')]
class EventApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
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
            'data' => array_map(fn(Event $e) => $this->serializeEvent($e), $events),
            'pagination' => $this->buildPaginationResponse($pagination, $total)
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Event $event): JsonResponse
    {
        return $this->json($this->serializeEvent($event, true));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
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
    public function delete(Event $event): JsonResponse
    {
        $this->em->remove($event);
        $this->em->flush();

        return $this->json(['message' => 'Event deleted successfully'], Response::HTTP_NO_CONTENT);
    }

    private function extractPaginationParams(Request $request): array
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 10)));
        
        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => ($page - 1) * $limit
        ];
    }

    private function extractFilters(Request $request): array
    {
        return [
            'status' => $request->query->get('status'),
            'level' => $request->query->get('level')
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
            'pages' => ceil($total / $pagination['limit'])
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
            'title' => fn($v) => $event->setTitle($v),
            'description' => fn($v) => $event->setDescription($v),
            'distance' => fn($v) => $event->setDistance((string) $v),
            'estimateDuration' => fn($v) => $event->setEstimateDuration($v),
            'maxParticipants' => fn($v) => $event->setMaxParticipants($v),
            'pace' => fn($v) => $event->setPace($v),
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
            'locationName' => fn($v) => $location->setLocationName($v),
            'address' => fn($v) => $location->setAddress($v),
            'city' => fn($v) => $location->setCity($v),
            'postalCode' => fn($v) => $location->setPostalCode($v),
            'latitude' => fn($v) => $location->setLatitude((string) $v),
            'longitude' => fn($v) => $location->setLongitude((string) $v),
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