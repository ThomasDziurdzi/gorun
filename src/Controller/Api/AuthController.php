<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
#[OA\Tag(name: 'Authentication', description: 'Endpoints pour l\'authentification et la gestion du profil utilisateur')]
class AuthController extends AbstractController
{
    #[Route('/register', name: 'register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/register',
        summary: 'Créer un nouveau compte utilisateur',
        description: 'Permet à un nouvel utilisateur de s\'inscrire sur la plateforme GoRun',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Données de l\'utilisateur à créer',
            content: new OA\JsonContent(
                required: ['email', 'password', 'firstname', 'lastname'],
                properties: [
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        format: 'email',
                        description: 'Adresse email de l\'utilisateur',
                        example: 'john.doe@example.com'
                    ),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        format: 'password',
                        description: 'Mot de passe (minimum 6 caractères)',
                        example: 'password123'
                    ),
                    new OA\Property(
                        property: 'firstname',
                        type: 'string',
                        description: 'Prénom de l\'utilisateur',
                        example: 'John'
                    ),
                    new OA\Property(
                        property: 'lastname',
                        type: 'string',
                        description: 'Nom de famille de l\'utilisateur',
                        example: 'Doe'
                    ),
                    new OA\Property(
                        property: 'city',
                        type: 'string',
                        description: 'Ville de résidence (optionnel)',
                        example: 'Paris',
                        nullable: true
                    ),
                    new OA\Property(
                        property: 'phoneNumber',
                        type: 'string',
                        description: 'Numéro de téléphone (optionnel)',
                        example: '0601020304',
                        nullable: true
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur créé avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User created successfully'),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'email', type: 'string', example: 'john.doe@example.com'),
                                new OA\Property(property: 'firstname', type: 'string', example: 'John'),
                                new OA\Property(property: 'lastname', type: 'string', example: 'Doe'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Données invalides ou manquantes',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Field \'email\' is required'),
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: 'L\'email existe déjà',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Email already exists'),
                    ]
                )
            ),
        ]
    )]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $validationError = $this->validateRegistrationData($data, $em);
        if ($validationError) {
            return $validationError;
        }

        $user = $this->createUser($data, $passwordHasher);

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatValidationErrors($errors)],
                Response::HTTP_BAD_REQUEST
            );
        }

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'User created successfully',
            'user' => $this->serializeUser($user),
        ], Response::HTTP_CREATED);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    #[OA\Get(
        path: '/api/me',
        summary: 'Récupérer les informations de l\'utilisateur connecté',
        description: 'Retourne les détails complets de l\'utilisateur actuellement authentifié',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Informations de l\'utilisateur',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'email', type: 'string', example: 'john.doe@example.com'),
                        new OA\Property(property: 'firstname', type: 'string', example: 'John'),
                        new OA\Property(property: 'lastname', type: 'string', example: 'Doe'),
                        new OA\Property(property: 'city', type: 'string', example: 'Paris', nullable: true),
                        new OA\Property(property: 'phoneNumber', type: 'string', example: '0601020304', nullable: true),
                        new OA\Property(
                            property: 'roles',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: ['ROLE_USER']
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié - token JWT manquant ou invalide',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Not authenticated'),
                    ]
                )
            ),
        ]
    )]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json($this->serializeUserDetailed($user));
    }

    private function validateRegistrationData(?array $data, EntityManagerInterface $em): ?JsonResponse
    {
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $requiredFields = ['email', 'password', 'firstname', 'lastname'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->json(
                    ['error' => "Field '{$field}' is required"],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['error' => 'Email already exists'], Response::HTTP_CONFLICT);
        }

        return null;
    }

    private function createUser(array $data, UserPasswordHasherInterface $passwordHasher): User
    {
        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstname($data['firstname']);
        $user->setLastname($data['lastname']);
        $user->setRoles(['ROLE_USER']);

        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        if (!empty($data['city'])) {
            $user->setCity($data['city']);
        }
        if (!empty($data['phoneNumber'])) {
            $user->setPhoneNumber($data['phoneNumber']);
        }

        return $user;
    }

    private function formatValidationErrors($errors): array
    {
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }

        return $errorMessages;
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
        ];
    }

    private function serializeUserDetailed(User $user): array
    {
        return array_merge($this->serializeUser($user), [
            'city' => $user->getCity(),
            'phoneNumber' => $user->getPhoneNumber(),
            'roles' => $user->getRoles(),
        ]);
    }
}
