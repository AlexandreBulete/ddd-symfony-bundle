<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Tracing;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Uid\Ulid;

/**
 * Decides who acts and where a message sits in its chain (ADR 0008/0009):
 *
 * - an explicit ActorStamp, then {@see TraceContext::runAs()}, win;
 * - a message sent while another is handled is sent by the system — a
 *   reaction coded in the application — and inherits the chain;
 * - otherwise the chain starts here: the signed-in user, or the system;
 * - a message consumed from a transport keeps what it was sent with.
 *
 * Must run when the message is sent, not when it is handled: Messenger may
 * defer the handling (DispatchAfterCurrentBusStamp) past the end of its cause.
 */
final readonly class Tracer
{
    public function __construct(
        private TraceContext $context,
        private ActorResolverInterface $actors,
    ) {}

    /**
     * Idempotent: an envelope already traced is returned as is.
     */
    public function stamp(Envelope $envelope): Envelope
    {
        if ($envelope->last(TraceStamp::class) !== null && $envelope->last(ActorStamp::class) !== null) {
            return $envelope;
        }

        $trace = $this->traceFor($envelope);

        return $envelope
            ->withoutAll(ActorStamp::class)
            ->withoutAll(TraceStamp::class)
            ->with(new ActorStamp($trace->actor), $trace->stamp);
    }

    /**
     * The trace an envelope carries — to be called on a stamped envelope.
     */
    public static function traceOf(Envelope $envelope): Trace
    {
        $actor = $envelope->last(ActorStamp::class);
        $trace = $envelope->last(TraceStamp::class);
        if ($actor === null || $trace === null) {
            throw new \LogicException(sprintf('Envelope of %s is not traced.', $envelope->getMessage()::class));
        }

        return new Trace($actor->actor, $trace);
    }

    private function traceFor(Envelope $envelope): Trace
    {
        // Consumed without a trace: sent by something that bypassed the traced
        // buses (the Scheduler, a message from before tracing existed).
        if ($envelope->last(ReceivedStamp::class) !== null) {
            return self::root(Actor::system(), 'worker');
        }

        $parent = $this->context->current();
        $override = $this->context->override();

        $actor = $envelope->last(ActorStamp::class)->actor
            ?? $override['actor']
            ?? ($parent !== null ? Actor::system() : ($this->actors->resolve() ?? Actor::system()));
        $channel = $override['channel'] ?? $parent?->stamp->channel ?? self::entryChannel();

        if ($parent === null) {
            return self::root($actor, $channel);
        }

        return new Trace($actor, new TraceStamp(
            messageId: (string) new Ulid(),
            correlationId: $parent->stamp->correlationId,
            causationId: $parent->stamp->messageId,
            channel: $channel,
        ));
    }

    private static function root(Actor $actor, string $channel): Trace
    {
        $id = (string) new Ulid();

        return new Trace($actor, new TraceStamp($id, $id, null, $channel));
    }

    private static function entryChannel(): string
    {
        return \PHP_SAPI === 'cli' ? 'cli' : 'http';
    }
}
