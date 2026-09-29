<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Tracing;

/**
 * Who stands at the entry point when a chain starts, if anyone: the signed-in
 * user of the request, typically. Null means nobody — the system acts.
 */
interface ActorResolverInterface
{
    public function resolve(): ?Actor;
}
