<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Authorization;

use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Actor;

/**
 * Whether an actor holds a permission. Provided by an IAM (ddd-iam-bundle
 * resolves roles to permissions); without an implementation, the
 * authorization middleware is not installed at all (ADR 0008).
 */
interface PermissionCheckerInterface
{
    public function isGranted(Actor $actor, string $permission): bool;
}
