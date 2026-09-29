<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Integration;

use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Trace;

/**
 * Collects the traces handlers saw, for assertions after the fact.
 */
final class TraceProbe
{
    /** @var array<string, Trace> */
    public array $seen = [];
}
