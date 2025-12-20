<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use AlexandreBulete\DddFoundation\Application\Query\AsQueryHandler;
use AlexandreBulete\DddFoundation\Application\Query\QueryInterface;
use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class DddSymfonyBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // $container->import('../src/Resources/config/services.php');
        // $container->import('../src/Resources/config/messenger.php');
        $container->import(__DIR__.'/Resources/config/services.php');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Configurer le framework messenger via prepend
        $builder->prependExtensionConfig('framework', [
            'messenger' => [
                'default_bus' => 'command.bus',
                'buses' => [
                    'command.bus' => [
                        'middleware' => [
                            'messenger.middleware.doctrine_transaction',
                        ],
                    ],
                    'query.bus' => [],
                ],
                'transports' => [
                    'sync' => 'sync://',
                ],
                'routing' => [
                    QueryInterface::class => 'sync',
                    CommandInterface::class => 'sync',
                ],
            ],
        ]);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerAttributeForAutoconfiguration(
            AsQueryHandler::class,
            static function (ChildDefinition $definition): void {
                $definition->addTag('messenger.message_handler', ['bus' => 'query.bus']);
            }
        );

        $container->registerAttributeForAutoconfiguration(
            AsCommandHandler::class,
            static function (ChildDefinition $definition): void {
                $definition->addTag('messenger.message_handler', ['bus' => 'command.bus']);
            }
        );
    }
}

