<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ForumLocaleSubscriber implements EventSubscriberInterface
{
    private const SUPPORTED_LOCALES = ['fr', 'en', 'de', 'es', 'it', 'nl', 'pt', 'pl'];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route', '');

        if ($route === '' || !str_starts_with($route, 'user_forum')) {
            return;
        }

        $session = $request->hasSession() ? $request->getSession() : null;
        $locale = $request->attributes->get('_locale');

        if (!is_string($locale) || !in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = $session?->get('forum_locale');
        }

        if (!is_string($locale) || !in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = $request->getPreferredLanguage(self::SUPPORTED_LOCALES) ?: 'fr';
        }

        $request->setLocale($locale);
    }
}
