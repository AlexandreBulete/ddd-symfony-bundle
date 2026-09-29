<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Unit\Messenger\Authorization;

use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionName;
use AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\Billing\Application\Command\IssueInvoiceCommand;
use AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\Billing\Application\Query\FindInvoicesQuery;
use AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\Loose\LooseCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PermissionNameTest extends TestCase
{
    #[Test]
    public function it_is_derived_from_the_context_and_the_use_case(): void
    {
        self::assertSame('billing.issue_invoice', PermissionName::of(IssueInvoiceCommand::class));
    }

    #[Test]
    public function a_bundle_context_drops_its_ddd_and_bundle_affixes(): void
    {
        // No such class needed: derivation only reads the name. Declared
        // permissions take precedence, which the class-based cases cover.
        $derive = new \ReflectionMethod(PermissionName::class, 'derive');

        self::assertSame('iam.create_user', $derive->invoke(null, 'AlexandreBulete\DddIamBundle\Application\Command\CreateUser\CreateUserCommand'));
        self::assertSame('client.find_clients', $derive->invoke(null, 'App\Client\Application\Query\FindClients\FindClientsQuery'));
    }

    #[Test]
    public function a_declared_permission_wins(): void
    {
        self::assertSame('billing.read', PermissionName::of(FindInvoicesQuery::class));
        self::assertTrue(PermissionName::isDeclared(FindInvoicesQuery::class));
    }

    #[Test]
    public function outside_the_convention_there_is_none(): void
    {
        self::assertNull(PermissionName::of(LooseCommand::class));
    }
}
