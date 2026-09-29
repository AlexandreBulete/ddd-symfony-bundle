<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Integration;

use AlexandreBulete\DddFoundation\Application\Query\QueryInterface;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Trace;

/**
 * @implements QueryInterface<Trace|null>
 */
final readonly class FindTrace implements QueryInterface
{
}
