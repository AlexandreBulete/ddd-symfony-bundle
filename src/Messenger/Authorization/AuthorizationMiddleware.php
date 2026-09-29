<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Authorization;

use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use AlexandreBulete\DddFoundation\Application\Query\QueryInterface;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Tracer;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * Refuses a command or query its actor has no permission for (ADR 0008).
 *
 * Right before the handling (AuthorizationMiddlewarePass), so it runs where a
 * message is handled: an async message is checked again in the worker —
 * rights withdrawn in between apply. The system is not checked: it is code
 * (a reaction, a cron), not someone asking.
 */
final readonly class AuthorizationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private PermissionRegistry $permissions,
        private PermissionCheckerInterface $checker,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        if (!$message instanceof CommandInterface && !$message instanceof QueryInterface) {
            return $stack->next()->handle($envelope, $stack);
        }

        $actor = Tracer::traceOf($envelope)->actor;
        if ($actor->isSystem()) {
            return $stack->next()->handle($envelope, $stack);
        }

        $permission = $this->permissions->permissionOf($message::class)
            ?? throw new \LogicException(sprintf('%s has no known permission: is its handler registered?', $message::class));

        if (!$this->checker->isGranted($actor, $permission)) {
            throw new PermissionDenied($actor, $permission, $message::class);
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
