<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Entity\Event;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_comments_')]
#[OA\Tag(name: 'Comments', description: 'Gestion des commentaires sur les événements')]
class CommentApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/events/{eventId}/comments', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/events/{eventId}/comments',
        summary: 'Lister les commentaires d\'un événement',
        description: 'Récupère tous les commentaires d\'un événement spécifique',
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
                description: 'Liste des commentaires',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'content', type: 'string', example: 'Super sortie !'),
                            new OA\Property(property: 'rating', type: 'integer', example: 5, nullable: true),
                            new OA\Property(property: 'publicationDate', type: 'string', format: 'date-time'),
                            new OA\Property(property: 'updatedDate', type: 'string', format: 'date-time', nullable: true),
                            new OA\Property(
                                property: 'author',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'firstname', type: 'string'),
                                    new OA\Property(property: 'lastname', type: 'string'),
                                ]
                            ),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Événement non trouvé'
            ),
        ]
    )]
    public function list(int $eventId, CommentRepository $commentRepository): JsonResponse
    {
        $event = $this->em->getRepository(Event::class)->find($eventId);

        if (!$event) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        $comments = $commentRepository->findBy(
            ['event' => $event],
            ['publicationDate' => 'DESC']
        );

        return $this->json(
            array_map(fn (Comment $c) => $this->serializeComment($c), $comments)
        );
    }

    #[Route('/events/{eventId}/comments', name: 'create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Post(
        path: '/api/events/{eventId}/comments',
        summary: 'Ajouter un commentaire',
        description: 'Ajoute un nouveau commentaire sur un événement. Authentification requise.',
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
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(
                        property: 'content',
                        type: 'string',
                        minLength: 1,
                        maxLength: 1000,
                        example: 'Excellent événement, très bien organisé !'
                    ),
                    new OA\Property(
                        property: 'rating',
                        type: 'integer',
                        minimum: 1,
                        maximum: 5,
                        example: 5,
                        nullable: true,
                        description: 'Note entre 1 et 5 (optionnel)'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Commentaire créé avec succès'
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
                response: 404,
                description: 'Événement non trouvé'
            ),
        ]
    )]
    public function create(int $eventId, Request $request): JsonResponse
    {
        $event = $this->em->getRepository(Event::class)->find($eventId);

        if (!$event) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['content'])) {
            return $this->json(['error' => 'Content is required'], Response::HTTP_BAD_REQUEST);
        }

        $comment = new Comment();
        $comment->setContent($data['content']);
        $comment->setEvent($event);
        $comment->setAuthor($this->getUser());

        if (isset($data['rating'])) {
            $comment->setRating($data['rating']);
        }

        $errors = $this->validator->validate($comment);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->em->persist($comment);
        $this->em->flush();

        return $this->json(
            $this->serializeComment($comment),
            Response::HTTP_CREATED
        );
    }

    #[Route('/comments/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Put(
        path: '/api/comments/{id}',
        summary: 'Modifier son commentaire',
        description: 'Modifie un commentaire. L\'utilisateur ne peut modifier que ses propres commentaires.',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID du commentaire',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'content', type: 'string', example: 'Commentaire modifié'),
                    new OA\Property(property: 'rating', type: 'integer', minimum: 1, maximum: 5, nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Commentaire modifié avec succès'
            ),
            new OA\Response(
                response: 403,
                description: 'Vous ne pouvez pas modifier ce commentaire'
            ),
            new OA\Response(
                response: 404,
                description: 'Commentaire non trouvé'
            ),
        ]
    )]
    public function update(Comment $comment, Request $request): JsonResponse
    {
        if ($comment->getAuthor() !== $this->getUser()) {
            return $this->json(
                ['error' => 'You can only edit your own comments'],
                Response::HTTP_FORBIDDEN
            );
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['content'])) {
            $comment->setContent($data['content']);
        }

        if (isset($data['rating'])) {
            $comment->setRating($data['rating']);
        }

        $comment->setUpdatedDate(new \DateTimeImmutable());

        $errors = $this->validator->validate($comment);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->em->flush();

        return $this->json($this->serializeComment($comment));
    }

    #[Route('/comments/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Delete(
        path: '/api/comments/{id}',
        summary: 'Supprimer un commentaire',
        description: 'Supprime un commentaire. L\'utilisateur peut supprimer ses propres commentaires, les admins peuvent tout supprimer.',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID du commentaire',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Commentaire supprimé avec succès'
            ),
            new OA\Response(
                response: 403,
                description: 'Vous ne pouvez pas supprimer ce commentaire'
            ),
            new OA\Response(
                response: 404,
                description: 'Commentaire non trouvé'
            ),
        ]
    )]
    public function delete(Comment $comment): JsonResponse
    {
        $user = $this->getUser();

        if ($comment->getAuthor() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(
                ['error' => 'You can only delete your own comments'],
                Response::HTTP_FORBIDDEN
            );
        }

        $this->em->remove($comment);
        $this->em->flush();

        return $this->json(
            ['message' => 'Comment deleted successfully'],
            Response::HTTP_NO_CONTENT
        );
    }

    private function serializeComment(Comment $comment): array
    {
        return [
            'id' => $comment->getId(),
            'content' => $comment->getContent(),
            'rating' => $comment->getRating(),
            'publicationDate' => $comment->getPublicationDate()->format('c'),
            'updatedDate' => $comment->getUpdatedDate()?->format('c'),
            'author' => [
                'id' => $comment->getAuthor()->getId(),
                'firstname' => $comment->getAuthor()->getFirstname(),
                'lastname' => $comment->getAuthor()->getLastname(),
            ],
        ];
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
