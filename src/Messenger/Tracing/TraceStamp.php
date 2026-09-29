<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Tracing;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Where a message sits in a chain of causes.
 *
 * - `messageId`: this message.
 * - `correlationId`: the whole chain — the id of the message that started it.
 * - `causationId`: the message being handled when this one was sent; null for
 *   the message that started the chain.
 * - `channel`: how the chain entered the application (http, cli, slack, agent…).
 */
final readonly class TraceStamp implements StampInterface
{
    public function __construct(
        public string $messageId,
        public string $correlationId,
        public ?string $causationId,
        public string $channel,
    ) {}

    public function isRoot(): bool
    {
        return $this->causationId === null;
    }
}
