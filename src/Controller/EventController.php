<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Event;
use App\Entity\Location;
use App\Entity\Registration;
use App\Enum\EventStatus;
use App\Enum\RegistrationStatus;
use App\Form\CommentType;
use App\Form\EventSearchType;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EventController extends AbstractController
{
    #[Route('/evenements', name: 'event_index', methods: ['GET'])]
    public function index(Request $request, EventRepository $eventRepository, PaginatorInterface $paginator): Response
    {
        $searchForm = $this->createForm(EventSearchType::class, null, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);

        $searchForm->handleRequest($request);

        $criteria = $searchForm->getData() ?? [];

        if (!$this->isGranted('ROLE_ADMIN')) {
            $criteria['exclude_drafts'] = true;
        }

        $query = $eventRepository->searchQuery($criteria);

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            9
        );

        return $this->render('event/index.html.twig', [
            'pagination' => $pagination,
            'searchForm' => $searchForm,
        ]);
    }

    #[Route('/evenement/nouveau', name: 'event_new')]
    #[IsGranted('ROLE_ADMIN', message: 'Seuls les administrateurs peuvent créer des évènements.')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Event();
        $location = new Location();

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $event->setOrganizer($user);
        $event->setStatus(EventStatus::DRAFT);
        $location->setCreatedBy($user);

        $event->setLocation($location);

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $action = $request->request->get('action');

            if ('publish' === $action) {
                $event->setStatus(EventStatus::PUBLISHED);
                $message = 'L\'évènement a été publié avec succès!';
            } else {
                $message = 'Brouillon enregistré avec succès!';
            }
            $em->persist($location);
            $em->persist($event);
            $em->flush();

            $this->addFlash('success', $message);

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        return $this->render('event/new.html.twig', [
            'eventForm' => $form->createView(),
        ]);
    }

    #[Route('/evenement/{id}', name: 'event_show')]
    public function show(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        if (EventStatus::DRAFT === $event->getStatus() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Cet événement n\'est pas encore publié.');
        }

        $comments = $em->getRepository(Comment::class)->findBy(
            ['event' => $event],
            ['publicationDate' => 'DESC']
        );

        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);

        $commentForm->handleRequest($request);

        return $this->render('event/show.html.twig', [
            'e' => $event,
            'comments' => $comments,
            'commentForm' => $commentForm,
        ]);
    }

    #[Route('/evenement/{id}/modifier', name: 'event_edit')]
    #[IsGranted('ROLE_ADMIN', message: 'Seuls les administrateurs peuvent modifier des évènements.')]
    public function edit(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        if (EventStatus::COMPLETED === $event->getStatus() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Impossible de modifier un événement terminé.');

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        if (EventStatus::CANCELLED === $event->getStatus() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Impossible de modifier un événement annulé.');

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

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

    #[Route('/evenement/{id}/publier', name: 'event_publish', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Seuls les administrateurs peuvent publier des évènements.')]
    public function publish(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('publish'.$event->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        if (EventStatus::DRAFT !== $event->getStatus()) {
            $this->addFlash('error', 'Seul un brouillon peut être publié.');

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        if ($event->getEventDate() < new \DateTimeImmutable()) {
            $this->addFlash('error', 'Impossible de publier un événement passé.');

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        $event->setStatus(EventStatus::PUBLISHED);
        $em->flush();

        $this->addFlash('success', 'Événement publié avec succès ! Il est maintenant visible par tous.');

        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }

    #[Route('/evenement/{id}/depublier', name: 'event_unpublish', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Seuls les administrateurs peuvent dépublier des évènements.')]
    public function unpublish(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('unpublish'.$event->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        if (EventStatus::PUBLISHED !== $event->getStatus()) {
            $this->addFlash('error', 'Seul un événement publié peut être dépublié.');

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        $event->setStatus(EventStatus::DRAFT);
        $em->flush();

        $this->addFlash('success', 'Événement remis en brouillon. Il n\'est plus visible publiquement.');

        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }

    #[Route('/evenement/{id}/annuler', name: 'event_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Seuls les administrateurs peuvent annuler des évènements.')]
    public function cancel(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('cancel'.$event->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        if (EventStatus::COMPLETED === $event->getStatus()) {
            $this->addFlash('error', 'Un événement terminé ne peut pas être annulé.');

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        if (EventStatus::CANCELLED === $event->getStatus()) {
            $this->addFlash('warning', 'Cet événement est déjà annulé.');

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        $event->setStatus(EventStatus::CANCELLED);
        $em->flush();

        $this->addFlash('success', 'Événement annulé.');

        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }

    #[Route('/evenement/{id}/supprimer', name: 'event_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Seuls les administrateurs peuvent supprimer des évènements.')]
    public function delete(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            $registrations = $event->getRegistrations();
            foreach ($registrations as $registration) {
                $em->remove($registration);
            }

            $comments = $event->getComments();
            foreach ($comments as $comment) {
                $em->remove($comment);
            }

            $notifications = $event->getNotifications();
            foreach ($notifications as $notification) {
                $em->remove($notification);
            }

            $em->remove($event);
            $em->flush();

            $this->addFlash('success', 'L\'évènement a été supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token de sécurité invalide. La suppression a échoué.');
        }

        return $this->redirectToRoute('event_index');
    }

    #[Route('/evenement/{id}/inscription', name: 'event_register', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function register(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('register'.$event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$event->canRegister()) {
            $effectiveStatus = $event->getEffectiveStatus();

            $message = match ($effectiveStatus) {
                EventStatus::DRAFT => 'Cet événement n\'est pas encore publié.',
                EventStatus::CANCELLED => 'Cet événement a été annulé.',
                EventStatus::COMPLETED => 'Cet événement est terminé.',
                default => 'Vous ne pouvez pas vous inscrire à cet événement.',
            };

            if ($event->isFull()) {
                $message = 'L\'événement est complet.';
            }

            $this->addFlash('error', $message);

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        $existingRegistration = $em->getRepository(Registration::class)->findOneBy([
            'user' => $user,
            'event' => $event,
            'status' => RegistrationStatus::CONFIRMED,
        ]);

        if ($existingRegistration) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit à cet événement.');

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        $em->getConnection()->beginTransaction();

        try {
            $event = $em->find(Event::class, $event->getId(), \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);

            if (!$event->canRegister()) {
                throw new \Exception('L\'événement n\'est plus disponible pour inscription.');
            }

            $registration = new Registration();
            $registration->setUser($user);
            $registration->setEvent($event);
            $registration->setStatus(RegistrationStatus::CONFIRMED);

            $em->persist($registration);
            $em->flush();
            $em->getConnection()->commit();

            $this->addFlash('success', sprintf(
                'Inscription confirmée pour "%s" ! Rendez-vous le %s.',
                $event->getTitle(),
                $event->getEventDate()->format('d/m/Y à H:i')
            ));

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }
    }

    #[Route('/evenement/{id}/desinscription', name: 'event_unregister', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function unregister(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('unregister'.$event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        $user = $this->getUser();

        $registration = $em->getRepository(Registration::class)->findOneBy([
            'user' => $user,
            'event' => $event,
            'status' => RegistrationStatus::CONFIRMED,
        ]);

        if (!$registration) {
            $this->addFlash('warning', 'Vous n\'êtes pas inscrit à un évènement.');

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        $em->remove($registration);

        $em->flush();

        $this->addFlash('success', 'Votre inscription a été annulée.');

        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }
}
