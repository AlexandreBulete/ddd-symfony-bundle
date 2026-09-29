<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Tracing;

/**
 * Who is behind a message.
 *
 * The label is a snapshot taken when the message is sent: a journal entry must
 * keep saying "Pauline Martin" after the account is renamed.
 */
final readonly class Actor
{
    private function __construct(
        public ActorKind $kind,
        public ?string $id,
        public string $label,
    ) {}

    public static function user(string $id, string $label): self
    {
        return new self(ActorKind::User, $id, $label);
    }

    public static function agent(string $id, string $label): self
    {
        return new self(ActorKind::Agent, $id, $label);
    }

    public static function system(): self
    {
        return new self(ActorKind::System, null, 'system');
    }

    public function isSystem(): bool
    {
        return $this->kind === ActorKind::System;
    }
}
