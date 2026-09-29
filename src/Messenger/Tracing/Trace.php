<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Tracing;

/**
 * The actor and position in the chain of the message being handled — what a
 * journal or an audit writer reads from {@see TraceContext::current()}.
 */
final readonly class Trace
{
    public function __construct(
        public Actor $actor,
        public TraceStamp $stamp,
    ) {}
}
