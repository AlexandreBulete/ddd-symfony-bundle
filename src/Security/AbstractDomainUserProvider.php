<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Security;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @template TDomainUser of object
 * @implements UserProviderInterface<SecurityUser>
 */
abstract class AbstractDomainUserProvider implements UserProviderInterface
{
    /**
     * Load the domain entity by its security identifier (email, username, …).
     *
     * @return TDomainUser|null
     */
    abstract protected function loadDomainUser(string $identifier): ?object;

    /**
     * Map the domain entity to a Symfony Security user.
     *
     * @param TDomainUser $domainUser
     */
    abstract protected function toSecurityUser(object $domainUser): SecurityUser;

    public function loadUserByIdentifier(string $identifier): SecurityUser
    {
        $domainUser = $this->loadDomainUser($identifier);

        if ($domainUser === null) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return $this->toSecurityUser($domainUser);
    }

    public function refreshUser(UserInterface $user): SecurityUser
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === SecurityUser::class || is_subclass_of($class, SecurityUser::class);
    }
}
