<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Authorization;

use AlexandreBulete\DddFoundation\Application\Authorization\Permission;

/**
 * The permission a use case requires (ADR 0008): the one it declares with
 * #[Permission], or one derived from its class —
 *
 *   App\Client\Application\Query\FindClients\FindClientsQuery → client.find_clients
 *   AlexandreBulete\DddIamBundle\Application\Command\CreateUser\CreateUserCommand → iam.create_user
 *
 * The context is the namespace segment before `Application` (a bundle's
 * `Ddd…Bundle` reduced to its name); the use case, the class name without its
 * Command/Query suffix. Null when neither applies.
 */
final class PermissionName
{
    /**
     * @param class-string $messageClass
     */
    public static function of(string $messageClass): ?string
    {
        $declared = (new \ReflectionClass($messageClass))->getAttributes(Permission::class);
        if ($declared !== []) {
            return $declared[0]->newInstance()->id;
        }

        return self::derive($messageClass);
    }

    /**
     * @param class-string $messageClass
     */
    public static function isDeclared(string $messageClass): bool
    {
        return (new \ReflectionClass($messageClass))->getAttributes(Permission::class) !== [];
    }

    private static function derive(string $messageClass): ?string
    {
        $segments = explode('\\', $messageClass);
        $application = array_search('Application', $segments, true);
        if ($application === false || $application === 0) {
            return null;
        }

        $context = preg_replace('/^Ddd(.+)Bundle$/', '$1', $segments[$application - 1]) ?? '';
        $useCase = preg_replace('/(Command|Query)$/', '', $segments[array_key_last($segments)]) ?? '';
        if ($context === '' || $useCase === '') {
            return null;
        }

        return self::snake($context) . '.' . self::snake($useCase);
    }

    private static function snake(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name) ?? $name);
    }
}
