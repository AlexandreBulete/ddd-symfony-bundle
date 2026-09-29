<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Integration;

use AlexandreBulete\DddFoundation\Application\Query\AsQueryHandler;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Trace;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\TraceContext;

#[AsQueryHandler]
final readonly class FindTraceHandler
{
    public function __construct(private TraceContext $context) {}

    public function __invoke(FindTrace $query): ?Trace
    {
        return $this->context->current();
    }
}
