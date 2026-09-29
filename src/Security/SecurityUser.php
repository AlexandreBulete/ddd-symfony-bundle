<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Security;

use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Actor;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\ActorAwareInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface, ActorAwareInterface
{
    /** @var non-empty-string */
    private string $userIdentifier;

    /**
     * @param list<string> $roles
     * @param Actor|null   $actor the account behind this login; defaults to the
     *                            identifier, which may change (an email)
     */
    public function __construct(
        string $userIdentifier,
        private string $hashedPassword,
        private array $roles = ['ROLE_USER'],
        private ?Actor $actor = null,
    ) {
        if ($userIdentifier === '') {
            throw new \InvalidArgumentException('A security user needs a non-empty identifier.');
        }
        $this->userIdentifier = $userIdentifier;
    }

    public function toActor(): Actor
    {
        return $this->actor ?? Actor::user($this->userIdentifier, $this->userIdentifier);
    }

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
