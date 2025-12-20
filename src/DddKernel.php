<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

abstract class DddKernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $projectDir = $this->getProjectDir();

        // Standard Symfony imports
        $container->import($projectDir.'/config/{packages}/*.{php,yaml}');
        $container->import($projectDir.'/config/{packages}/'.$this->environment.'/*.{php,yaml}');
        $container->import($projectDir.'/config/services.yaml');
        $container->import($projectDir.'/config/{services}_'.$this->environment.'.yaml');

        // Auto-import Bounded Contexts services & packages
        $container->import($projectDir.'/src/*/Infrastructure/Symfony/config/services.{php,yaml}');
        $container->import($projectDir.'/src/*/Infrastructure/Symfony/config/packages/*.{php,yaml}');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $projectDir = $this->getProjectDir();

        // Standard Symfony routes
        $routes->import($projectDir.'/config/{routes}/'.$this->environment.'/*.{php,yaml}');
        $routes->import($projectDir.'/config/{routes}/*.{php,yaml}');

        // Auto-import Bounded Contexts routes
        $routes->import($projectDir.'/src/*/Infrastructure/Symfony/routes/*.{php,yaml}');
    }
}