<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture;

use AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\Billing\Application\Command\IssueInvoiceCommand;

final readonly class IssueInvoiceHandler
{
    public function __invoke(IssueInvoiceCommand $message): void
    {
    }
}
