<?php

namespace App\Controller\User;

use App\Entity\User\Utilisateur;
use App\Repository\User\UserNotificationRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'user_notifications', methods: ['GET'])]
    public function index(UserNotificationRepository $notificationRepository): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        try {
            $notifications = $notificationRepository->findRecentForUser($user, 20);
            $unreadCount = $notificationRepository->countUnreadForUser($user);
        } catch (DBALException) {
            $notifications = [];
            $unreadCount = 0;
        }

        return $this->render('user/notification/index.html.twig', [
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    #[Route('/mark-all-read', name: 'user_notifications_mark_all_read', methods: ['POST'])]
    public function markAllRead(
        Request $request,
        UserNotificationRepository $notificationRepository
    ): RedirectResponse {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('user_notifications_mark_all_read', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        try {
            $notificationRepository->markAllAsReadForUser($user);
        } catch (DBALException) {
            $this->addFlash('danger', "Impossible de marquer les notifications comme lues tant que la table n'est pas creee.");

            return $this->redirectToRoute('user_notifications');
        }

        $this->addFlash('success', 'Toutes les notifications ont ete marquees comme lues.');

        return $this->redirectToRoute('user_notifications');
    }

    #[Route('/{id}/open', name: 'user_notifications_open', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function open(
        int $id,
        Request $request,
        UserNotificationRepository $notificationRepository,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('user_notifications_open_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        try {
            $notification = $notificationRepository->findOneForUser($id, $user);
        } catch (DBALException) {
            $this->addFlash('danger', "Impossible d'ouvrir cette notification tant que la table n'est pas creee.");

            return $this->redirectToRoute('user_notifications');
        }

        if (!$notification) {
            throw $this->createNotFoundException('Notification introuvable.');
        }

        if (!$notification->isRead()) {
            $notification->markAsRead();
            $entityManager->flush();
        }

        $target = trim((string) $notification->getLinkUrl());
        if ($target !== '' && str_starts_with($target, '/')) {
            return $this->redirect($target);
        }

        return $this->redirectToRoute('user_notifications');
    }
}
