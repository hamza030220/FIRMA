<?php

namespace App\Controller\User;

use App\Entity\Forum\Commentaire;
use App\Entity\Forum\ForumPostBookmark;
use App\Entity\Forum\ForumModerationAlert;
use App\Entity\Forum\Post;
use App\Entity\Forum\ReactionPost;
use App\Entity\User\Utilisateur;
use App\Repository\Forum\CategorieForumRepository;
use App\Repository\Forum\ForumPostBookmarkRepository;
use App\Repository\Forum\ForumModerationAlertRepository;
use App\Repository\Forum\PostRepository;
use App\Repository\Forum\ReactionPostRepository;
use App\Service\Forum\ForumBadWordsService;
use App\Service\Forum\ForumTranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/forum')]
#[IsGranted('ROLE_USER')]
class ForumController extends AbstractController
{
    private const APP_TIMEZONE = 'Africa/Lagos';
    private const FORUM_SORT_OPTIONS = ['recent', 'oldest', 'title_asc', 'title_desc'];
    private const FORUM_REACTION_TYPES = ['like', 'dislike', 'solidaire', 'encolere', 'triste'];
    private const COMMENT_IMAGE_MAX_BYTES = 5 * 1024 * 1024;
    private const COMMENT_IMAGE_FORMAT_MESSAGE = 'Formats acceptes : JPG, PNG, WEBP ou GIF.';
    private const COMMENT_IMAGE_DIRECTORY = 'uploads/commentaires';
    private const COMMENT_IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];
    private const COMMENT_IMAGE_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif',
    ];
    private const COMMENT_IMAGE_MIN_WIDTH = 200;
    private const COMMENT_IMAGE_MIN_HEIGHT = 200;
    private const COMMENT_IMAGE_MAX_WIDTH = 6000;
    private const COMMENT_IMAGE_MAX_HEIGHT = 6000;
    private const COMMENT_MIN_LENGTH = 2;
    private const COMMENT_MAX_LENGTH = 1000;

    public function __construct(
        private readonly ForumTranslationService $translationService,
        private readonly ForumBadWordsService $badWordsService,
    ) {
    }

    #[Route('', name: 'user_forum', methods: ['GET'])]
    public function index(
        Request $request,
        PostRepository $postRepository,
        CategorieForumRepository $categorieForumRepository,
        ForumPostBookmarkRepository $bookmarkRepository
    ): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $sort = $this->resolveSort((string) $request->query->get('sort', 'recent'));

        return $this->renderForumIndexPage(
            $request,
            $postRepository,
            $categorieForumRepository,
            $bookmarkRepository,
            $search,
            $sort,
            [
                'titre' => '',
                'contenu' => '',
                'categorie' => '',
            ],
            []
        );
    }

    #[Route('/favoris', name: 'user_forum_favorites', methods: ['GET'])]
    public function favorites(
        Request $request,
        CategorieForumRepository $categorieForumRepository,
        ForumPostBookmarkRepository $bookmarkRepository
    ): Response {
        return $this->renderForumBookmarksPage(
            $request,
            ForumPostBookmark::TYPE_FAVORITE,
            'Mes favoris',
            'Retrouvez ici les publications que vous avez ajoutees aux favoris.',
            $categorieForumRepository,
            $bookmarkRepository
        );
    }

    #[Route('/saved', name: 'user_forum_saved', methods: ['GET'])]
    public function saved(
        Request $request,
        CategorieForumRepository $categorieForumRepository,
        ForumPostBookmarkRepository $bookmarkRepository
    ): Response {
        return $this->renderForumBookmarksPage(
            $request,
            ForumPostBookmark::TYPE_SAVED,
            'A lire plus tard',
            'Retrouvez ici les publications que vous avez enregistrees pour plus tard.',
            $categorieForumRepository,
            $bookmarkRepository
        );
    }

    #[Route('/new', name: 'user_forum_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        PostRepository $postRepository,
        CategorieForumRepository $categorieForumRepository,
        ForumPostBookmarkRepository $bookmarkRepository
    ): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('forum_create', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $titre = trim((string) $request->request->get('titre', ''));
        $contenu = trim((string) $request->request->get('contenu', ''));
        $categorie = $this->sanitizeCategory((string) $request->request->get('categorie', ''));

        if ($response = $this->renderCreateValidationError($titre, $contenu, $categorie, $request, $postRepository, $categorieForumRepository, $bookmarkRepository)) {
            return $response;
        }

        $post = new Post();
        $post
            ->setUtilisateur($user)
            ->setTitre($titre)
            ->setContenu($contenu)
            ->setCategorie($categorie !== '' ? $categorie : null)
            ->setStatut('actif')
            ->setDateCreation(new \DateTime('now', new \DateTimeZone(self::APP_TIMEZONE)));

        $entityManager->persist($post);
        $entityManager->flush();

        $this->queueForumSuccessToast($request, 'Post publié avec succès');

        return $this->redirectToRoute('user_forum');
    }

    #[Route('/{id}', name: 'user_forum_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        Request $request,
        int $id,
        PostRepository $postRepository,
        CategorieForumRepository $categorieForumRepository,
        ForumPostBookmarkRepository $bookmarkRepository
    ): Response
    {
        $post = $postRepository->findOneForForum($id);
        if (!$post) {
            throw $this->createNotFoundException('Post introuvable.');
        }

        $editCommentId = $request->query->getInt('editComment');
        $editableComment = null;

        if ($editCommentId > 0) {
            foreach ($post->getCommentaires() as $commentaire) {
                if ($commentaire->getId() !== $editCommentId) {
                    continue;
                }

                if ($this->canManageComment($commentaire)) {
                    $editableComment = $commentaire;
                }

                break;
            }
        }

        return $this->renderShowPage($request, $post, $editableComment, [], [], $categorieForumRepository, null, $bookmarkRepository);
    }

    #[Route('/{id}/edit', name: 'user_forum_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        CategorieForumRepository $categorieForumRepository
    ): Response
    {
        $this->denyPostAccess($post);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('forum_edit_' . $post->getId(), (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $titre = trim((string) $request->request->get('titre', ''));
            $contenu = trim((string) $request->request->get('contenu', ''));
            $categorie = $this->sanitizeCategory((string) $request->request->get('categorie', ''));
            $categories = $categorieForumRepository->findCategoryNames();

            $errors = $this->getPostValidationErrors($titre, $contenu, $categorie, $categories);
            if ($errors !== []) {
                foreach ($errors as $err) {
                    $this->addFlash('danger', $err);
                }
                return $this->render('user/forum/edit.html.twig', [
                    'post' => $post,
                    'categories' => $categories,
                    'translated_categories' => $this->buildTranslatedForumCategories($categories, $request->getLocale()),
                    'form_data' => [
                        'titre' => $titre,
                        'contenu' => $contenu,
                        'categorie' => $categorie ?? '',
                    ],
                    'form_errors' => $errors,
                ]);
            }

            $post
                ->setTitre($titre)
                ->setContenu($contenu)
                ->setCategorie($categorie !== '' ? $categorie : null);

            $entityManager->flush();

            $this->queueForumSuccessToast($request, 'Post Modifié avec succès');

            return $this->redirectToRoute('user_forum');
        }

        return $this->render('user/forum/edit.html.twig', [
            'post' => $post,
            'categories' => $categorieForumRepository->findCategoryNames(),
            'translated_categories' => $this->buildTranslatedForumCategories($categorieForumRepository->findCategoryNames(), $request->getLocale()),
            'forum_success_toast' => $this->consumeForumSuccessToast($request),
            'form_data' => [
                'titre' => $post->getTitre() ?? '',
                'contenu' => $post->getContenu() ?? '',
                'categorie' => $post->getCategorie() ?? '',
            ],
            'form_errors' => [],
        ]);
    }

    #[Route('/{id}/delete', name: 'user_forum_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Post $post, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $this->denyPostAccess($post);

        if (!$this->isCsrfTokenValid('forum_delete_' . $post->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $entityManager->remove($post);
        $entityManager->flush();

        $this->queueForumSuccessToast($request, 'Post supprimé avec succès');

        return $this->redirectToRoute('user_forum');
    }

    #[Route('/{id}/comment', name: 'user_forum_comment', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function comment(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        CategorieForumRepository $categorieForumRepository,
        ForumModerationAlertRepository $moderationAlertRepository,
        ForumPostBookmarkRepository $bookmarkRepository
    ): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('forum_comment_' . $post->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $contenu = trim((string) $request->request->get('contenu', ''));
        $originalContent = $contenu;
        $imageError = null;
        $image = $this->getValidUploadedImage($request, $imageError);

        if ($imageError !== null) {
            return $this->renderShowPage($request, $post, null, ['contenu' => $contenu], [
                'image' => $imageError,
            ], $categorieForumRepository, $moderationAlertRepository, $bookmarkRepository);
        }

        $errors = $this->getCommentValidationErrors($contenu, $image instanceof UploadedFile);
        if ($image instanceof UploadedFile) {
            $errors = array_merge($errors, $this->getCommentImageValidationErrors($image));
        }

        if ($errors !== []) {
            return $this->renderShowPage($request, $post, null, ['contenu' => $contenu], $errors, $categorieForumRepository, $moderationAlertRepository, $bookmarkRepository);
        }

        $moderation = $this->badWordsService->mask($contenu);
        $maskedContent = $moderation['maskedText'];

        $commentaire = new Commentaire();
        $commentaire
            ->setPost($post)
            ->setUtilisateur($user)
            ->setContenu($maskedContent)
            ->setDateCreation(new \DateTime('now', new \DateTimeZone(self::APP_TIMEZONE)));

        if ($image instanceof UploadedFile) {
            try {
                $commentaire->setImagePath($this->storeCommentImage($image));
            } catch (FileException $exception) {
                return $this->renderShowPage($request, $post, null, ['contenu' => $contenu], [
                    'image' => "L'image n'a pas pu etre enregistree. Veuillez reessayer.",
                ], $categorieForumRepository, $moderationAlertRepository, $bookmarkRepository);
            }
        }

        $entityManager->persist($commentaire);
        $entityManager->flush();

        if (!empty($moderation['matches'])) {
            $alert = new ForumModerationAlert();
            $alert
                ->setCommentaire($commentaire)
                ->setUtilisateur($user)
                ->setOriginalContent($originalContent)
                ->setMaskedContent($maskedContent)
                ->setMatchedWords($moderation['matches'])
                ->setStatus('pending')
                ->setCreatedAt(new \DateTime('now', new \DateTimeZone(self::APP_TIMEZONE)));

            $entityManager->persist($alert);
            $entityManager->flush();
        }

        $this->queueForumSuccessToast($request, 'Commentaire ajouté avec succès');

        return $this->redirectToRoute('user_forum_show', ['id' => $post->getId(), '_fragment' => 'post-comments']);
    }

    #[Route('/comment/{id}/edit', name: 'user_forum_comment_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function editComment(
        Commentaire $commentaire,
        Request $request,
        EntityManagerInterface $entityManager,
        CategorieForumRepository $categorieForumRepository,
        ForumModerationAlertRepository $moderationAlertRepository,
        ForumPostBookmarkRepository $bookmarkRepository
    ): Response
    {
        $this->denyCommentAccess($commentaire);

        $post = $commentaire->getPost();
        if (!$post) {
            throw $this->createNotFoundException('Post introuvable.');
        }

        if (!$this->isCsrfTokenValid('forum_comment_edit_' . $commentaire->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $contenu = trim((string) $request->request->get('contenu', ''));
        $originalContent = $contenu;
        $replaceImage = $request->request->getBoolean('replace_image');
        $removeImage = $request->request->getBoolean('remove_image');
        $imageError = null;
        $image = $this->getValidUploadedImage($request, $imageError);

        if ($imageError !== null) {
            return $this->renderShowPage($request, $post, $commentaire, ['contenu' => $contenu], [
                'image' => $imageError,
            ], $categorieForumRepository, $moderationAlertRepository, $bookmarkRepository);
        }

        $hasImageAfterSubmit = $commentaire->getImagePath() !== null;
        if ($removeImage) {
            $hasImageAfterSubmit = false;
        }
        if ($image instanceof UploadedFile) {
            $hasImageAfterSubmit = true;
        }

        $errors = $this->getCommentValidationErrors($contenu, $hasImageAfterSubmit);
        if ($image instanceof UploadedFile) {
            $errors = array_merge($errors, $this->getCommentImageValidationErrors($image));
        }

        if ($errors !== []) {
            return $this->renderShowPage($request, $post, $commentaire, ['contenu' => $contenu], $errors, $categorieForumRepository, $moderationAlertRepository, $bookmarkRepository);
        }

        $moderation = $this->badWordsService->mask($contenu);
        $maskedContent = $moderation['maskedText'];
        $commentaire->setContenu($maskedContent);

        if ($removeImage && $commentaire->getImagePath() !== null) {
            $this->removeCommentImage($commentaire->getImagePath());
            $commentaire->setImagePath(null);
        }

        if ($replaceImage && $image instanceof UploadedFile) {
            try {
                $newImagePath = $this->storeCommentImage($image);
            } catch (FileException $exception) {
                return $this->renderShowPage($request, $post, $commentaire, ['contenu' => $contenu], [
                    'image' => "L'image n'a pas pu etre enregistree. Veuillez reessayer.",
                ], $categorieForumRepository, $moderationAlertRepository, $bookmarkRepository);
            }

            if ($commentaire->getImagePath() !== null) {
                $this->removeCommentImage($commentaire->getImagePath());
            }

            $commentaire->setImagePath($newImagePath);
        }

        $entityManager->flush();

        if (!empty($moderation['matches'])) {
            $alert = new ForumModerationAlert();
            $alert
                ->setCommentaire($commentaire)
                ->setUtilisateur($this->getUser())
                ->setOriginalContent($originalContent)
                ->setMaskedContent($maskedContent)
                ->setMatchedWords($moderation['matches'])
                ->setStatus('pending')
                ->setCreatedAt(new \DateTime('now', new \DateTimeZone(self::APP_TIMEZONE)));

            $entityManager->persist($alert);
            $entityManager->flush();
        }

        $this->queueForumSuccessToast($request, 'Commentaire modifié avec succès');

        return $this->redirectToRoute('user_forum_show', ['id' => $post->getId(), '_fragment' => 'post-comments']);
    }

    #[Route('/comment/{id}/delete', name: 'user_forum_comment_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteComment(Commentaire $commentaire, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $this->denyCommentAccess($commentaire);

        $post = $commentaire->getPost();
        if (!$post) {
            throw $this->createNotFoundException('Post introuvable.');
        }

        if (!$this->isCsrfTokenValid('forum_comment_delete_' . $commentaire->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($commentaire->getImagePath() !== null) {
            $this->removeCommentImage($commentaire->getImagePath());
        }

        $entityManager->remove($commentaire);
        $entityManager->flush();

        $this->queueForumSuccessToast($request, 'Commentaire supprimé avec succès');

        return $this->redirectToRoute('user_forum_show', ['id' => $post->getId(), '_fragment' => 'post-comments']);
    }

    #[Route('/{id}/reaction', name: 'user_forum_reaction', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reactToPost(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        ReactionPostRepository $reactionPostRepository
    ): RedirectResponse {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Utilisateur non connecte.');
        }

        if (!$this->isCsrfTokenValid('forum_reaction_' . $post->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $reactionType = $this->sanitizeReactionType((string) $request->request->get('reaction', ''));
        if ($reactionType === null) {
            $this->addFlash('danger', 'Reaction invalide.');

            return $this->redirectBackToPost($request, $post);
        }

        $existingReaction = $reactionPostRepository->findOneByPostAndUser($post, $user);
        if ($existingReaction !== null && $existingReaction->getType() === $reactionType) {
            $entityManager->remove($existingReaction);
            $entityManager->flush();

            return $this->redirectBackToPost($request, $post);
        }

        if ($existingReaction === null) {
            $existingReaction = new ReactionPost();
            $existingReaction
                ->setPost($post)
                ->setUtilisateur($user);
            $entityManager->persist($existingReaction);
        }

        $existingReaction
            ->setType($reactionType)
            ->setDateCreation(new \DateTime('now', new \DateTimeZone(self::APP_TIMEZONE)));

        $entityManager->flush();

        return $this->redirectBackToPost($request, $post);
    }

    #[Route('/{id}/bookmark/{type}', name: 'user_forum_bookmark_toggle', requirements: ['id' => '\d+', 'type' => 'favorite|saved'], methods: ['POST'])]
    public function toggleBookmark(
        Post $post,
        string $type,
        Request $request,
        EntityManagerInterface $entityManager,
        ForumPostBookmarkRepository $bookmarkRepository
    ): RedirectResponse {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Utilisateur non connecte.');
        }

        $bookmarkType = $this->sanitizeBookmarkType($type);
        if ($bookmarkType === null) {
            throw $this->createNotFoundException('Type de marque-page introuvable.');
        }

        if (!$this->isCsrfTokenValid('forum_bookmark_' . $post->getId() . '_' . $bookmarkType, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $existingBookmark = $bookmarkRepository->findOneByPostUserType($post, $user, $bookmarkType);

        if ($existingBookmark !== null) {
            $entityManager->remove($existingBookmark);
            $entityManager->flush();

            $this->queueForumSuccessToast(
                $request,
                $bookmarkType === ForumPostBookmark::TYPE_FAVORITE
                    ? 'Publication retiree des favoris.'
                    : 'Publication retiree de la liste de lecture.'
            );

            return $this->redirectBackToPost($request, $post);
        }

        $bookmark = new ForumPostBookmark();
        $bookmark
            ->setPost($post)
            ->setUtilisateur($user)
            ->setBookmarkType($bookmarkType)
            ->setCreatedAt(new \DateTime('now', new \DateTimeZone(self::APP_TIMEZONE)));

        $entityManager->persist($bookmark);
        $entityManager->flush();

        $this->queueForumSuccessToast(
            $request,
            $bookmarkType === ForumPostBookmark::TYPE_FAVORITE
                ? 'Publication ajoutee aux favoris.'
                : 'Publication ajoutee a la liste de lecture.'
        );

        return $this->redirectBackToPost($request, $post);
    }

    private function denyPostAccess(Post $post): void
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();

        if (!$user || $post->getUtilisateur()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce post.');
        }
    }

    private function denyCommentAccess(Commentaire $commentaire): void
    {
        if (!$this->canManageComment($commentaire)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce commentaire.');
        }
    }

    private function canManageComment(Commentaire $commentaire): bool
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();

        return $user !== null && $commentaire->getUtilisateur()?->getId() === $user->getId();
    }

    private function sanitizeCategory(string $categorie): ?string
    {
        $categorie = trim($categorie);

        return $categorie === '' ? null : $categorie;
    }

    /**
     * @return array<string, string>
     */
    private function getPostValidationErrors(string $titre, string $contenu, ?string $categorie, array $availableCategories): array
    {
        $errors = [];

        if ($titre === '') {
            $errors['titre'] = 'Le titre est obligatoire.';
        } elseif (mb_strlen($titre) > 255) {
            $errors['titre'] = 'Le titre ne doit pas depasser 255 caracteres.';
        }

        if ($contenu === '') {
            $errors['contenu'] = 'Le contenu est obligatoire.';
        } elseif (mb_strlen($contenu) < 10) {
            $errors['contenu'] = 'Le contenu doit contenir au moins 10 caracteres.';
        }

        if ($categorie === null || $categorie === '') {
            $errors['categorie'] = 'La categorie est obligatoire.';
        } elseif (!in_array($categorie, $availableCategories, true)) {
            $errors['categorie'] = 'La categorie selectionnee est invalide.';
        }

        return $errors;
    }

    /**
     * @return array<string, string>
     */
    private function getPostModerationErrors(string $titre, string $contenu): array
    {
        $errors = [];

        $titleScan = $this->badWordsService->scan($titre);
        if (!empty($titleScan['blocked'])) {
            $errors['titre'] = 'Le titre contient un mot interdit.';
        }

        $contentScan = $this->badWordsService->scan($contenu);
        if (!empty($contentScan['blocked'])) {
            $errors['contenu'] = 'Le contenu contient un mot interdit.';
        }

        return $errors;
    }

    /**
     * @return array<string, string>
     */
    private function getCommentModerationErrors(string $contenu): array
    {
        $errors = [];
        $scan = $this->badWordsService->scan($contenu);

        if (!empty($scan['blocked'])) {
            $errors['contenu'] = 'Le commentaire contient un mot interdit.';
        }

        return $errors;
    }

    private function renderCreateValidationError(
        string $titre,
        string $contenu,
        ?string $categorie,
        Request $request,
        PostRepository $postRepository,
        CategorieForumRepository $categorieForumRepository,
        ForumPostBookmarkRepository $bookmarkRepository
    ): ?Response {
        $categories = $categorieForumRepository->findCategoryNames();
        $errors = array_merge(
            $this->getPostValidationErrors($titre, $contenu, $categorie, $categories),
            $this->getPostModerationErrors($titre, $contenu)
        );
        if ($errors === []) {
            return null;
        }

        foreach ($errors as $err) {
            $this->addFlash('danger', $err);
        }

        $search = trim((string) $request->query->get('q', ''));
        $sort = $this->resolveSort((string) $request->query->get('sort', 'recent'));

        return $this->renderForumIndexPage(
            $request,
            $postRepository,
            $categorieForumRepository,
            $bookmarkRepository,
            $search,
            $sort,
            [
                'titre' => $titre,
                'contenu' => $contenu,
                'categorie' => $categorie ?? '',
            ],
            $errors
        );
    }

    /**
     * @return array<string, string>
     */
    private function getCommentValidationErrors(string $contenu, bool $hasImage = false): array
    {
        $errors = [];

        if ($contenu === '') {
            $errors['contenu'] = 'Le commentaire est obligatoire.';
        } elseif (mb_strlen($contenu) < self::COMMENT_MIN_LENGTH) {
            $errors['contenu'] = sprintf('Le commentaire doit contenir au moins %d caracteres.', self::COMMENT_MIN_LENGTH);
        } elseif (mb_strlen($contenu) > self::COMMENT_MAX_LENGTH) {
            $errors['contenu'] = sprintf('Le commentaire ne doit pas depasser %d caracteres.', self::COMMENT_MAX_LENGTH);
        }

        return $errors;
    }

    /**
     * @return array<string, string>
     */
    private function getCommentImageValidationErrors(UploadedFile $image): array
    {
        $errors = [];
        $mimeType = (string) $image->getMimeType();
        $extension = $this->resolveAllowedImageExtension($image);

        if (!$image->isValid()) {
            $errors['image'] = $image->getErrorMessage();
        } elseif (!in_array($mimeType, self::COMMENT_IMAGE_MIME_TYPES, true)) {
            $errors['image'] = self::COMMENT_IMAGE_FORMAT_MESSAGE;
        } elseif ($extension === null) {
            $errors['image'] = 'Extension invalide. Utilisez JPG, PNG, WEBP ou GIF.';
        } elseif ($image->getSize() !== null && $image->getSize() > self::COMMENT_IMAGE_MAX_BYTES) {
            $errors['image'] = "L'image ne doit pas depasser 5 Mo.";
        } else {
            $dimensions = @getimagesize($image->getPathname());
            if ($dimensions === false) {
                $errors['image'] = "Impossible de lire les dimensions de l'image.";
            } else {
                [$width, $height] = $dimensions;

                if ($width < self::COMMENT_IMAGE_MIN_WIDTH || $height < self::COMMENT_IMAGE_MIN_HEIGHT) {
                    $errors['image'] = sprintf(
                        "Image trop petite : %dx%d px. Minimum requis : %dx%d px.",
                        $width,
                        $height,
                        self::COMMENT_IMAGE_MIN_WIDTH,
                        self::COMMENT_IMAGE_MIN_HEIGHT
                    );
                } elseif ($width > self::COMMENT_IMAGE_MAX_WIDTH || $height > self::COMMENT_IMAGE_MAX_HEIGHT) {
                    $errors['image'] = sprintf(
                        "Image trop grande : %dx%d px. Maximum autorise : %dx%d px.",
                        $width,
                        $height,
                        self::COMMENT_IMAGE_MAX_WIDTH,
                        self::COMMENT_IMAGE_MAX_HEIGHT
                    );
                }
            }
        }

        return $errors;
    }

    private function getValidUploadedImage(Request $request, ?string &$error = null): ?UploadedFile
    {
        $uploaded = $request->files->get('image');

        if ($uploaded === null) {
            return null;
        }

        if (!$uploaded instanceof UploadedFile) {
            $error = 'Le fichier image transmis est invalide.';

            return null;
        }

        if (!$uploaded->isValid()) {
            $error = $uploaded->getErrorMessage();

            return null;
        }

        return $uploaded;
    }

    private function resolveAllowedImageExtension(UploadedFile $image): ?string
    {
        $candidates = [
            strtolower((string) $image->guessExtension()),
            strtolower((string) $image->getClientOriginalExtension()),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && in_array($candidate, self::COMMENT_IMAGE_EXTENSIONS, true)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array{contenu?: string} $commentFormData
     * @param array<string, string> $commentFormErrors
     */
    private function renderShowPage(
        Request $request,
        Post $post,
        ?Commentaire $editableComment = null,
        array $commentFormData = [],
        array $commentFormErrors = [],
        ?CategorieForumRepository $categorieForumRepository = null,
        ?ForumModerationAlertRepository $moderationAlertRepository = null,
        ?ForumPostBookmarkRepository $bookmarkRepository = null
    ): Response {
        foreach ($commentFormErrors as $err) {
            $this->addFlash('danger', $err);
        }
        $locale = $request->getLocale();
        $alertsByComment = [];
        if ($moderationAlertRepository !== null) {
            $commentIds = [];
            foreach ($post->getCommentaires() as $commentaire) {
                if ($commentaire->getId() !== null) {
                    $commentIds[] = $commentaire->getId();
                }
            }

            foreach ($moderationAlertRepository->findByCommentIds($commentIds) as $alert) {
                $commentId = $alert->getCommentaire()?->getId();
                if ($commentId === null) {
                    continue;
                }

                $alertsByComment[$commentId][] = $alert;
            }
        }

        $bookmarkFlags = $bookmarkRepository !== null
            ? $this->buildBookmarkFlags($this->getUser(), $bookmarkRepository)
            : ['favorite' => [], 'saved' => []];

        return $this->render('user/forum/show.html.twig', [
            'post' => $post,
            'translated_post' => $this->buildTranslatedForumPost($post, $locale),
            'translated_comments' => $this->buildTranslatedForumComments($post->getCommentaires(), $locale),
            'translated_category' => $this->translateForumCategory($post->getCategorie() ?? '', $locale),
            'alerts_by_comment' => $alertsByComment,
            'favorite_bookmarked_ids' => $bookmarkFlags['favorite'],
            'saved_bookmarked_ids' => $bookmarkFlags['saved'],
            'forum_active_section' => 'feed',
            'categories' => $categorieForumRepository?->findCategoryNames() ?? [],
            'forum_success_toast' => $this->consumeForumSuccessToast($request),
            'edit_comment_id' => $editableComment?->getId(),
            'comment_form_data' => [
                'contenu' => $commentFormData['contenu'] ?? $editableComment?->getContenu() ?? '',
            ],
            'comment_form_errors' => $commentFormErrors,
        ]);
    }

    /**
     * @throws FileException
     */
    private function storeCommentImage(UploadedFile $image): string
    {
        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $uploadDirectory = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . self::COMMENT_IMAGE_DIRECTORY;

        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0777, true) && !is_dir($uploadDirectory)) {
            throw new FileException("Impossible de creer le dossier d'upload.");
        }

        $extension = $this->resolveAllowedImageExtension($image);
        if ($extension === null) {
            throw new FileException(self::COMMENT_IMAGE_FORMAT_MESSAGE);
        }

        $fileName = sprintf('comment-%s.%s', bin2hex(random_bytes(8)), $extension);

        $image->move($uploadDirectory, $fileName);

        return self::COMMENT_IMAGE_DIRECTORY . '/' . $fileName;
    }

    private function removeCommentImage(string $imagePath): void
    {
        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $absolutePath = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $imagePath);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function resolveSort(string $sort): string
    {
        return in_array($sort, self::FORUM_SORT_OPTIONS, true) ? $sort : 'recent';
    }

    /**
     * @param array{titre: string, contenu: string, categorie: string} $formData
     * @param array<string, string> $formErrors
     */
    private function renderForumIndexPage(
        Request $request,
        PostRepository $postRepository,
        CategorieForumRepository $categorieForumRepository,
        ForumPostBookmarkRepository $bookmarkRepository,
        string $search,
        string $sort,
        array $formData,
        array $formErrors
    ): Response {
        $locale = $request->getLocale();
        $categories = $categorieForumRepository->findCategoryNames();
        $posts = $postRepository->findForumFeed(null, $sort);
        $translatedPosts = $this->buildTranslatedForumPosts($posts, $locale);
        $bookmarkFlags = $this->buildBookmarkFlags($this->getUser(), $bookmarkRepository);

        if ($search !== '' && mb_strlen($search) >= 3) {
            [$posts, $translatedPosts] = $this->filterForumPostsBySearch($posts, $translatedPosts, $search);
        }

        return $this->render('user/forum/index.html.twig', [
            'posts' => $posts,
            'translated_posts' => $translatedPosts,
            'translated_categories' => $this->buildTranslatedForumCategories($categories, $locale),
            'favorite_bookmarked_ids' => $bookmarkFlags['favorite'],
            'saved_bookmarked_ids' => $bookmarkFlags['saved'],
            'forum_active_section' => 'feed',
            'search' => $search,
            'sort' => $sort,
            'categories' => $categories,
            'forum_success_toast' => $this->consumeForumSuccessToast($request),
            'form_data' => $formData,
            'form_errors' => $formErrors,
        ]);
    }

    private function renderForumBookmarksPage(
        Request $request,
        string $bookmarkType,
        string $pageTitle,
        string $pageLead,
        CategorieForumRepository $categorieForumRepository,
        ForumPostBookmarkRepository $bookmarkRepository
    ): Response {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $locale = $request->getLocale();
        $categories = $categorieForumRepository->findCategoryNames();
        $posts = $bookmarkRepository->findPostsByUserAndType($user, $bookmarkType);
        $translatedPosts = $this->buildTranslatedForumPosts($posts, $locale);
        $bookmarkFlags = $this->buildBookmarkFlags($user, $bookmarkRepository);

        return $this->render('user/forum/bookmarks.html.twig', [
            'posts' => $posts,
            'translated_posts' => $translatedPosts,
            'translated_categories' => $this->buildTranslatedForumCategories($categories, $locale),
            'favorite_bookmarked_ids' => $bookmarkFlags['favorite'],
            'saved_bookmarked_ids' => $bookmarkFlags['saved'],
            'forum_active_section' => $bookmarkType === ForumPostBookmark::TYPE_FAVORITE ? 'favorites' : 'saved',
            'forum_page_title' => $pageTitle,
            'forum_page_lead' => $pageLead,
            'bookmark_type' => $bookmarkType,
            'bookmark_total' => count($posts),
            'forum_success_toast' => $this->consumeForumSuccessToast($request),
            'forum_back_to_feed' => $this->generateUrl('user_forum'),
            'forum_favorites_link' => $this->generateUrl('user_forum_favorites'),
            'forum_saved_link' => $this->generateUrl('user_forum_saved'),
        ]);
    }

    /**
     * @param iterable<int, Post> $posts
     * @return array<int, array{title: string, content: string}>
     */
    private function buildTranslatedForumPosts(iterable $posts, string $locale): array
    {
        $translatedPosts = [];

        foreach ($posts as $post) {
            if (!$post instanceof Post) {
                continue;
            }

            $translatedPosts[$post->getId()] = $this->buildTranslatedForumPost($post, $locale);
        }

        return $translatedPosts;
    }

    /**
     * @param list<Post> $posts
     * @param array<int, array{title: string, content: string}> $translatedPosts
     * @return array{0: list<Post>, 1: array<int, array{title: string, content: string}>}
     */
    private function filterForumPostsBySearch(array $posts, array $translatedPosts, string $search): array
    {
        $search = trim($search);
        if ($search === '' || mb_strlen($search) < 3) {
            return [$posts, $translatedPosts];
        }

        $visiblePosts = [];
        $visiblePostIds = [];

        foreach ($posts as $post) {
            if (!$post instanceof Post) {
                continue;
            }

            $postId = $post->getId();
            if ($postId === null) {
                continue;
            }

            $originalTitle = $post->getTitre() ?? '';
            $translatedTitle = $translatedPosts[$postId]['title'] ?? '';

            if (
                $this->forumTextMatchesSearch($originalTitle, $search)
                || $this->forumTextMatchesSearch($translatedTitle, $search)
            ) {
                $visiblePosts[] = $post;
                $visiblePostIds[$postId] = true;
            }
        }

        $visibleTranslatedPosts = array_intersect_key($translatedPosts, $visiblePostIds);

        return [$visiblePosts, $visibleTranslatedPosts];
    }

    /**
     * @return array{title: string, content: string}
     */
    private function buildTranslatedForumPost(Post $post, string $locale): array
    {
        return [
            'title' => $this->translateAndMaskForumText($post->getTitre() ?? '', $locale),
            'content' => $this->translateAndMaskForumText($post->getContenu() ?? '', $locale),
        ];
    }

    /**
     * @param list<string> $categories
     * @return array<string, string>
     */
    private function buildTranslatedForumCategories(array $categories, string $locale): array
    {
        $translatedCategories = [];

        foreach ($categories as $category) {
            $translatedCategories[$category] = $this->translateForumCategory($category, $locale);
        }

        return $translatedCategories;
    }

    /**
     * @param iterable<int, Commentaire> $comments
     * @return array<int, array{content: string}>
     */
    private function buildTranslatedForumComments(iterable $comments, string $locale): array
    {
        $translatedComments = [];

        foreach ($comments as $commentaire) {
            if (!$commentaire instanceof Commentaire) {
                continue;
            }

            $translatedComments[$commentaire->getId()] = [
                'content' => $this->translateAndMaskForumText($commentaire->getContenu() ?? '', $locale),
            ];
        }

        return $translatedComments;
    }

    /**
     * @return array{favorite: array<int, true>, saved: array<int, true>}
     */
    private function buildBookmarkFlags(?Utilisateur $user, ForumPostBookmarkRepository $bookmarkRepository): array
    {
        if (!$user || $user->getId() === null) {
            return [
                'favorite' => [],
                'saved' => [],
            ];
        }

        return [
            'favorite' => array_fill_keys($bookmarkRepository->findPostIdsByUserAndType($user, ForumPostBookmark::TYPE_FAVORITE), true),
            'saved' => array_fill_keys($bookmarkRepository->findPostIdsByUserAndType($user, ForumPostBookmark::TYPE_SAVED), true),
        ];
    }

    private function translateForumText(string $text, string $locale): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $targetLanguage = $this->resolveDeepLTargetLanguage($locale);
        if ($targetLanguage === null) {
            return $text;
        }

        // Posts are authored in French. Skip the DeepL round-trip when the UI
        // is already in French to avoid N synchronous HTTP calls per page load.
        if (str_starts_with($targetLanguage, 'FR')) {
            return $text;
        }

        try {
            $result = $this->translationService->translate($text, $targetLanguage);
            $translatedText = trim((string) ($result['text'] ?? ''));
            return $translatedText !== '' ? $translatedText : $text;
        } catch (\Throwable) {
            return $text;
        }
    }

    private function translateAndMaskForumText(string $text, string $locale): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if ($this->badWordsService->isFullyMaskedProfanity($text)) {
            return $text;
        }

        $preMaskedText = $this->badWordsService->mask($text)['maskedText'];
        if ($this->badWordsService->isStandaloneProfanity($text)) {
            return $preMaskedText;
        }

        $translatedText = $this->translateForumText($preMaskedText, $locale);

        return $this->badWordsService->mask($translatedText)['maskedText'];
    }

    private function translateForumCategory(string $category, string $locale): string
    {
        $category = trim($category);
        if ($category === '') {
            return '';
        }

        return $this->translateForumText($category, $locale);
    }

    private function forumTextMatchesSearch(string $text, string $search): bool
    {
        $textVariants = $this->buildForumSearchVariants($text);
        $searchVariants = $this->buildForumSearchVariants($search);

        foreach ($searchVariants as $searchVariant) {
            if ($searchVariant === '') {
                continue;
            }

            foreach ($textVariants as $textVariant) {
                if ($textVariant !== '' && str_starts_with($textVariant, $searchVariant)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function buildForumSearchVariants(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $variants = [mb_strtolower($text)];
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $variants[0]);

        if (is_string($ascii) && trim($ascii) !== '') {
            $variants[] = mb_strtolower($ascii);
        }

        return array_values(array_unique($variants));
    }

    private function resolveDeepLTargetLanguage(string $locale): ?string
    {
        return match ($locale) {
            'fr' => 'FR',
            'en' => 'EN-GB',
            'de' => 'DE',
            'es' => 'ES',
            'it' => 'IT',
            'nl' => 'NL',
            'pt' => 'PT-PT',
            'pl' => 'PL',
            default => null,
        };
    }

    private function sanitizeReactionType(string $reactionType): ?string
    {
        $reactionType = trim(mb_strtolower($reactionType));

        return in_array($reactionType, self::FORUM_REACTION_TYPES, true) ? $reactionType : null;
    }

    private function sanitizeBookmarkType(string $bookmarkType): ?string
    {
        $bookmarkType = trim(mb_strtolower($bookmarkType));

        return in_array($bookmarkType, [ForumPostBookmark::TYPE_FAVORITE, ForumPostBookmark::TYPE_SAVED], true) ? $bookmarkType : null;
    }

    private function redirectBackToPost(Request $request, Post $post): RedirectResponse
    {
        $returnTo = trim((string) $request->request->get('return_to', ''));
        if ($returnTo !== '' && str_starts_with($returnTo, '/')) {
            return $this->redirect($returnTo);
        }

        return $this->redirectToRoute('user_forum_show', ['id' => $post->getId(), '_fragment' => 'post-detail']);
    }

    private function queueForumSuccessToast(Request $request, string $message): void
    {
        if (!$request->hasSession()) {
            return;
        }

        $request->getSession()->set('forum_success_toast', [
            'message' => $message,
            'type' => 'success',
            'duration' => 10000,
        ]);
    }

    private function consumeForumSuccessToast(Request $request): ?array
    {
        if (!$request->hasSession()) {
            return null;
        }

        $session = $request->getSession();
        $toast = $session->get('forum_success_toast');
        if (!is_array($toast)) {
            return null;
        }

        $session->remove('forum_success_toast');

        return $toast;
    }
}
