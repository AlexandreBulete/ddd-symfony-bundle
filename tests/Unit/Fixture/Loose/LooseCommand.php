<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\Loose;

use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;

/**
 * Outside the <Context>\Application\… convention, no #[Permission].
 *
 * @implements CommandInterface<void>
 */
final readonly class LooseCommand implements CommandInterface
{
}
