<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Integration;

use AlexandreBulete\DddFoundation\Application\Authorization\Permission;
use AlexandreBulete\DddFoundation\Application\Query\QueryInterface;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Trace;

/**
 * @implements QueryInterface<Trace|null>
 */
#[Permission('test.find_trace')]
final readonly class FindTrace implements QueryInterface
{
}
