<?php

namespace App\Controller\Admin;

use App\Entity\User\Utilisateur;
use App\Form\User\AdminUserType;
use App\Repository\User\UtilisateurRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
class UtilisateurController extends AbstractController
{
    public function __construct(private LoggerInterface $logger) {}

    // ─────────────────────────────────────────────────────────────────────────
    // LIST
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('', name: 'admin_utilisateurs')]
    public function index(Request $request, UtilisateurRepository $repo): Response
    {
        $keyword = $request->query->get('q', '');
        $role    = $request->query->get('role', '');

        $utilisateurs = $repo->searchAndFilter($keyword, $role);
        $stats        = $repo->countByType();

        $page       = max(1, $request->query->getInt('page', 1));
        $limit      = 10;
        $total      = count($utilisateurs);
        $totalPages = max(1, (int) ceil($total / $limit));
        $page       = min($page, $totalPages);
        $utilisateurs = array_slice($utilisateurs, ($page - 1) * $limit, $limit);

        return $this->render('admin/user/index.html.twig', [
            'utilisateurs' => $utilisateurs,
            'keyword'      => $keyword,
            'role'         => $role,
            'stats'        => $stats,
            'currentPage'  => $page,
            'totalPages'   => $totalPages,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATE
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/nouveau', name: 'admin_utilisateur_new')]
    public function new(
        Request $request,
        UtilisateurRepository $repo,
        UserPasswordHasherInterface $hasher,
    ): Response {
        $utilisateur = new Utilisateur();
        $form = $this->createForm(AdminUserType::class, $utilisateur, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($repo->findOneBy(['email' => $utilisateur->getEmail()])) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->render('admin/user/new.html.twig', ['form' => $form]);
            }

            $hashed = $hasher->hashPassword($utilisateur, $form->get('plainPassword')->getData());
            $utilisateur->setMotDePasse($hashed);
            $utilisateur->setDateCreation(new \DateTime());

            $em = $repo->getEntityManager();
            $em->persist($utilisateur);
            $em->flush();

            $this->logAction('CREATE', $utilisateur);
            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('admin_utilisateurs');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->render('admin/user/new.html.twig', ['form' => $form]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHOW
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/{id}', name: 'admin_utilisateur_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Utilisateur $utilisateur): Response
    {
        return $this->render('admin/user/show.html.twig', ['utilisateur' => $utilisateur]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EDIT
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/{id}/modifier', name: 'admin_utilisateur_edit', requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        Utilisateur $utilisateur,
        UtilisateurRepository $repo,
        UserPasswordHasherInterface $hasher,
    ): Response {
        $form = $this->createForm(AdminUserType::class, $utilisateur, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emailExistant = $repo->findOneBy(['email' => $utilisateur->getEmail()]);
            if ($emailExistant && $emailExistant->getId() !== $utilisateur->getId()) {
                $this->addFlash('error', 'Cet email est déjà utilisé par un autre compte.');
                return $this->render('admin/user/edit.html.twig', [
                    'form'        => $form,
                    'utilisateur' => $utilisateur,
                ]);
            }

            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $utilisateur->setMotDePasse($hasher->hashPassword($utilisateur, $plainPassword));
            }

            $repo->getEntityManager()->flush();
            $this->logAction('EDIT', $utilisateur);
            $this->addFlash('success', 'Utilisateur modifié avec succès.');
            return $this->redirectToRoute('admin_utilisateurs');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->render('admin/user/edit.html.twig', [
            'form'        => $form,
            'utilisateur' => $utilisateur,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/{id}/supprimer', name: 'admin_utilisateur_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        Utilisateur $utilisateur,
        UtilisateurRepository $repo,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_user_' . $utilisateur->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_utilisateurs');
        }

        if ($utilisateur->getId() === $this->getUser()?->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_utilisateurs');
        }

        $this->logAction('DELETE', $utilisateur);

        $em = $repo->getEntityManager();
        $em->remove($utilisateur);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé.');
        return $this->redirectToRoute('admin_utilisateurs');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BAN / UNBAN
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/{id}/bannir', name: 'admin_utilisateur_ban', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function ban(
        Request $request,
        Utilisateur $utilisateur,
        UtilisateurRepository $repo,
    ): Response {
        if (!$this->isCsrfTokenValid('ban_user_' . $utilisateur->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_utilisateurs');
        }

        // Cannot ban yourself
        if ($utilisateur->getId() === $this->getUser()?->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas bannir votre propre compte.');
            return $this->redirectToRoute('admin_utilisateurs');
        }

        // Cannot ban another admin
        if (in_array('ROLE_ADMIN', $utilisateur->getRoles(), true)) {
            $this->addFlash('error', 'Vous ne pouvez pas bannir un autre administrateur.');
            return $this->redirectToRoute('admin_utilisateurs');
        }

        $reason = trim($request->request->get('ban_reason', 'Aucune raison précisée.'));

        $utilisateur->setIsBanned(true);
        $utilisateur->setBanReason($reason);
        $utilisateur->setBannedAt(new \DateTime());

        $repo->getEntityManager()->flush();

        $this->logAction('BAN', $utilisateur, $reason);
        $this->addFlash('warning', sprintf('Le compte de %s a été suspendu.', $utilisateur->getFullName()));

        return $this->redirectToRoute('admin_utilisateurs');
    }

    #[Route('/{id}/debannir', name: 'admin_utilisateur_unban', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function unban(
        Request $request,
        Utilisateur $utilisateur,
        UtilisateurRepository $repo,
    ): Response {
        if (!$this->isCsrfTokenValid('unban_user_' . $utilisateur->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_utilisateurs');
        }

        $utilisateur->setIsBanned(false);
        $utilisateur->setBanReason(null);
        $utilisateur->setBannedAt(null);

        $repo->getEntityManager()->flush();

        $this->logAction('UNBAN', $utilisateur);
        $this->addFlash('success', sprintf('Le compte de %s a été réactivé.', $utilisateur->getFullName()));

        return $this->redirectToRoute('admin_utilisateurs');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function logAction(string $action, Utilisateur $target, string $extra = ''): void
    {
        /** @var Utilisateur $admin */
        $admin = $this->getUser();
        $this->logger->info('[ADMIN_AUDIT] {action} | Admin: {admin} | Target: {target} (id={id}) | {extra}', [
            'action' => $action,
            'admin'  => $admin?->getFullName() . ' <' . $admin?->getEmail() . '>',
            'target' => $target->getFullName(),
            'id'     => $target->getId(),
            'extra'  => $extra,
        ]);
    }
}