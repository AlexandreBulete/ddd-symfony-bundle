<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture;

use AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\Billing\Application\Query\ShowInvoiceQuery;

final readonly class ShowInvoiceHandler
{
    public function __invoke(ShowInvoiceQuery $message): void
    {
    }
}
