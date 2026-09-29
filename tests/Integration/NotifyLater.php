<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Integration;

use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;

/**
 * @implements CommandInterface<void>
 */
final readonly class NotifyLater implements CommandInterface
{
}
