<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Unit\Messenger\Tracing;

final readonly class Message
{
    public function __construct(
        public string $name,
    ) {}
}
