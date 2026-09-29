<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\DependencyInjection;

use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\AuthorizationMiddleware;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionCheckerInterface;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Installs the authorization middleware — only when something can check
 * permissions (an IAM registers a PermissionCheckerInterface). A project
 * without one gets no deny-by-default imposed (ADR 0008).
 *
 * Right before `handle_message`, like the activity journal, and after it: a
 * refusal passes through the journal on its way out, and is recorded.
 */
final class AuthorizationMiddlewarePass implements CompilerPassInterface
{
    public const MIDDLEWARE = 'ddd.messenger.authorization_middleware';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(PermissionCheckerInterface::class)) {
            return;
        }

        $container->setDefinition(self::MIDDLEWARE, new Definition(AuthorizationMiddleware::class, [
            new Reference(PermissionRegistry::class),
            new Reference(PermissionCheckerInterface::class),
        ]));

        foreach (['command.bus', 'query.bus'] as $bus) {
            $parameter = $bus . '.middleware';
            if (!$container->hasParameter($parameter)) {
                continue;
            }

            /** @var list<array{id: string, arguments?: array<mixed>}> $middleware */
            $middleware = $container->getParameter($parameter);
            $ids = array_column($middleware, 'id');
            if (in_array(self::MIDDLEWARE, $ids, true)) {
                continue;
            }

            $position = array_search('handle_message', $ids, true);
            array_splice($middleware, $position === false ? count($middleware) : $position, 0, [['id' => self::MIDDLEWARE, 'arguments' => []]]);
            $container->setParameter($parameter, $middleware);
        }
    }
}
