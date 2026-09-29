<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Authorization;

/**
 * Every permission of the application: discovered from its handlers when the
 * container is built (PermissionDiscoveryPass), plus those declared by
 * PermissionProviderInterface services — what a role screen lists, and what
 * the authorization middleware looks up.
 */
final readonly class PermissionRegistry
{
    /**
     * @param array<class-string, string>          $permissions message class => permission
     * @param iterable<PermissionProviderInterface> $providers
     */
    public function __construct(
        private array $permissions,
        private iterable $providers = [],
    ) {}

    /**
     * @param class-string $messageClass
     */
    public function permissionOf(string $messageClass): ?string
    {
        return $this->permissions[$messageClass] ?? null;
    }

    /**
     * @return list<string> sorted, unique
     */
    public function all(): array
    {
        $all = array_values($this->permissions);
        foreach ($this->providers as $provider) {
            array_push($all, ...$provider->permissions());
        }

        $all = array_values(array_unique($all));
        sort($all);

        return $all;
    }

    /**
     * @return array<string, list<string>> context ("client") => its permissions
     */
    public function byContext(): array
    {
        $grouped = [];
        foreach ($this->all() as $permission) {
            $context = strstr($permission, '.', true);
            $grouped[$context === false ? $permission : $context][] = $permission;
        }

        return $grouped;
    }

    public function has(string $permission): bool
    {
        return in_array($permission, $this->all(), true);
    }
}
