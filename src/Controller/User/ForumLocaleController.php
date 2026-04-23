<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user/forum')]
#[IsGranted('ROLE_USER')]
class ForumLocaleController extends AbstractController
{
    private const SUPPORTED_LOCALES = ['fr', 'en', 'de', 'es', 'it', 'nl', 'pt', 'pl'];

    public function __construct(protected readonly TranslatorInterface $translator)
    {
    }

    #[Route('/langue/{locale}', name: 'user_forum_locale', requirements: ['locale' => 'fr|en|de|es|it|nl|pt|pl'], methods: ['GET'])]
    public function switchLocale(string $locale, Request $request): RedirectResponse
    {
        $request->getSession()->set('forum_locale', $locale);

        $referer = $request->headers->get('referer');
        $host = $request->getSchemeAndHttpHost();

        if (is_string($referer) && str_starts_with($referer, $host)) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('user_forum');
    }

    /**
     * @return string one of the supported locale codes
     */
    protected function resolveForumLocale(Request $request): string
    {
        $locale = $request->getSession()->get('forum_locale', $request->getPreferredLanguage(self::SUPPORTED_LOCALES) ?: 'fr');

        return in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'fr';
    }

    protected function applyForumLocale(Request $request): string
    {
        $locale = $this->resolveForumLocale($request);
        $request->setLocale($locale);

        return $locale;
    }

    protected function t(string $key, array $parameters = []): string
    {
        return $this->translator->trans($key, $parameters, 'forum');
    }
}
