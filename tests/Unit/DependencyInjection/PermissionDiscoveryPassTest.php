<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Unit\DependencyInjection;

use AlexandreBulete\DddSymfonyBundle\DependencyInjection\PermissionDiscoveryPass;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionCheckerInterface;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionRegistry;
use AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\Billing\Application\Command\IssueInvoiceCommand;
use AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\Billing\Application\Query\FindInvoicesQuery;
use AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\Billing\Application\Query\ShowInvoiceQuery;
use AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\FindInvoicesHandler;
use AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\IssueInvoiceHandler;
use AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\LooseHandler;
use AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\ShowInvoiceHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;

final class PermissionDiscoveryPassTest extends TestCase
{
    #[Test]
    public function every_handled_use_case_becomes_a_permission(): void
    {
        $container = $this->container([
            IssueInvoiceHandler::class => 'command.bus',
            FindInvoicesHandler::class => 'query.bus',
            ShowInvoiceHandler::class => 'query.bus',
        ]);

        (new PermissionDiscoveryPass())->process($container);

        self::assertSame([
            IssueInvoiceCommand::class => 'billing.issue_invoice',
            FindInvoicesQuery::class => 'billing.read',
            ShowInvoiceQuery::class => 'billing.read',
        ], $container->getDefinition(PermissionRegistry::class)->getArgument(0));
    }

    #[Test]
    public function with_authorization_active_a_use_case_without_permission_fails_the_build(): void
    {
        $container = $this->container([LooseHandler::class => 'command.bus'], withChecker: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/LooseCommand.*#\[Permission\]/');

        (new PermissionDiscoveryPass())->process($container);
    }

    #[Test]
    public function without_authorization_it_is_only_left_out(): void
    {
        $container = $this->container([
            LooseHandler::class => 'command.bus',
            IssueInvoiceHandler::class => 'command.bus',
        ]);

        (new PermissionDiscoveryPass())->process($container);

        self::assertSame(
            [IssueInvoiceCommand::class => 'billing.issue_invoice'],
            $container->getDefinition(PermissionRegistry::class)->getArgument(0),
        );
    }

    /**
     * @param array<class-string, string> $handlers handler => bus
     */
    private function container(array $handlers, bool $withChecker = false): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(PermissionRegistry::class, new Definition(PermissionRegistry::class, [[]]));
        if ($withChecker) {
            $container->setDefinition(PermissionCheckerInterface::class, new Definition(\stdClass::class));
        }

        foreach ($handlers as $handler => $bus) {
            $container->setDefinition($handler, (new Definition($handler))->addTag('messenger.message_handler', ['bus' => $bus]));
        }

        return $container;
    }
}
