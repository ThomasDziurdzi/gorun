<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/connexion', name: 'app_login')]
    public function login(): Response
    {
        return $this->render('security/login.html.twig');
    }

    #[Route('/inscription', name: 'app_register')]
    public function register(): Response
    {
        return $this->render('registration/register.html.twig');
    }

    #[Route('/mot-de-passe-oublie', name: 'app_forgot_password')]
    public function forgot(): Response
    {
        return $this->render('security/forgot_password.html.twig');
    }

    #[Route('/reset', name: 'app_reset_password')]
    public function reset(): Response
    {
        return $this->render('security/reset_password.html.twig');
    }

    #[Route('/deconnexion', name: 'app_logout')]
    public function logout(): Response
    {
        $this->addFlash('success', 'Déconnexion (UI simulée)');
        return $this->redirectToRoute('app_default');
    }
}
