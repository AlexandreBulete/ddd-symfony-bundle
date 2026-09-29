<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Tracing;

use Symfony\Contracts\Service\ResetInterface;

/**
 * The chain being handled right now, and who acts in it.
 *
 * {@see current()} is what a journal reads. {@see runAs()} is how an entry
 * point that Symfony Security does not see — an agent runtime, a Slack webhook
 * — says on whose behalf the messages it sends are.
 *
 * Stateful by nature: reset between two requests or two worker messages.
 */
final class TraceContext implements ResetInterface
{
    /** @var list<Trace> */
    private array $stack = [];

    /** @var list<array{actor: Actor, channel: string}> */
    private array $overrides = [];

    public function current(): ?Trace
    {
        return $this->stack === [] ? null : $this->stack[array_key_last($this->stack)];
    }

    /**
     * Runs $operation with $actor as the actor of every message it sends,
     * entering through $channel.
     *
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function runAs(Actor $actor, string $channel, callable $operation): mixed
    {
        $this->overrides[] = ['actor' => $actor, 'channel' => $channel];

        try {
            return $operation();
        } finally {
            array_pop($this->overrides);
        }
    }

    /**
     * @internal for {@see Tracer}
     *
     * @return array{actor: Actor, channel: string}|null
     */
    public function override(): ?array
    {
        return $this->overrides === [] ? null : $this->overrides[array_key_last($this->overrides)];
    }

    /**
     * @internal for {@see TracingMiddleware}
     *
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function within(Trace $trace, callable $operation): mixed
    {
        $this->stack[] = $trace;

        try {
            return $operation();
        } finally {
            array_pop($this->stack);
        }
    }

    public function reset(): void
    {
        $this->stack = [];
        $this->overrides = [];
    }
}
