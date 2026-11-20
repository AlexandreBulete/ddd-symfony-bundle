<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Integration;

use AlexandreBulete\DddFoundation\Application\Authorization\PublicAccess;
use AlexandreBulete\DddFoundation\Application\Query\QueryInterface;

/**
 * What a public site reads: nobody is granted it, everybody may run it.
 *
 * @implements QueryInterface<string>
 */
#[PublicAccess]
final readonly class ReadPublicNotice implements QueryInterface
{
}
