<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Integration;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddFoundation\Application\Query\QueryBusInterface;
use AlexandreBulete\DddSymfonyBundle\DddSymfonyBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

/**
 * The bundle as a project installs it — without Security, without IAM: the
 * minimum it must work with.
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new DddSymfonyBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/ddd-symfony-bundle-tests/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/ddd-symfony-bundle-tests/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'test' => true,
            'secret' => 'test',
            'http_method_override' => false,
        ]);
        $container->extension('doctrine', [
            'dbal' => ['url' => 'sqlite:///:memory:'],
            'orm' => ['mappings' => []],
        ]);

        $services = $container->services();
        $services->set(RecordTraceHandler::class)->autowire()->autoconfigure();
        $services->set(FindTraceHandler::class)->autowire()->autoconfigure();
        $services->set(NotifyLaterHandler::class)->autowire()->autoconfigure();
        $services->set(TraceProbe::class)->public();

        // The buses as an application gets them, kept public for the test.
        $services->alias('test.command_bus', CommandBusInterface::class)->public();
        $services->alias('test.query_bus', QueryBusInterface::class)->public();
    }
}
