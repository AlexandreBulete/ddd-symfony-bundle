<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Tracing;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Who asked for this message. Travels with it through async transports, so a
 * worker knows on whose behalf it runs.
 */
final readonly class ActorStamp implements StampInterface
{
    public function __construct(
        public Actor $actor,
    ) {}
}
