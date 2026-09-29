<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Authorization;

use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Actor;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * An actor asked for a use case it has no permission for. An
 * AccessDeniedException: over HTTP, the firewall answers 403 with nothing to
 * configure.
 */
final class PermissionDenied extends AccessDeniedException
{
    public function __construct(
        public readonly Actor $actor,
        public readonly string $permission,
        public readonly string $messageClass,
    ) {
        parent::__construct(sprintf('%s is not allowed to %s.', $actor->label, $permission));
    }
}
