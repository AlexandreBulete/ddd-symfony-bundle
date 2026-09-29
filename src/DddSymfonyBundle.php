<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle;

use Symfony\Component\AssetMapper\AssetMapper;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use AlexandreBulete\DddSymfonyBundle\DependencyInjection\AuthorizationMiddlewarePass;
use AlexandreBulete\DddSymfonyBundle\DependencyInjection\PermissionDiscoveryPass;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionProviderInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use AlexandreBulete\DddFoundation\Application\Query\AsQueryHandler;
use AlexandreBulete\DddFoundation\Application\Query\QueryInterface;
use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class DddSymfonyBundle extends AbstractBundle
{
    /**
     * @param array<mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import($this->getPath().'/config/services.php');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->prependExtensionConfig('framework', [
            'messenger' => [
                'default_bus' => 'command.bus',
                'buses' => [
                    // Tracing first: the transaction, and anything that reads
                    // the current trace inside it, already know who acts.
                    'command.bus' => [
                        'middleware' => [
                            'ddd.messenger.tracing_middleware',
                            'messenger.middleware.doctrine_transaction',
                        ],
                    ],
                    'query.bus' => [
                        'middleware' => [
                            'ddd.messenger.tracing_middleware',
                        ],
                    ],
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

        // Declaring asset_mapper paths enables AssetMapper: only when the
        // component is installed, or a project without it cannot boot.
        if (!class_exists(AssetMapper::class)) {
            return;
        }

        $builder->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    $this->getPath().'/assets/dist' => '@alexandrebulete/ddd-symfony-bundle',
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

        $container->registerForAutoconfiguration(PermissionProviderInterface::class)
            ->addTag('ddd.permission_provider');

        // Both before Messenger's pass (priority 0), which builds the buses.
        // Authorization after the activity journal's (10): closer to the
        // handling, so a refusal is journaled on its way out.
        $container->addCompilerPass(new PermissionDiscoveryPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 5);
        $container->addCompilerPass(new AuthorizationMiddlewarePass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 5);
    }
}

