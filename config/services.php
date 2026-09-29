<?php

declare(strict_types=1);

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddFoundation\Application\Event\EventDispatcherInterface;
use AlexandreBulete\DddFoundation\Application\Query\QueryBusInterface;
use AlexandreBulete\DddSymfonyBundle\Event\SymfonyEventDispatcher;
use AlexandreBulete\DddSymfonyBundle\Messenger\MessengerCommandBus;
use AlexandreBulete\DddSymfonyBundle\Messenger\MessengerQueryBus;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\ActorResolverInterface;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\SecurityActorResolver;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\TraceContext;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Tracer;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\TracingMessageBus;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\TracingMiddleware;
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

    $services->set(SymfonyEventDispatcher::class)
        ->alias(EventDispatcherInterface::class, SymfonyEventDispatcher::class);

    // ── Tracing: actor and chain of causes on every message (ADR 0008/0009) ─
    $services->set(TraceContext::class)
        ->tag('kernel.reset', ['method' => 'reset']);

    // Security is optional: without it, nobody is ever signed in and the
    // system is the actor of every chain.
    $services->set(SecurityActorResolver::class)
        ->args([service('security.token_storage')->nullOnInvalid()]);
    $services->alias(ActorResolverInterface::class, SecurityActorResolver::class);

    $services->set(Tracer::class);

    // Stamps at dispatch time, before any middleware: the only moment the cause
    // of a deferred message (DispatchAfterCurrentBusStamp) is still known.
    foreach (['command.bus', 'query.bus'] as $bus) {
        $services->set('ddd.messenger.tracing.' . $bus, TracingMessageBus::class)
            ->decorate($bus)
            ->args([service('.inner'), service(Tracer::class)]);
    }

    // Referenced by id in the bus middleware lists (DddSymfonyBundle::prependExtension()).
    $services->set('ddd.messenger.tracing_middleware', TracingMiddleware::class);
};

