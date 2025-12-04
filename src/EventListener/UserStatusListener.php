<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: KernelEvents::REQUEST)]
class UserStatusListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private RouterInterface $router,
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Refresh user from database to get the latest status
        // The user in the token might be stale (from session)
        $freshUser = $this->entityManager->getRepository(User::class)->find($user->getId());

        if (!$freshUser) {
            // User was deleted
            $this->logoutAndRedirect($event, 'Your account has been deleted.');
            return;
        }

        if ($freshUser->isBlocked()) {
            // User was blocked
            $this->logoutAndRedirect($event, 'Your account has been blocked.');
        }
    }

    private function logoutAndRedirect(RequestEvent $event, string $message): void
    {
        // Invalidate session
        $request = $event->getRequest();
        $session = $request->getSession();
        if ($session) {
            $session->invalidate();
            // We can't easily add a flash message to an invalidated session for the *next* request
            // But we can try to start a new one or just rely on the redirect.
            // For simplicity, we'll just redirect to login.
        }
        
        $this->tokenStorage->setToken(null);

        // Redirect to login
        $loginUrl = $this->router->generate('app_login');
        $event->setResponse(new RedirectResponse($loginUrl));
    }
}
