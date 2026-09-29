<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Tracing;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Stamps a message at the moment it is sent, before any middleware — the only
 * moment its cause is certain (see {@see Tracer}).
 */
final readonly class TracingMessageBus implements MessageBusInterface
{
    public function __construct(
        private MessageBusInterface $bus,
        private Tracer $tracer,
    ) {}

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        return $this->bus->dispatch($this->tracer->stamp(Envelope::wrap($message, $stamps)));
    }
}
