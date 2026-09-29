<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Unit\Messenger\Tracing;

use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Actor;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\ActorKind;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ActorTest extends TestCase
{
    #[Test]
    public function the_credential_survives_a_transport(): void
    {
        $actor = unserialize(serialize(Actor::agent('a-7', 'Veille', 'tok-1')));

        self::assertInstanceOf(Actor::class, $actor);
        self::assertSame('tok-1', $actor->credential);
        self::assertSame(ActorKind::Agent, $actor->kind);
    }

    #[Test]
    public function an_actor_serialized_before_credentials_existed_is_still_readable(): void
    {
        // What 1.3 wrote: public properties, no credential.
        $payload = sprintf(
            'O:%d:"%s":3:{s:4:"kind";E:%d:"%s";s:2:"id";s:3:"u-1";s:5:"label";s:7:"Pauline";}',
            strlen(Actor::class),
            Actor::class,
            strlen(ActorKind::class . ':User'),
            ActorKind::class . ':User',
        );

        $actor = unserialize($payload);

        self::assertInstanceOf(Actor::class, $actor);
        self::assertSame('Pauline', $actor->label);
        self::assertNull($actor->credential);
    }
}
