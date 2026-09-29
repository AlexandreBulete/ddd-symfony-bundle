<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Integration;

use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionCheckerInterface;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Actor;

/**
 * What an IAM would provide — here, everyone may find traces, nobody may
 * record one.
 */
final readonly class OnlyFindTraceChecker implements PermissionCheckerInterface
{
    public function isGranted(Actor $actor, string $permission): bool
    {
        return $permission === 'test.find_trace';
    }
}
