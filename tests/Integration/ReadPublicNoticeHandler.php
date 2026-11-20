<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Integration;

use AlexandreBulete\DddFoundation\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class ReadPublicNoticeHandler
{
    public function __invoke(ReadPublicNotice $query): string
    {
        return 'open to all';
    }
}
