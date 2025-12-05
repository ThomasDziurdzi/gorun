<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
class AuthController extends AbstractController
{
    #[Route('/register', name: 'register', methods: ['POST'])]
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