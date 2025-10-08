<?php
namespace App\Controller;

use App\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route('/evenements', name: 'event_index')]
    public function index(): Response
    {
        return $this->render('event/index.html.twig');
    }
    
    #[Route('/evenement/nouveau', name: 'event_new')]
    public function new(): Response
    {
        return $this->render('event/new.html.twig');
    }

    #[Route('/evenement/{id}', name: 'event_show')]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'e' => $event]);
    }


    #[Route('/evenement/{id}/inscription', name: 'event_register')]
    public function register(string $id): Response
    {
        $this->addFlash(
            'success',
            sprintf('Inscription simulée à l\'évènement "%s" (UI uniquement).', $id)
        );

        return $this->redirectToRoute('event_show', ['id' => $id]);
    }
}
