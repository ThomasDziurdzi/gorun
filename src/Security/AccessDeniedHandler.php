<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        // Stocker le message dans la session manuellement
        $session = $request->getSession();
        $session->set('_security.access_denied_message', 'Vous n\'avez pas les droits pour accéder à cette page.');

        return new RedirectResponse(
            $this->urlGenerator->generate('event_index')
        );
    }
}
