<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        private string $userIdentifier,
        private string $hashedPassword,
        private array $roles = ['ROLE_USER'],
    ) {}

    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function getPassword(): string
    {
        return $this->hashedPassword;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
    }
}
