<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Tracing;

/**
 * Implemented by a Symfony security user that knows which account it stands
 * for. Without it, the actor falls back to the user identifier (often an email,
 * which can change — hence this interface).
 */
interface ActorAwareInterface
{
    public function toActor(): Actor;
}
