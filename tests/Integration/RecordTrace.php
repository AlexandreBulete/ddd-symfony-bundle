<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Integration;

use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Trace;

/**
 * @implements CommandInterface<Trace|null>
 */
final readonly class RecordTrace implements CommandInterface
{
}
