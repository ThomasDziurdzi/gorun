<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Location;
use App\Form\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EventController extends AbstractController
{
    #[Route('/evenements', name: 'event_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $events = $em->getRepository(Event::class)->findAll();

        return $this->render('event/index.html.twig', [
            'events' => $events
        ]);
    }

    #[Route('/evenement/nouveau', name: 'event_new')]
    #[IsGranted('ROLE_ADMIN', message: 'Seuls les administrateurs peuvent créer des évènements.')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Event();
        $location = new Location();

        $event->setOrganizer($this->getUser());
        $location->setCreatedBy($this->getUser());

        $event->setLocation($location);

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($location);
            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'L\'évènement a été créé avec succès!');

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        return $this->render('event/new.html.twig', [
            'eventForm' => $form->createView(),
        ]);
    }

    #[Route('/evenement/{id}', name: 'event_show')]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'e' => $event
        ]);
    }

    #[Route('/evenement/{id}/modifier', name: 'event_edit')]
    #[IsGranted('ROLE_ADMIN', message: 'Seuls les administrateurs peuvent modifier des évènements.')]
    public function edit(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setUpdatedDate(new \DateTimeImmutable());

            $location = $event->getLocation();
            if ($location) {
                $em->persist($location);
            }

            $em->flush();

            $this->addFlash('success', 'L\'événement a été modifié avec succès !');

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        return $this->render('event/edit.html.twig', [
            'eventForm' => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route('/evenement/{id}/supprimer', name: 'event_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Seuls les administrateurs peuvent supprimer des évènements.')]
    public function delete(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $event->getId(), $request->request->get('_token'))) {
            $em->remove($event);
            $em->flush();

            $this->addFlash('success', 'L\'évènement a été supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token de sécurité invalide. La suppression a échoué.');
        }

        return $this->redirectToRoute('event_index');
    }   


    #[Route('/evenement/{id}/inscription', name: 'event_register')]
    public function register(Event $event): Response
    {
        $this->addFlash(
            'success',
            sprintf('Inscription simulée à l\'évènement "%s" (UI uniquement).', $event->getId())
        );

        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }
}
