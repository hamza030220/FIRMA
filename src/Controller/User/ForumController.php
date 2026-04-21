<?php

namespace App\Controller\User;

use App\Entity\Forum\Commentaire;
use App\Entity\Forum\Post;
use App\Entity\User\Utilisateur;
use App\Repository\Forum\CategorieForumRepository;
use App\Repository\Forum\PostRepository;
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

    #[Route('', name: 'user_forum', methods: ['GET'])]
    public function index(Request $request, PostRepository $postRepository, CategorieForumRepository $categorieForumRepository): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $sort = $this->resolveSort((string) $request->query->get('sort', 'recent'));

        $allPosts = $postRepository->findForumFeed($search, $sort);

        // Pagination
        $page  = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $totalPosts = count($allPosts);
        $totalPages = max(1, (int) ceil($totalPosts / $limit));
        $page = min($page, $totalPages);
        $posts = array_slice($allPosts, ($page - 1) * $limit, $limit);

        return $this->render('user/forum/index.html.twig', [
            'posts' => $posts,
            'search' => $search,
            'sort' => $sort,
            'categories' => $categorieForumRepository->findCategoryNames(),
            'form_data' => [
                'titre' => '',
                'contenu' => '',
                'categorie' => '',
            ],
            'form_errors' => [],
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/new', name: 'user_forum_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        PostRepository $postRepository,
        CategorieForumRepository $categorieForumRepository
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

        if ($response = $this->renderCreateValidationError($titre, $contenu, $categorie, $request, $postRepository, $categorieForumRepository)) {
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

        $this->addFlash('success', 'Votre post a ete publie avec succes.');

        return $this->redirectToRoute('user_forum');
    }

    #[Route('/{id}', name: 'user_forum_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Request $request, int $id, PostRepository $postRepository, CategorieForumRepository $categorieForumRepository): Response
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

        return $this->renderShowPage($post, $editableComment, [], [], $categorieForumRepository);
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

            $errors = $this->getPostValidationErrors($titre, $contenu, $categorie, $categorieForumRepository->findCategoryNames());
            if ($errors !== []) {
                foreach ($errors as $err) {
                    $this->addFlash('danger', $err);
                }
                return $this->render('user/forum/edit.html.twig', [
                    'post' => $post,
                    'categories' => $categorieForumRepository->findCategoryNames(),
                    'form_data' => [
                        'titre' => $titre,
                        'contenu' => $contenu,
                        'categorie' => $categorie ?? '',
                    ],
                    'form_errors' => [],
                ]);
            }

            $post
                ->setTitre($titre)
                ->setContenu($contenu)
                ->setCategorie($categorie !== '' ? $categorie : null);

            $entityManager->flush();

            $this->addFlash('success', 'Votre post a ete modifie avec succes.');

            return $this->redirectToRoute('user_forum');
        }

        return $this->render('user/forum/edit.html.twig', [
            'post' => $post,
            'categories' => $categorieForumRepository->findCategoryNames(),
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

        $this->addFlash('success', 'Votre post a ete supprime.');

        return $this->redirectToRoute('user_forum');
    }

    #[Route('/{id}/comment', name: 'user_forum_comment', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function comment(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        CategorieForumRepository $categorieForumRepository
    ): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('forum_comment_' . $post->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $contenu = trim((string) $request->request->get('contenu', ''));
        $imageError = null;
        $image = $this->getValidUploadedImage($request, $imageError);

        if ($imageError !== null) {
            return $this->renderShowPage($post, null, ['contenu' => $contenu], [
                'image' => $imageError,
            ], $categorieForumRepository);
        }

        $errors = $this->getCommentValidationErrors($contenu, $image instanceof UploadedFile);
        if ($image instanceof UploadedFile) {
            $errors = array_merge($errors, $this->getCommentImageValidationErrors($image));
        }

        if ($errors !== []) {
            return $this->renderShowPage($post, null, ['contenu' => $contenu], $errors, $categorieForumRepository);
        }

        $commentaire = new Commentaire();
        $commentaire
            ->setPost($post)
            ->setUtilisateur($user)
            ->setContenu($contenu)
            ->setDateCreation(new \DateTime('now', new \DateTimeZone(self::APP_TIMEZONE)));

        if ($image instanceof UploadedFile) {
            try {
                $commentaire->setImagePath($this->storeCommentImage($image));
            } catch (FileException $exception) {
                return $this->renderShowPage($post, null, ['contenu' => $contenu], [
                    'image' => "L'image n'a pas pu etre enregistree. Veuillez reessayer.",
                ], $categorieForumRepository);
            }
        }

        $entityManager->persist($commentaire);
        $entityManager->flush();

        $this->addFlash('success', 'Commentaire ajoute.');

        return $this->redirectToRoute('user_forum_show', ['id' => $post->getId(), '_fragment' => 'post-comments']);
    }

    #[Route('/comment/{id}/edit', name: 'user_forum_comment_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function editComment(
        Commentaire $commentaire,
        Request $request,
        EntityManagerInterface $entityManager,
        CategorieForumRepository $categorieForumRepository
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
        $replaceImage = $request->request->getBoolean('replace_image');
        $removeImage = $request->request->getBoolean('remove_image');
        $imageError = null;
        $image = $this->getValidUploadedImage($request, $imageError);

        if ($imageError !== null) {
            return $this->renderShowPage($post, $commentaire, ['contenu' => $contenu], [
                'image' => $imageError,
            ], $categorieForumRepository);
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
            return $this->renderShowPage($post, $commentaire, ['contenu' => $contenu], $errors, $categorieForumRepository);
        }

        $commentaire->setContenu($contenu);

        if ($removeImage && $commentaire->getImagePath() !== null) {
            $this->removeCommentImage($commentaire->getImagePath());
            $commentaire->setImagePath(null);
        }

        if ($replaceImage && $image instanceof UploadedFile) {
            try {
                $newImagePath = $this->storeCommentImage($image);
            } catch (FileException $exception) {
                return $this->renderShowPage($post, $commentaire, ['contenu' => $contenu], [
                    'image' => "L'image n'a pas pu etre enregistree. Veuillez reessayer.",
                ], $categorieForumRepository);
            }

            if ($commentaire->getImagePath() !== null) {
                $this->removeCommentImage($commentaire->getImagePath());
            }

            $commentaire->setImagePath($newImagePath);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Votre commentaire a ete modifie.');

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

        $this->addFlash('success', 'Votre commentaire a ete supprime.');

        return $this->redirectToRoute('user_forum_show', ['id' => $post->getId(), '_fragment' => 'post-comments']);
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

    private function renderCreateValidationError(
        string $titre,
        string $contenu,
        ?string $categorie,
        Request $request,
        PostRepository $postRepository,
        CategorieForumRepository $categorieForumRepository
    ): ?Response {
        $categories = $categorieForumRepository->findCategoryNames();
        $errors = $this->getPostValidationErrors($titre, $contenu, $categorie, $categories);
        if ($errors === []) {
            return null;
        }

        foreach ($errors as $err) {
            $this->addFlash('danger', $err);
        }

        $search = trim((string) $request->query->get('q', ''));
        $sort = $this->resolveSort((string) $request->query->get('sort', 'recent'));

        return $this->render('user/forum/index.html.twig', [
            'posts' => $postRepository->findForumFeed($search, $sort),
            'search' => $search,
            'sort' => $sort,
            'categories' => $categories,
            'form_data' => [
                'titre' => $titre,
                'contenu' => $contenu,
                'categorie' => $categorie ?? '',
            ],
            'form_errors' => [],
        ]);
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
        Post $post,
        ?Commentaire $editableComment = null,
        array $commentFormData = [],
        array $commentFormErrors = [],
        ?CategorieForumRepository $categorieForumRepository = null
    ): Response {
        foreach ($commentFormErrors as $err) {
            $this->addFlash('danger', $err);
        }

        return $this->render('user/forum/show.html.twig', [
            'post' => $post,
            'categories' => $categorieForumRepository?->findCategoryNames() ?? [],
            'edit_comment_id' => $editableComment?->getId(),
            'comment_form_data' => [
                'contenu' => $commentFormData['contenu'] ?? $editableComment?->getContenu() ?? '',
            ],
            'comment_form_errors' => [],
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
}
