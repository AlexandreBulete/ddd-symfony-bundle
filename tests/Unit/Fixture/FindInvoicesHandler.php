<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture;

use AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\Billing\Application\Query\FindInvoicesQuery;

final readonly class FindInvoicesHandler
{
    public function __invoke(FindInvoicesQuery $message): void
    {
    }
}
