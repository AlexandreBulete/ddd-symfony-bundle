<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Unit\Messenger\Tracing;

use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Actor;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\ActorKind;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\ActorResolverInterface;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\ActorStamp;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Trace;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\TraceContext;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Tracer;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\TraceStamp;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\TracingMessageBus;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\TracingMiddleware;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\DispatchAfterCurrentBusMiddleware;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

final class TracingMiddlewareTest extends TestCase
{
    private TraceContext $context;
    private ?Actor $signedIn = null;

    /** @var array<string, list<Trace>> message name => traces seen by its handler */
    private array $seen = [];

    /** @var array<string, \Closure(): mixed> message name => what its handler does next */
    private array $then = [];

    private MessageBusInterface $bus;

    protected function setUp(): void
    {
        $this->context = new TraceContext();
        $resolver = new class ($this) implements ActorResolverInterface {
            public function __construct(private TracingMiddlewareTest $test) {}

            public function resolve(): ?Actor
            {
                return $this->test->signedIn();
            }
        };

        $handler = function (Message $message): void {
            $trace = $this->context->current();
            self::assertNotNull($trace, 'a message is handled within its trace');
            $this->seen[$message->name][] = $trace;
            ($this->then[$message->name] ?? static function (): void {})();
        };

        $tracer = new Tracer($this->context, $resolver);
        $this->bus = new TracingMessageBus(new MessageBus([
            new DispatchAfterCurrentBusMiddleware(),
            new TracingMiddleware($this->context, $tracer),
            new HandleMessageMiddleware(new HandlersLocator([Message::class => [$handler]])),
        ]), $tracer);
    }

    public function signedIn(): ?Actor
    {
        return $this->signedIn;
    }

    #[Test]
    public function a_chain_starts_with_the_signed_in_user(): void
    {
        $this->signedIn = Actor::user('u-1', 'Pauline');

        $envelope = $this->bus->dispatch(new Message('approve'));

        $trace = $this->only('approve');
        self::assertSame(ActorKind::User, $trace->actor->kind);
        self::assertSame('Pauline', $trace->actor->label);
        self::assertTrue($trace->stamp->isRoot());
        self::assertSame($trace->stamp->messageId, $trace->stamp->correlationId);
        self::assertEquals($trace->stamp, $envelope->last(TraceStamp::class), 'the stamps stay on the envelope');
    }

    #[Test]
    public function without_anyone_signed_in_the_system_acts(): void
    {
        $this->bus->dispatch(new Message('cron'));

        self::assertTrue($this->only('cron')->actor->isSystem());
        self::assertSame('cli', $this->only('cron')->stamp->channel);
    }

    #[Test]
    public function what_a_message_triggers_is_done_by_the_system_within_the_same_chain(): void
    {
        $this->signedIn = Actor::user('u-1', 'Pauline');
        $this->then['approve'] = fn () => $this->bus->dispatch(new Message('deploy'));

        $this->bus->dispatch(new Message('approve'));

        $approve = $this->only('approve');
        $deploy = $this->only('deploy');
        self::assertTrue($deploy->actor->isSystem());
        self::assertSame($approve->stamp->correlationId, $deploy->stamp->correlationId);
        self::assertSame($approve->stamp->messageId, $deploy->stamp->causationId);
    }

    #[Test]
    public function run_as_names_the_actor_even_inside_a_chain(): void
    {
        $agent = Actor::agent('a-7', 'Chef de projet');
        $this->then['run-agent'] = fn () => $this->context->runAs(
            $agent,
            'agent',
            fn () => $this->bus->dispatch(new Message('read-contract')),
        );

        $this->bus->dispatch(new Message('run-agent'));

        $read = $this->only('read-contract');
        self::assertSame(ActorKind::Agent, $read->actor->kind);
        self::assertSame('a-7', $read->actor->id);
        self::assertSame('agent', $read->stamp->channel);
        self::assertSame($this->only('run-agent')->stamp->correlationId, $read->stamp->correlationId);
    }

    #[Test]
    public function an_explicit_actor_stamp_wins(): void
    {
        $this->signedIn = Actor::user('u-1', 'Pauline');

        $this->bus->dispatch(new Message('import'), [new ActorStamp(Actor::agent('a-7', 'Importer'))]);

        self::assertSame('a-7', $this->only('import')->actor->id);
    }

    #[Test]
    public function a_message_consumed_from_a_transport_keeps_its_actor_and_chain(): void
    {
        $this->signedIn = Actor::user('u-1', 'Pauline');
        $sent = $this->bus->dispatch(new Message('send-mail'));
        $this->signedIn = null; // the worker has nobody signed in
        $this->then['send-mail'] = fn () => $this->bus->dispatch(new Message('log-mail'));

        // What a transport hands back: the serialized envelope, minus what only
        // made sense in the sending process.
        $serializer = new PhpSerializer();
        $received = $serializer->decode($serializer->encode($sent->withoutAll(HandledStamp::class)))
            ->with(new ReceivedStamp('async'));
        $this->bus->dispatch($received);

        [$first, $consumed] = $this->seen['send-mail'];
        self::assertSame('Pauline', $consumed->actor->label);
        self::assertSame($first->stamp->messageId, $consumed->stamp->messageId);
        self::assertSame($consumed->stamp->messageId, $this->only('log-mail')->stamp->causationId);
    }

    #[Test]
    public function a_message_consumed_without_trace_starts_a_system_chain(): void
    {
        $this->bus->dispatch(new Envelope(new Message('scheduled'), [new ReceivedStamp('scheduler')]));

        self::assertTrue($this->only('scheduled')->actor->isSystem());
        self::assertSame('worker', $this->only('scheduled')->stamp->channel);
    }

    #[Test]
    public function a_message_dispatched_after_its_parent_still_knows_its_cause(): void
    {
        $this->signedIn = Actor::user('u-1', 'Pauline');
        $this->then['approve'] = fn () => $this->bus->dispatch(new Message('notify'), [new DispatchAfterCurrentBusStamp()]);

        $this->bus->dispatch(new Message('approve'));

        self::assertSame($this->only('approve')->stamp->messageId, $this->only('notify')->stamp->causationId);
        self::assertTrue($this->only('notify')->actor->isSystem());
    }

    #[Test]
    public function a_failing_handler_does_not_leak_its_trace(): void
    {
        $this->then['boom'] = static fn () => throw new \RuntimeException('boom');

        try {
            $this->bus->dispatch(new Message('boom'));
            self::fail('the exception must surface');
        } catch (\Throwable) {
        }

        self::assertNull($this->context->current());
        $this->bus->dispatch(new Message('next'));
        self::assertTrue($this->only('next')->stamp->isRoot());
    }

    private function only(string $name): Trace
    {
        self::assertArrayHasKey($name, $this->seen);
        self::assertCount(1, $this->seen[$name]);

        return $this->seen[$name][0];
    }
}
