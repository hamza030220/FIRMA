<?php

namespace App\EventSubscriber;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;

class DatabaseTimezoneSubscriber implements EventSubscriberInterface
{
    private const DB_TIMEZONE_OFFSET = '+01:00';

    public function __construct(private readonly ManagerRegistry $doctrine)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $connection = $this->doctrine->getConnection();
        if (!$connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            return;
        }

        $connection->executeStatement("SET time_zone = '" . self::DB_TIMEZONE_OFFSET . "'");
    }
}
