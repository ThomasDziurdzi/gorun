<?php

namespace App\Controller;

use App\Enum\EventStatus;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_default')]
    public function index(EventRepository $eventRepository): Response
    {
        $upcomingEvents = $eventRepository->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.eventDate >= :now')
            ->setParameter('status', EventStatus::PUBLISHED)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.eventDate', 'ASC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        return $this->render('default/index.html.twig', [
            'upcomingEvents' => $upcomingEvents,
        ]);
    }
}