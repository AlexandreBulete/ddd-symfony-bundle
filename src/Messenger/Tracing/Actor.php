<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Tracing;

/**
 * Who is behind a message.
 *
 * The label is a snapshot taken when the message is sent: a journal entry must
 * keep saying "Pauline Martin" after the account is renamed.
 *
 * The credential is what the actor authenticated with, when it is worth
 * tracing on its own — an API token's id, never the token: when one leaks,
 * "everything done with it" must be a search, not a guess.
 */
final readonly class Actor
{
    private function __construct(
        public ActorKind $kind,
        public ?string $id,
        public string $label,
        public ?string $credential = null,
    ) {}

    public static function user(string $id, string $label): self
    {
        return new self(ActorKind::User, $id, $label);
    }

    public static function agent(string $id, string $label, ?string $credential = null): self
    {
        return new self(ActorKind::Agent, $id, $label, $credential);
    }

    public static function system(): self
    {
        return new self(ActorKind::System, null, 'system');
    }

    public function isSystem(): bool
    {
        return $this->kind === ActorKind::System;
    }

    /**
     * @return array{kind: ActorKind, id: ?string, label: string, credential: ?string}
     */
    public function __serialize(): array
    {
        return ['kind' => $this->kind, 'id' => $this->id, 'label' => $this->label, 'credential' => $this->credential];
    }

    /**
     * An actor serialized before a field existed (a message waiting in an
     * async transport across a deployment) must still be readable.
     *
     * @param array{kind: ActorKind, id: ?string, label: string, credential?: ?string} $data
     */
    public function __unserialize(array $data): void
    {
        $this->kind = $data['kind'];
        $this->id = $data['id'];
        $this->label = $data['label'];
        $this->credential = $data['credential'] ?? null;
    }
}
