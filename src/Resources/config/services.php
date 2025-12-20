<?php

declare(strict_types=1);

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddFoundation\Application\Query\QueryBusInterface;
use AlexandreBulete\DddSymfonyBundle\Messenger\MessengerCommandBus;
use AlexandreBulete\DddSymfonyBundle\Messenger\MessengerQueryBus;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(MessengerCommandBus::class)
        ->args([service('command.bus')])
        ->alias(CommandBusInterface::class, MessengerCommandBus::class);

    $services->set(MessengerQueryBus::class)
        ->args([service('query.bus')])
        ->alias(QueryBusInterface::class, MessengerQueryBus::class);
};

