<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Integration;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddFoundation\Application\Query\QueryBusInterface;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionDenied;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionRegistry;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Actor;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Trace;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\TraceContext;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AuthorizationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return AuthorizedTestKernel::class;
    }

    #[Test]
    public function every_use_case_is_a_discovered_permission(): void
    {
        $registry = self::getContainer()->get(PermissionRegistry::class);
        self::assertInstanceOf(PermissionRegistry::class, $registry);

        self::assertSame(['test.find_trace', 'test.notify_later', 'test.record_trace'], $registry->all());
    }

    #[Test]
    public function a_user_is_held_to_its_permissions(): void
    {
        $user = Actor::user('u-1', 'Pauline');

        self::assertInstanceOf(Trace::class, $this->as($user, fn () => $this->queries()->ask(new FindTrace())));

        $this->expectException(PermissionDenied::class);
        $this->as($user, fn () => $this->commands()->dispatch(new RecordTrace()));
    }

    #[Test]
    public function the_system_and_what_it_triggers_are_not_checked(): void
    {
        // RecordTrace is refused to everyone, and sends NotifyLater: both run
        // when nobody is signed in — the system acts.
        self::assertInstanceOf(Trace::class, $this->commands()->dispatch(new RecordTrace()));
    }

    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    private function as(Actor $actor, callable $operation): mixed
    {
        $context = self::getContainer()->get(TraceContext::class);
        self::assertInstanceOf(TraceContext::class, $context);

        return $context->runAs($actor, 'test', $operation);
    }

    private function commands(): CommandBusInterface
    {
        $bus = self::getContainer()->get('test.command_bus');
        self::assertInstanceOf(CommandBusInterface::class, $bus);

        return $bus;
    }

    private function queries(): QueryBusInterface
    {
        $bus = self::getContainer()->get('test.query_bus');
        self::assertInstanceOf(QueryBusInterface::class, $bus);

        return $bus;
    }
}
