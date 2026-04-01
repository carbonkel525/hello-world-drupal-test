<?php

declare(strict_types=1);

namespace Drupal\todo\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Logs visits to the todo route.
 */
final class TodoRequestSubscriber implements EventSubscriberInterface
{

  /**
   * Channel logger for todo module.
   */
  private LoggerInterface $logger;

  /**
   * Creates subscriber instance.
   */
  public function __construct(LoggerInterface $logger)
  {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array
  {
    return [
      KernelEvents::REQUEST => 'onRequest',
    ];
  }

  /**
   * Logs when /todo route is requested.
   */
  public function onRequest(RequestEvent $event): void
  {
    if (!$event->isMainRequest()) {
      return;
    }

    $request = $event->getRequest();
    if ($request->attributes->get('_route') !== 'todo.overview') {
      return;
    }

    $this->logger->info('Todo page visited.');
  }

}
