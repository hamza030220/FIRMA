<?php

namespace App\Controller\Admin;

use App\Entity\Forum\CategorieForum;
use App\Entity\Forum\Commentaire;
use App\Entity\Forum\Post;
use App\Repository\Forum\CategorieForumRepository;
use App\Repository\Forum\CommentaireRepository;
use App\Repository\Forum\ForumModerationAlertRepository;
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
        CategorieForumRepository $categorieForumRepository,
        ForumModerationAlertRepository $moderationAlertRepository
    ): Response
    {
        $editCategoryId = $request->query->getInt('editCategory');
        $editingCategory = $editCategoryId > 0 ? $categorieForumRepository->find($editCategoryId) : null;
        $search = trim((string) $request->query->get('q', ''));
        $activeSearch = mb_strlen($search) >= 3 ? $search : '';
        $categories = $categorieForumRepository->findAllOrdered();
        $mostUsedCategory = $postRepository->findMostUsedCategory();
        $allPosts = $postRepository->findForumFeed();
        $displayedPosts = $postRepository->findForumFeed($activeSearch);

        return $this->render('admin/forum/index.html.twig', [
            'posts' => $displayedPosts,
            'total_posts' => $postRepository->countAllPosts(),
            'total_commentaires' => $commentaireRepository->countAllCommentaires(),
            'pending_alerts' => $moderationAlertRepository->countPending(),
            'recent_alerts' => $moderationAlertRepository->findRecentPending(5),
            'categories' => $categories,
            'total_categories' => count($categories),
            'categorized_posts' => $postRepository->countCategorizedPosts(),
            'uncategorized_posts' => $postRepository->countUncategorizedPosts(),
            'most_used_category' => $mostUsedCategory,
            'community_pulse' => $this->buildCommunityPulse($allPosts),
            'search' => $search,
            'active_search' => $activeSearch,
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
        CommentaireRepository $commentaireRepository,
        ForumModerationAlertRepository $moderationAlertRepository
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
                $moderationAlertRepository,
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
        CommentaireRepository $commentaireRepository,
        ForumModerationAlertRepository $moderationAlertRepository
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
                $moderationAlertRepository,
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

        $this->addFlash('success', 'Le post a ete supprime.');

        return $this->redirectToRoute('admin_forum');
    }

    #[Route('/post/{id}', name: 'admin_forum_post_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showPost(
        int $id,
        PostRepository $postRepository,
        ForumModerationAlertRepository $moderationAlertRepository
    ): Response
    {
        $post = $postRepository->findOneForForum($id);
        if (!$post) {
            throw $this->createNotFoundException('Post introuvable.');
        }

        $commentIds = [];
        foreach ($post->getCommentaires() as $commentaire) {
            if ($commentaire->getId() !== null) {
                $commentIds[] = $commentaire->getId();
            }
        }

        $alertsByComment = [];
        foreach ($moderationAlertRepository->findByCommentIds($commentIds) as $alert) {
            $commentId = $alert->getCommentaire()?->getId();
            if ($commentId === null) {
                continue;
            }

            $alertsByComment[$commentId][] = $alert;
        }

        return $this->render('admin/forum/show.html.twig', [
            'post' => $post,
            'alerts_by_comment' => $alertsByComment,
        ]);
    }

    #[Route('/post/{id}/pin', name: 'admin_forum_post_toggle_pin', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function togglePostPin(Post $post, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('toggle_pin_post_' . $post->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $post->setIsPinned(!$post->isPinned());
        $entityManager->flush();

        $this->addFlash('success', $post->isPinned() ? 'Le post a ete epingle.' : 'Le post a ete desepingle.');

        $returnTo = trim((string) $request->request->get('return_to', ''));
        if ($returnTo !== '' && str_starts_with($returnTo, '/')) {
            return $this->redirect($returnTo);
        }

        return $this->redirectToRoute('admin_forum');
    }

    #[Route('/alert/{id}/warn', name: 'admin_forum_alert_warn', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function warnAlert(
        int $id,
        Request $request,
        ForumModerationAlertRepository $moderationAlertRepository,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $alert = $moderationAlertRepository->find($id);
        if (!$alert) {
            throw $this->createNotFoundException('Alerte introuvable.');
        }

        if (!$this->isCsrfTokenValid('warn_alert_' . $alert->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user = $alert->getUtilisateur();
        if ($user === null) {
            $this->addFlash('danger', "Impossible d'envoyer l'avertissement: utilisateur introuvable.");

            return $this->redirectToRoute('admin_forum');
        }

        $post = $alert->getCommentaire()?->getPost();

        $createdAt = new \DateTime('now', new \DateTimeZone('Africa/Lagos'));
        $linkUrl = $post !== null && $post->getId() !== null
            ? $this->generateUrl('user_forum_show', ['id' => $post->getId(), '_fragment' => 'post-comments'])
            : $this->generateUrl('user_notifications');

        try {
            $entityManager->getConnection()->executeStatement(
                'INSERT INTO user_notification (recipient_id, type, title, message, link_url, is_read, created_at, read_at) VALUES (:recipient_id, :type, :title, :message, :link_url, :is_read, :created_at, :read_at)',
                [
                    'recipient_id' => $user->getId(),
                    'type' => 'forum_warning',
                    'title' => 'Avertissement forum',
                    'message' => 'Notre application interdit les propos insultants. Consultez vos avertissements dans le forum.',
                    'link_url' => $linkUrl,
                    'is_read' => 0,
                    'created_at' => $createdAt->format('Y-m-d H:i:s'),
                    'read_at' => null,
                ]
            );

            $alert
                ->setStatus('treated')
                ->setReviewedAt($createdAt);
            $alert->setNote('Avertissement envoye le ' . $createdAt->format('d/m/Y H:i'));
            $entityManager->flush();
        } catch (\Throwable $exception) {
            $this->addFlash('danger', "La notification interne n'a pas pu etre enregistree.");

            return $this->redirectToRoute('admin_forum');
        }

        $displayName = trim((string) ($user->getPrenom() . ' ' . $user->getNom()));
        $this->addFlash('success', 'Avertissement interne envoye' . ($displayName !== '' ? ' a ' . $displayName : '') . '.');

        return $this->redirectToRoute('admin_forum');
    }

    #[Route('/comment/{id}/delete', name: 'admin_forum_comment_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteComment(Commentaire $commentaire, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_comment_' . $commentaire->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $now = new \DateTime('now', new \DateTimeZone('Africa/Lagos'));
        foreach ($commentaire->getModerationAlerts() as $alert) {
            $alert
                ->setStatus('treated')
                ->setReviewedAt($now)
                ->setNote('Commentaire supprime par l\'administrateur.');
        }

        $entityManager->remove($commentaire);
        $entityManager->flush();

        $this->addFlash('success', 'Le commentaire a ete supprime.');

        if ($request->request->getBoolean('redirect_to_post')) {
            $postId = $commentaire->getPost()?->getId();
            if ($postId !== null) {
                return $this->redirectToRoute('admin_forum_post_show', ['id' => $postId]);
            }
        }

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
        ForumModerationAlertRepository $moderationAlertRepository,
        array $categoryFormData = ['nom' => ''],
        array $categoryErrors = [],
        ?int $editingCategoryId = null
    ): Response {
        foreach ($categoryErrors as $err) {
            $this->addFlash('danger', $err);
        }

        $categories = $categorieForumRepository->findAllOrdered();
        $mostUsedCategory = $postRepository->findMostUsedCategory();
        $posts = $postRepository->findForumFeed();

        return $this->render('admin/forum/index.html.twig', [
            'posts' => $posts,
            'total_posts' => $postRepository->countAllPosts(),
            'total_commentaires' => $commentaireRepository->countAllCommentaires(),
            'pending_alerts' => $moderationAlertRepository->countPending(),
            'recent_alerts' => $moderationAlertRepository->findRecentPending(5),
            'categories' => $categories,
            'total_categories' => count($categories),
            'categorized_posts' => $postRepository->countCategorizedPosts(),
            'uncategorized_posts' => $postRepository->countUncategorizedPosts(),
            'most_used_category' => $mostUsedCategory,
            'community_pulse' => $this->buildCommunityPulse($posts),
            'search' => '',
            'active_search' => '',
            'category_form_data' => $categoryFormData,
            'category_errors' => [],
            'editing_category_id' => $editingCategoryId,
        ]);
    }

    /**
     * @param list<Post> $posts
     * @return array{
     *     score: int,
     *     level: string,
     *     label: string,
     *     interactive_accounts: int,
     *     posts_with_comments_pct: float,
     *     avg_comments_per_post: float,
     *     image_comments_pct: float,
     *     trend_labels: list<string>,
     *     trend_values: list<int>,
     *     trend_peak: int
     * }
     */
    private function buildCommunityPulse(array $posts): array
    {
        $totalPosts = count($posts);
        $postsWithComments = 0;
        $totalComments = 0;
        $imageComments = 0;
        $interactiveUsers = [];

        $timezone = new \DateTimeZone('Africa/Lagos');
        $now = new \DateTimeImmutable('now', $timezone);
        $windowStart = $now->setTime(0, 0)->modify('-6 days');
        $weekdayLabels = [
            1 => 'Lun',
            2 => 'Mar',
            3 => 'Mer',
            4 => 'Jeu',
            5 => 'Ven',
            6 => 'Sam',
            7 => 'Dim',
        ];

        $trendBuckets = [];
        $trendLabels = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $windowStart->modify('+' . $i . ' days');
            $key = $day->format('Y-m-d');
            $trendBuckets[$key] = 0;
            $trendLabels[] = $weekdayLabels[(int) $day->format('N')] ?? $day->format('d/m');
        }

        foreach ($posts as $post) {
            $hasComment = false;
            $postAuthor = $post->getUtilisateur();
            if ($postAuthor !== null && $postAuthor->getId() !== null) {
                $interactiveUsers[$postAuthor->getId()] = true;
            }

            $postDate = $post->getDateCreation();
            if ($postDate instanceof \DateTimeInterface) {
                $postDateImmutable = \DateTimeImmutable::createFromInterface($postDate)->setTimezone($timezone);
                $postKey = $postDateImmutable->format('Y-m-d');
                if (isset($trendBuckets[$postKey])) {
                    $trendBuckets[$postKey] += 1;
                }
            }

            foreach ($post->getCommentaires() as $commentaire) {
                $hasComment = true;
                $totalComments++;

                $commentAuthor = $commentaire->getUtilisateur();
                if ($commentAuthor !== null && $commentAuthor->getId() !== null) {
                    $interactiveUsers[$commentAuthor->getId()] = true;
                }

                if ($commentaire->getImagePath() !== null && trim($commentaire->getImagePath()) !== '') {
                    $imageComments++;
                }

                $commentDate = $commentaire->getDateCreation();
                if (!$commentDate instanceof \DateTimeInterface) {
                    continue;
                }

                $commentDateImmutable = \DateTimeImmutable::createFromInterface($commentDate)->setTimezone($timezone);
                $commentKey = $commentDateImmutable->format('Y-m-d');
                if (isset($trendBuckets[$commentKey])) {
                    // Le commentaire pese plus dans la dynamique communautaire.
                    $trendBuckets[$commentKey] += 2;
                }
            }

            foreach ($post->getReactions() as $reaction) {
                $reactionUser = $reaction->getUtilisateur();
                if ($reactionUser !== null && $reactionUser->getId() !== null) {
                    $interactiveUsers[$reactionUser->getId()] = true;
                }
            }

            if ($hasComment) {
                $postsWithComments++;
            }
        }

        $postsWithCommentsPct = $totalPosts > 0 ? ($postsWithComments * 100 / $totalPosts) : 0.0;
        $avgCommentsPerPost = $totalPosts > 0 ? ($totalComments / $totalPosts) : 0.0;
        $imageCommentsPct = $totalComments > 0 ? ($imageComments * 100 / $totalComments) : 0.0;

        $score = (int) round(
            min(100, max(0,
                ($postsWithCommentsPct * 0.5)
                + (min(5.0, $avgCommentsPerPost) * 10.0)
                + (min(50.0, $imageCommentsPct) * 0.4)
            ))
        );

        $level = 'A demarrer';
        $label = 'low';
        if ($score >= 75) {
            $level = 'Excellente dynamique';
            $label = 'high';
        } elseif ($score >= 45) {
            $level = 'Croissance stable';
            $label = 'medium';
        }

        $trendValues = array_values($trendBuckets);
        $trendPeak = max(1, (int) max($trendValues));

        return [
            'score' => $score,
            'level' => $level,
            'label' => $label,
            'interactive_accounts' => count($interactiveUsers),
            'posts_with_comments_pct' => round($postsWithCommentsPct, 1),
            'avg_comments_per_post' => round($avgCommentsPerPost, 1),
            'image_comments_pct' => round($imageCommentsPct, 1),
            'trend_labels' => $trendLabels,
            'trend_values' => array_map(static fn (int $value): int => $value, $trendValues),
            'trend_peak' => $trendPeak,
        ];
    }
}


