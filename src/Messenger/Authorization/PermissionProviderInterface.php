<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger\Authorization;

/**
 * Declares permissions that no message carries — checked by the code that
 * owns them, not by the bus: "access the back office" is enforced by the
 * firewall, for instance. Autoconfigured: implementing it is enough.
 */
interface PermissionProviderInterface
{
    /**
     * @return list<string>
     */
    public function permissions(): array;
}
