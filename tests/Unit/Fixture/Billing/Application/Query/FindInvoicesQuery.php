<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\Billing\Application\Query;

use AlexandreBulete\DddFoundation\Application\Authorization\Permission;
use AlexandreBulete\DddFoundation\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<list<string>>
 */
#[Permission('billing.read')]
final readonly class FindInvoicesQuery implements QueryInterface
{
}
