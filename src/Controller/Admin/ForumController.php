<?php

namespace App\Controller\Admin;

use App\Entity\Forum\CategorieForum;
use App\Entity\Forum\Commentaire;
use App\Entity\Forum\Post;
use App\Repository\Forum\CategorieForumRepository;
use App\Repository\Forum\CommentaireRepository;
use App\Repository\Forum\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/forum')]
#[IsGranted('ROLE_ADMIN')]
class ForumController extends AbstractController
{
    #[Route('', name: 'admin_forum', methods: ['GET'])]
    public function index(
        Request $request,
        PostRepository $postRepository,
        CommentaireRepository $commentaireRepository,
        CategorieForumRepository $categorieForumRepository
    ): Response
    {
        $editCategoryId = $request->query->getInt('editCategory');
        $editingCategory = $editCategoryId > 0 ? $categorieForumRepository->find($editCategoryId) : null;

        return $this->render('admin/forum/index.html.twig', [
            'posts' => $postRepository->findForumFeed(),
            'total_posts' => $postRepository->countAllPosts(),
            'total_commentaires' => $commentaireRepository->countAllCommentaires(),
            'recent_commentaires' => $commentaireRepository->findRecentCommentaires(),
            'categories' => $categorieForumRepository->findAllOrdered(),
            'category_form_data' => [
                'nom' => $editingCategory?->getNom() ?? '',
            ],
            'category_errors' => [],
            'editing_category_id' => $editingCategory?->getId(),
        ]);
    }

    #[Route('/categories/new', name: 'admin_forum_category_create', methods: ['POST'])]
    public function createCategory(
        Request $request,
        EntityManagerInterface $entityManager,
        CategorieForumRepository $categorieForumRepository,
        PostRepository $postRepository,
        CommentaireRepository $commentaireRepository
    ): Response {
        if (!$this->isCsrfTokenValid('admin_forum_category_create', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $nom = trim((string) $request->request->get('nom', ''));
        $errors = $this->validateCategoryName($nom, $categorieForumRepository);
        if ($errors !== []) {
            return $this->renderAdminForumPage(
                $postRepository,
                $commentaireRepository,
                $categorieForumRepository,
                ['nom' => $nom],
                $errors
            );
        }

        $categorie = new CategorieForum();
        $categorie->setNom($nom);

        $entityManager->persist($categorie);
        $entityManager->flush();

        $this->addFlash('success', 'La categorie a ete ajoutee.');

        return $this->redirectToRoute('admin_forum');
    }

    #[Route('/categories/{id}/edit', name: 'admin_forum_category_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function editCategory(
        CategorieForum $categorie,
        Request $request,
        EntityManagerInterface $entityManager,
        CategorieForumRepository $categorieForumRepository,
        PostRepository $postRepository,
        CommentaireRepository $commentaireRepository
    ): Response {
        if (!$this->isCsrfTokenValid('admin_forum_category_edit_' . $categorie->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $newName = trim((string) $request->request->get('nom', ''));
        $errors = $this->validateCategoryName($newName, $categorieForumRepository, $categorie->getId());
        if ($errors !== []) {
            return $this->renderAdminForumPage(
                $postRepository,
                $commentaireRepository,
                $categorieForumRepository,
                ['nom' => $newName],
                $errors,
                $categorie->getId()
            );
        }

        $oldName = (string) $categorie->getNom();
        $categorie->setNom($newName);
        $entityManager->flush();

        if ($oldName !== $newName) {
            $postRepository->renameCategory($oldName, $newName);
        }

        $this->addFlash('success', 'La categorie a ete modifiee.');

        return $this->redirectToRoute('admin_forum');
    }

    #[Route('/categories/{id}/delete', name: 'admin_forum_category_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteCategory(
        CategorieForum $categorie,
        Request $request,
        EntityManagerInterface $entityManager,
        PostRepository $postRepository
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('admin_forum_category_delete_' . $categorie->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $categoryName = (string) $categorie->getNom();
        $postRepository->clearCategory($categoryName);
        $entityManager->remove($categorie);
        $entityManager->flush();

        $this->addFlash('success', 'La categorie a ete supprimee.');

        return $this->redirectToRoute('admin_forum');
    }

    #[Route('/post/{id}/delete', name: 'admin_forum_post_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deletePost(Post $post, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_post_' . $post->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $entityManager->remove($post);
        $entityManager->flush();

        $this->addFlash('success', 'Le post a été supprimé.');

        return $this->redirectToRoute('admin_forum');
    }

    #[Route('/comment/{id}/delete', name: 'admin_forum_comment_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteComment(Commentaire $commentaire, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_comment_' . $commentaire->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $entityManager->remove($commentaire);
        $entityManager->flush();

        $this->addFlash('success', 'Le commentaire a été supprimé.');

        return $this->redirectToRoute('admin_forum');
    }

    /**
     * @return array<string, string>
     */
    private function validateCategoryName(string $name, CategorieForumRepository $categorieForumRepository, ?int $ignoreId = null): array
    {
        $errors = [];

        if ($name === '') {
            $errors['nom'] = 'Le nom de la categorie est obligatoire.';

            return $errors;
        }

        if (mb_strlen($name) > 100) {
            $errors['nom'] = 'Le nom de la categorie ne doit pas depasser 100 caracteres.';

            return $errors;
        }

        $existingCategory = $categorieForumRepository->findOneByNormalizedName($name);
        if ($existingCategory !== null && $existingCategory->getId() !== $ignoreId) {
            $errors['nom'] = 'Une categorie avec ce nom existe deja.';
        }

        return $errors;
    }

    /**
     * @param array{nom: string} $categoryFormData
     * @param array<string, string> $categoryErrors
     */
    private function renderAdminForumPage(
        PostRepository $postRepository,
        CommentaireRepository $commentaireRepository,
        CategorieForumRepository $categorieForumRepository,
        array $categoryFormData = ['nom' => ''],
        array $categoryErrors = [],
        ?int $editingCategoryId = null
    ): Response {
        return $this->render('admin/forum/index.html.twig', [
            'posts' => $postRepository->findForumFeed(),
            'total_posts' => $postRepository->countAllPosts(),
            'total_commentaires' => $commentaireRepository->countAllCommentaires(),
            'recent_commentaires' => $commentaireRepository->findRecentCommentaires(),
            'categories' => $categorieForumRepository->findAllOrdered(),
            'category_form_data' => $categoryFormData,
            'category_errors' => $categoryErrors,
            'editing_category_id' => $editingCategoryId,
        ]);
    }
}
