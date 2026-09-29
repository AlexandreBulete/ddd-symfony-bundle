<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Integration;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Trace;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\TraceContext;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

/**
 * Also sends a deferred command: its cause must survive the deferral, which
 * only the TracingMessageBus decorator makes possible.
 */
#[AsCommandHandler]
final readonly class RecordTraceHandler
{
    public function __construct(
        private TraceContext $context,
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(RecordTrace $command): ?Trace
    {
        $this->commandBus->dispatch(new NotifyLater(), [new DispatchAfterCurrentBusStamp()]);

        return $this->context->current();
    }
}
