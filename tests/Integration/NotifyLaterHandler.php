<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Integration;

use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\TraceContext;

#[AsCommandHandler]
final readonly class NotifyLaterHandler
{
    public function __construct(
        private TraceContext $context,
        private TraceProbe $probe,
    ) {}

    public function __invoke(NotifyLater $command): void
    {
        $trace = $this->context->current();
        if ($trace !== null) {
            $this->probe->seen['notify'] = $trace;
        }
    }
}
