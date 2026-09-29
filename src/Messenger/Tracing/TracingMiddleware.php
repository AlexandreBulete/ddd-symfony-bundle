<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Tracing;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * Makes a message's trace the current one while it is handled, so that what
 * it sends in turn finds its cause.
 *
 * Messages sent through a {@see TracingMessageBus} arrive stamped; on a bus
 * that is not decorated, the stamps are set here — a deferred message then
 * loses its cause, the rest is the same.
 */
final readonly class TracingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TraceContext $context,
        private Tracer $tracer,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $envelope = $this->tracer->stamp($envelope);

        return $this->context->within(
            Tracer::traceOf($envelope),
            static fn (): Envelope => $stack->next()->handle($envelope, $stack),
        );
    }
}
