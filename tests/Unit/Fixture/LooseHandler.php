<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture;

use AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\Loose\LooseCommand;

final readonly class LooseHandler
{
    public function __invoke(LooseCommand $message): void
    {
    }
}
