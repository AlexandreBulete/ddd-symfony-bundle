<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\Billing\Application\Query;

use AlexandreBulete\DddFoundation\Application\Authorization\Permission;
use AlexandreBulete\DddFoundation\Application\Query\QueryInterface;

/**
 * Grouped with FindInvoicesQuery, on purpose.
 *
 * @implements QueryInterface<string>
 */
#[Permission('billing.read')]
final readonly class ShowInvoiceQuery implements QueryInterface
{
}
