<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Integration;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddFoundation\Application\Query\QueryBusInterface;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Trace;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BundleWiringTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    #[Test]
    public function commands_and_queries_are_traced_without_security_installed(): void
    {
        $container = self::getContainer();

        $command = $container->get('test.command_bus');
        self::assertInstanceOf(CommandBusInterface::class, $command);
        $query = $container->get('test.query_bus');
        self::assertInstanceOf(QueryBusInterface::class, $query);

        $commandTrace = $command->dispatch(new RecordTrace());
        $queryTrace = $query->ask(new FindTrace());

        self::assertInstanceOf(Trace::class, $commandTrace);
        self::assertTrue($commandTrace->actor->isSystem(), 'no security: the system acts');
        self::assertInstanceOf(Trace::class, $queryTrace);
        self::assertNotSame($commandTrace->stamp->correlationId, $queryTrace->stamp->correlationId);
    }

    #[Test]
    public function a_deferred_message_keeps_its_cause(): void
    {
        $command = self::getContainer()->get('test.command_bus');
        self::assertInstanceOf(CommandBusInterface::class, $command);
        $probe = self::getContainer()->get(TraceProbe::class);
        self::assertInstanceOf(TraceProbe::class, $probe);

        $root = $command->dispatch(new RecordTrace());

        self::assertInstanceOf(Trace::class, $root);
        self::assertArrayHasKey('notify', $probe->seen, 'the deferred command ran');
        self::assertSame($root->stamp->messageId, $probe->seen['notify']->stamp->causationId);
    }
}
