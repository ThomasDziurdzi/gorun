<?php

namespace App\Controller;

use App\Form\UserProfileType;
use App\Repository\EventRepository;
use App\Repository\RegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    RegistrationRepository $registrationRepository
): Response
{
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
}
