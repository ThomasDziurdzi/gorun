<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Event;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/commentaire')]
class CommentController extends AbstractController
{
    #[Route('/evenement/{id}/ajouter', name: 'comment_new', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function new(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();

            $comment->setEvent($event);
            $comment->setAuthor($user);

            $em->persist($comment);
            $em->flush();

            $this->addFlash('success', 'Votre commentaire a été publié avec succès !');

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        // Afficher les erreurs en haut de page (flash messages classiques)
        foreach ($form->getErrors(true) as $error) {
            $this->addFlash('error', $error->getMessage());
        }

        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }

    #[Route('/{id}/modifier', name: 'comment_edit', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($comment->getAuthor() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier ce commentaire.');

            return $this->redirectToRoute('event_show', ['id' => $comment->getEvent()->getId()]);
        }

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setUpdatedDate(new \DateTimeImmutable());
            $em->flush();

            $this->addFlash('success', 'Commentaire modifié avec succès !');

            return $this->redirectToRoute('event_show', ['id' => $comment->getEvent()->getId()]);
        }

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'comment_delete', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($comment->getAuthor() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce commentaire.');

            return $this->redirectToRoute('event_show', ['id' => $comment->getEvent()->getId()]);
        }

        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $eventId = $comment->getEvent()->getId();
            $em->remove($comment);
            $em->flush();

            $this->addFlash('success', 'Commentaire supprimé avec succès.');

            return $this->redirectToRoute('event_show', ['id' => $eventId]);
        }

        $this->addFlash('error', 'Token CSRF invalide.');

        return $this->redirectToRoute('event_show', ['id' => $comment->getEvent()->getId()]);
    }
}
