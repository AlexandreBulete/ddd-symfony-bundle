<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Tracing;

enum ActorKind: string
{
    /** A person, signed in. */
    case User = 'user';

    /** A non-human account (an AI agent, an integration), authenticated by token. */
    case Agent = 'agent';

    /** No one: a reaction coded in the application, a cron, the CLI. */
    case System = 'system';
}
