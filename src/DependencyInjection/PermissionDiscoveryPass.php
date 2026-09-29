<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\DependencyInjection;

use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddFoundation\Application\Query\QueryInterface;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionCheckerInterface;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionName;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;

/**
 * Builds the PermissionRegistry from the handlers of the command and query
 * buses: every use case is a permission, with nothing to declare (ADR 0008).
 *
 * When authorization is active (a PermissionCheckerInterface exists), the
 * build fails — rather than a use case silently escaping it — when a message
 * has no permission (outside the naming convention, without #[Permission]),
 * or when two messages derive the same one by accident. Without a checker,
 * nothing is enforced, and the registry simply leaves such messages out: an
 * application that does not use authorization is not asked to comply.
 */
final class PermissionDiscoveryPass implements CompilerPassInterface
{
    private const BUSES = ['command.bus', 'query.bus'];

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(PermissionRegistry::class)) {
            return;
        }

        $permissions = [];
        $problems = [];

        foreach ($container->findTaggedServiceIds('messenger.message_handler') as $id => $tags) {
            foreach ($tags as $tag) {
                if (!is_array($tag)) {
                    continue;
                }

                $bus = $tag['bus'] ?? null;
                if (is_string($bus) && !in_array($bus, self::BUSES, true)) {
                    continue;
                }

                foreach ($this->handledMessages($container, $id, $tag) as $messageClass) {
                    if (!is_subclass_of($messageClass, CommandInterface::class) && !is_subclass_of($messageClass, QueryInterface::class)) {
                        continue;
                    }

                    $permission = PermissionName::of($messageClass);
                    if ($permission === null) {
                        $problems[] = sprintf('%s: outside the <Context>\Application\… convention — declare #[Permission].', $messageClass);
                        continue;
                    }

                    $permissions[$messageClass] = $permission;
                }
            }
        }

        $byPermission = [];
        foreach ($permissions as $messageClass => $permission) {
            $byPermission[$permission][] = $messageClass;
        }
        foreach ($byPermission as $permission => $classes) {
            $derived = array_filter($classes, static fn (string $class): bool => !PermissionName::isDeclared($class));
            if (count($classes) > 1 && $derived !== []) {
                $problems[] = sprintf('"%s" is derived for %s — group them on purpose with #[Permission], or rename.', $permission, implode(', ', $classes));
            }
        }

        if ($problems !== [] && $container->has(PermissionCheckerInterface::class)) {
            throw new LogicException("Use cases without a usable permission (ADR 0008):\n - " . implode("\n - ", $problems));
        }

        ksort($permissions);
        $container->getDefinition(PermissionRegistry::class)->replaceArgument(0, $permissions);
    }

    /**
     * @param array<mixed> $tag
     *
     * @return list<class-string>
     */
    private function handledMessages(ContainerBuilder $container, string $id, array $tag): array
    {
        $handles = $tag['handles'] ?? null;
        if (is_string($handles) && class_exists($handles)) {
            return [$handles];
        }

        $class = $container->getParameterBag()->resolveValue($container->findDefinition($id)->getClass());
        if (!is_string($class)) {
            return [];
        }

        $reflection = $container->getReflectionClass($class, false);
        $method = is_string($tag['method'] ?? null) ? $tag['method'] : '__invoke';
        if ($reflection === null || !$reflection->hasMethod($method)) {
            return [];
        }

        $parameter = $reflection->getMethod($method)->getParameters()[0] ?? null;
        $type = $parameter?->getType();
        $types = $type instanceof \ReflectionUnionType ? $type->getTypes() : [$type];

        $messages = [];
        foreach ($types as $candidate) {
            if ($candidate instanceof \ReflectionNamedType && !$candidate->isBuiltin() && class_exists($candidate->getName())) {
                $messages[] = $candidate->getName();
            }
        }

        return $messages;
    }
}
