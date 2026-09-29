<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Integration;

use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionCheckerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * The same kernel with a permission checker: authorization turns on.
 */
final class AuthorizedTestKernel extends TestKernel
{
    protected function configureAuthorization(ContainerConfigurator $container): void
    {
        $container->services()->set(OnlyFindTraceChecker::class);
        $container->services()->alias(PermissionCheckerInterface::class, OnlyFindTraceChecker::class);
    }
}
