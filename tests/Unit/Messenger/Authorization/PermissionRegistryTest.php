<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Unit\Messenger\Authorization;

use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionProviderInterface;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionRegistry;
use AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\Billing\Application\Query\FindInvoicesQuery;
use AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\Billing\Application\Query\ShowInvoiceQuery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PermissionRegistryTest extends TestCase
{
    #[Test]
    public function it_merges_discovered_and_declared_permissions_by_context(): void
    {
        $registry = new PermissionRegistry(
            [FindInvoicesQuery::class => 'billing.read', ShowInvoiceQuery::class => 'billing.read'],
            [new class implements PermissionProviderInterface {
                public function permissions(): array
                {
                    return ['backoffice.access'];
                }
            }],
        );

        self::assertSame(['backoffice.access', 'billing.read'], $registry->all());
        self::assertSame(['backoffice' => ['backoffice.access'], 'billing' => ['billing.read']], $registry->byContext());
        self::assertTrue($registry->has('backoffice.access'));
        self::assertSame('billing.read', $registry->permissionOf(ShowInvoiceQuery::class));
    }
}
