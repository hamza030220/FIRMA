<?php

namespace App\Controller\Admin;

use App\Entity\User\Utilisateur;
use App\Form\User\AdminUserType;
use App\Repository\User\UtilisateurRepository;
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
    #[Route('', name: 'admin_utilisateurs')]
    public function index(Request $request, UtilisateurRepository $repo): Response
    {
        $keyword = $request->query->get('q', '');
        $role    = $request->query->get('role', '');

        $utilisateurs = $repo->searchAndFilter($keyword, $role);
        $stats        = $repo->countByType();

        return $this->render('admin/user/index.html.twig', [
            'utilisateurs' => $utilisateurs,
            'keyword'      => $keyword,
            'role'         => $role,
            'stats'        => $stats,
        ]);
    }

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
            // Vérifier email unique
            if ($repo->findOneBy(['email' => $utilisateur->getEmail()])) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->render('admin/user/new.html.twig', ['form' => $form]);
            }

            $plainPassword = $form->get('plainPassword')->getData();
            $hashed = $hasher->hashPassword($utilisateur, $plainPassword);
            $utilisateur->setMotDePasse($hashed);
            $utilisateur->setDateCreation(new \DateTime());

            $repo->getEntityManager()->persist($utilisateur);
            $repo->getEntityManager()->flush();

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

    #[Route('/{id}', name: 'admin_utilisateur_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Utilisateur $utilisateur): Response
    {
        return $this->render('admin/user/show.html.twig', ['utilisateur' => $utilisateur]);
    }

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
            // Vérifier unicité email si modifié
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
                $hashed = $hasher->hashPassword($utilisateur, $plainPassword);
                $utilisateur->setMotDePasse($hashed);
            }

            $repo->getEntityManager()->flush();
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

        // Empêcher la suppression de son propre compte
        if ($utilisateur->getId() === $this->getUser()?->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_utilisateurs');
        }

        $em = $repo->getEntityManager();
        $em->remove($utilisateur);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé.');
        return $this->redirectToRoute('admin_utilisateurs');
    }
}
