<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Form\UserProfileType;
use App\Repository\EventRepository;
use App\Repository\RegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(UserProfileType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/mes-evenements', name: 'app_profile_events')]
    public function myEvents(
        EventRepository $eventRepository,
        RegistrationRepository $registrationRepository,
    ): Response {
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $upcomingRegistrations = $registrationRepository->findUpcomingByUser($user);

        $pastRegistrations = $registrationRepository->findPastByUser($user);

        $stats = [
            'totalParticipations' => $registrationRepository->countConfirmedByUser($user),
            'totalKilometers' => $registrationRepository->getTotalKilometersByUser($user),
        ];

        $organizedEvents = [];
        if ($isAdmin) {
            $organizedEvents = $eventRepository->findOrganizedByUser($user);
            $stats['totalOrganized'] = $eventRepository->countOrganizedByUser($user);
        }

        return $this->render('profile/my_events.html.twig', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'upcomingRegistrations' => $upcomingRegistrations,
            'organizedEvents' => $organizedEvents,
            'pastRegistrations' => $pastRegistrations,
            'stats' => $stats,
        ]);
    }

    #[Route('/profil/changer-mot-de-passe', name: 'app_profile_change_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect');

                return $this->redirectToRoute('app_profile_change_password');
            }

            $newPassword = $form->get('newPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $em->flush();

            $this->addFlash('success', 'Votre mot de passe a été changé avec succès !');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }
}
