<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Event;

use AlexandreBulete\DddFoundation\Application\Event\EventDispatcherInterface;
use AlexandreBulete\DddFoundation\Domain\Event\DomainEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;

final readonly class SymfonyEventDispatcher implements EventDispatcherInterface
{
    public function __construct(private SymfonyEventDispatcherInterface $eventDispatcher)
    {
    }

    public function dispatch(DomainEvent $event): void
    {
        $this->eventDispatcher->dispatch($event, $event::class);
    }
}

