<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Tracing;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Reads the actor from Symfony Security, when it is installed and someone is
 * signed in.
 */
final readonly class SecurityActorResolver implements ActorResolverInterface
{
    public function __construct(
        private ?TokenStorageInterface $tokenStorage = null,
    ) {}

    public function resolve(): ?Actor
    {
        $user = $this->tokenStorage?->getToken()?->getUser();
        if ($user === null) {
            return null;
        }

        if ($user instanceof ActorAwareInterface) {
            return $user->toActor();
        }

        return Actor::user($user->getUserIdentifier(), $user->getUserIdentifier());
    }
}
